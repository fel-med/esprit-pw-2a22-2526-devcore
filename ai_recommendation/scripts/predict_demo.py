"""
Load exported JSON model and print demo predictions with simple explanations.
"""
from __future__ import annotations

import json
import math
from pathlib import Path
from typing import Any


def sigmoid(x: float) -> float:
    if x < -40.0:
        return 0.0
    if x > 40.0:
        return 1.0
    return 1.0 / (1.0 + math.exp(-x))


def probability(features: dict[str, float], model: dict[str, Any]) -> float:
    names: list[str] = model["features"]
    w: dict[str, float] = model["weights"]
    b = float(model["intercept"])
    logit = b
    for name in names:
        logit += float(w[name]) * float(features[name])
    return sigmoid(logit)


def explain(features: dict[str, float]) -> list[str]:
    reasons: list[str] = []
    cm = features.get("category_match", 0)
    if cm >= 0.5:
        reasons.append("Category fits the offer")
    elif cm < 0.5:
        reasons.append("Category overlap with the brief is limited (adjacent or different vertical)")

    bf = features.get("budget_fit", 0)
    if bf >= 0.65:
        reasons.append("Budget is close to the proposed range")
    elif bf >= 0.38:
        reasons.append("Budget is in a workable range for this offer")
    elif bf < 0.35:
        reasons.append("Budget alignment is weak")

    df = features.get("deadline_fit", 0)
    if df >= 0.65:
        reasons.append("Delivery timeline fits the deadline")
    elif df >= 0.38:
        reasons.append("Delivery timing looks feasible vs the deadline")
    elif df < 0.35:
        reasons.append("Deadline risk: delivery profile looks tight")

    if features.get("has_portfolio", 0) >= 0.5:
        reasons.append("Portfolio exists")

    pcs = features.get("previous_collabs_scaled", 0)
    if pcs >= 0.35:
        reasons.append("Good previous collaboration score")
    elif pcs >= 0.12:
        reasons.append("Some collaboration history on the platform")
    elif pcs < 0.1:
        reasons.append("Limited collaboration history on platform")

    rs = features.get("rating_score", 0)
    if rs >= 0.65:
        reasons.append("Strong rating profile")
    elif rs >= 0.5:
        reasons.append("Rating profile is acceptable for this brief")
    elif rs >= 0.35:
        reasons.append("Mixed rating signals - confirm fit before shortlisting")
    else:
        reasons.append("Rating profile is below average for this match")

    if features.get("response_quality_score", 0) >= 0.65:
        reasons.append("Response quality signals look strong")

    if features.get("text_similarity_score", 0) >= 0.55:
        reasons.append("Text/topic similarity to the offer is solid")

    if not reasons:
        reasons.append("Mixed signals - review profile and brief manually")

    return reasons[:6]


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    model_path = root / "models" / "creator_match_model.json"
    if not model_path.is_file():
        raise SystemExit(f"Missing {model_path}. Run scripts/train_model.py first.")

    with model_path.open(encoding="utf-8") as f:
        model = json.load(f)

    demos = [
        {
            "label": "Creator: Hedi Photography",
            "features": {
                "category_match": 1.0,
                "budget_fit": 0.41,
                "deadline_fit": 0.43,
                "has_portfolio": 1.0,
                "creator_accept_rate": 0.39,
                "previous_collabs_scaled": 0.06,
                "rating_score": 0.43,
                "response_quality_score": 0.41,
                "text_similarity_score": 0.39,
            },
        },
        {
            "label": "Creator: Alex Motion Lab",
            "features": {
                "category_match": 0.0,
                "budget_fit": 0.72,
                "deadline_fit": 0.70,
                "has_portfolio": 1.0,
                "creator_accept_rate": 0.55,
                "previous_collabs_scaled": 0.22,
                "rating_score": 0.55,
                "response_quality_score": 0.52,
                "text_similarity_score": 0.50,
            },
        },
        {
            "label": "Creator: Sam Generic Studio",
            "features": {
                "category_match": 0.0,
                "budget_fit": 0.28,
                "deadline_fit": 0.22,
                "has_portfolio": 0.0,
                "creator_accept_rate": 0.35,
                "previous_collabs_scaled": 0.05,
                "rating_score": 0.42,
                "response_quality_score": 0.38,
                "text_similarity_score": 0.25,
            },
        },
    ]

    for demo in demos:
        p = probability(demo["features"], model)
        score_pct = round(p * 100)
        print(demo["label"])
        print(f"Match score: {score_pct}%")
        print("Reasons:")
        for line in explain(demo["features"]):
            print(f"- {line}")
        print()


if __name__ == "__main__":
    main()
