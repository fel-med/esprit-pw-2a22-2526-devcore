"""
Train a logistic regression match model on the seed dataset and export JSON + metrics.

Uses NumPy for a lightweight logistic regression (gradient descent) so training works
on Windows even when scikit-learn cannot be installed (e.g. very new Python builds).
The exported JSON format matches what a future PHP or Python scorer can load.
"""
from __future__ import annotations

import csv
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import numpy as np

FEATURE_COLUMNS = [
    "category_match",
    "budget_fit",
    "deadline_fit",
    "has_portfolio",
    "creator_accept_rate",
    "previous_collabs_scaled",
    "rating_score",
    "response_quality_score",
    "text_similarity_score",
]

PREVIOUS_COLABS_MAX = 20.0


def sigmoid(z: np.ndarray) -> np.ndarray:
    z = np.clip(z, -40.0, 40.0)
    return 1.0 / (1.0 + np.exp(-z))


def train_logistic(
    X: np.ndarray, y: np.ndarray, *, lr: float = 0.6, epochs: int = 4000, seed: int = 42
) -> tuple[np.ndarray, float]:
    """Binary logistic regression via batch gradient descent."""
    rng = np.random.default_rng(seed)
    n, d = X.shape
    w = rng.normal(scale=0.01, size=d)
    b = 0.0
    for _ in range(epochs):
        p = sigmoid(X @ w + b)
        err = (p - y) / n
        grad_w = X.T @ err
        grad_b = float(np.sum(err))
        w -= lr * grad_w
        b -= lr * grad_b
    return w, b


def predict_proba(X: np.ndarray, w: np.ndarray, b: float) -> np.ndarray:
    return sigmoid(X @ w + b)


def accuracy(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    return float(np.mean(y_true == y_pred))


def precision_recall_f1(y_true: np.ndarray, y_pred: np.ndarray) -> tuple[float, float, float]:
    tp = np.sum((y_true == 1) & (y_pred == 1))
    fp = np.sum((y_true == 0) & (y_pred == 1))
    fn = np.sum((y_true == 1) & (y_pred == 0))
    prec = float(tp / (tp + fp)) if (tp + fp) > 0 else 0.0
    rec = float(tp / (tp + fn)) if (tp + fn) > 0 else 0.0
    f1 = float(2 * prec * rec / (prec + rec)) if (prec + rec) > 0 else 0.0
    return prec, rec, f1


def confusion_matrix_binary(y_true: np.ndarray, y_pred: np.ndarray) -> list[list[int]]:
    tn = int(np.sum((y_true == 0) & (y_pred == 0)))
    fp = int(np.sum((y_true == 0) & (y_pred == 1)))
    fn = int(np.sum((y_true == 1) & (y_pred == 0)))
    tp = int(np.sum((y_true == 1) & (y_pred == 1)))
    return [[tn, fp], [fn, tp]]


def load_dataset(csv_path: Path) -> tuple[np.ndarray, np.ndarray]:
    rows = []
    with csv_path.open(encoding="utf-8") as f:
        reader = csv.DictReader(f)
        for row in reader:
            pc = min(float(row["previous_collabs"]), PREVIOUS_COLABS_MAX) / PREVIOUS_COLABS_MAX
            rows.append(
                {
                    "category_match": float(row["category_match"]),
                    "budget_fit": float(row["budget_fit"]),
                    "deadline_fit": float(row["deadline_fit"]),
                    "has_portfolio": float(row["has_portfolio"]),
                    "creator_accept_rate": float(row["creator_accept_rate"]),
                    "previous_collabs_scaled": pc,
                    "rating_score": float(row["rating_score"]),
                    "response_quality_score": float(row["response_quality_score"]),
                    "text_similarity_score": float(row["text_similarity_score"]),
                    "result": int(row["result"]),
                }
            )
    X = np.array([[r[c] for c in FEATURE_COLUMNS] for r in rows], dtype=np.float64)
    y = np.array([r["result"] for r in rows], dtype=np.float64)
    return X, y


def stratified_split(
    X: np.ndarray, y: np.ndarray, test_fraction: float = 0.2, seed: int = 42
) -> tuple[np.ndarray, np.ndarray, np.ndarray, np.ndarray]:
    rng = np.random.default_rng(seed)
    n = len(y)
    mask_test = np.zeros(n, dtype=bool)
    for c in (0.0, 1.0):
        ic = np.where(y == c)[0]
        rng.shuffle(ic)
        n_test = max(1, int(len(ic) * test_fraction))
        mask_test[ic[:n_test]] = True
    train_idx = np.where(~mask_test)[0]
    test_idx = np.where(mask_test)[0]
    rng.shuffle(train_idx)
    rng.shuffle(test_idx)
    return X[train_idx], X[test_idx], y[train_idx], y[test_idx]


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    csv_path = root / "dataset" / "creator_match_seed.csv"
    if not csv_path.is_file():
        raise SystemExit(f"Missing dataset: {csv_path}. Run scripts/build_seed_dataset.py first.")

    X, y = load_dataset(csv_path)
    X_train, X_test, y_train, y_test = stratified_split(X, y, test_fraction=0.2, seed=42)

    w, b = train_logistic(X_train, y_train, lr=0.65, epochs=4500, seed=42)

    proba_test = predict_proba(X_test, w, b)
    y_hat = (proba_test >= 0.5).astype(np.float64)

    acc = accuracy(y_test, y_hat)
    prec, rec, f1 = precision_recall_f1(y_test, y_hat)
    cm = confusion_matrix_binary(y_test, y_hat)

    models_dir = root / "models"
    models_dir.mkdir(parents=True, exist_ok=True)

    weights_dict = {name: float(w[i]) for i, name in enumerate(FEATURE_COLUMNS)}

    trained_at = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    model_payload: dict[str, Any] = {
        "modelName": "creator_offer_match_logistic_regression",
        "version": "1.0",
        "trainedAt": trained_at,
        "features": FEATURE_COLUMNS,
        "intercept": float(b),
        "weights": weights_dict,
        "threshold": 0.5,
        "scoreScale": "0_to_100",
        "notes": "Initial demo model trained on generated seed data.",
    }

    metrics_payload = {
        "accuracy": round(float(acc), 4),
        "precision": round(float(prec), 4),
        "recall": round(float(rec), 4),
        "f1": round(float(f1), 4),
        "confusion_matrix": cm,
        "trainRows": int(len(X_train)),
        "testRows": int(len(X_test)),
        "trainedAt": trained_at,
        "trainer": "numpy_gradient_descent",
    }

    model_path = models_dir / "creator_match_model.json"
    metrics_path = models_dir / "metrics.json"

    with model_path.open("w", encoding="utf-8") as f:
        json.dump(model_payload, f, indent=2)

    with metrics_path.open("w", encoding="utf-8") as f:
        json.dump(metrics_payload, f, indent=2)

    print("Accuracy:", round(acc, 4))
    print("Precision:", round(prec, 4))
    print("Recall:", round(rec, 4))
    print("F1:", round(f1, 4))
    print("Confusion matrix:", cm)
    print(f"Saved model to {model_path}")
    print(f"Saved metrics to {metrics_path}")


if __name__ == "__main__":
    main()
