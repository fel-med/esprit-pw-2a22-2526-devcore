"""
Generate a reproducible seed CSV for creator–offer match training (demo data).
Uses only the Python standard library (no pip dependencies required to generate data).
"""
from __future__ import annotations

import csv
import math
import random
from pathlib import Path

RNG_SEED = 42
N_ROWS = 420

CATEGORIES = [
    "photography",
    "design",
    "video",
    "music",
    "writing",
    "motion_graphics",
]

CLOSE_PAIRS = {
    ("photography", "video"),
    ("video", "motion_graphics"),
    ("design", "motion_graphics"),
    ("writing", "music"),
}


def clip(x: float, lo: float, hi: float) -> float:
    return max(lo, min(hi, x))


def categories_match(creator_cat: str, offer_cat: str) -> int:
    if creator_cat == offer_cat:
        return 1
    a, b = (creator_cat, offer_cat), (offer_cat, creator_cat)
    if a in CLOSE_PAIRS or b in CLOSE_PAIRS:
        return 1
    return 0


def compute_budget_fit(offer_budget: float, creator_expected: float) -> float:
    if offer_budget <= 0:
        return 0.0
    lo, hi = min(offer_budget, creator_expected), max(offer_budget, creator_expected)
    if hi <= 0:
        return 0.0
    return clip(lo / hi, 0.0, 1.0)


def compute_deadline_fit(deadline_days: float, creator_avg_delivery: float) -> float:
    if deadline_days <= 0:
        return 0.0
    if creator_avg_delivery <= deadline_days:
        return 1.0
    over = creator_avg_delivery - deadline_days
    return clip(1.0 - over / max(deadline_days, 1.0), 0.0, 1.0)


def weighted_signal(feats: dict) -> float:
    pc = min(feats["previous_collabs"], 20) / 20.0
    return (
        0.22 * feats["category_match"]
        + 0.20 * feats["budget_fit"]
        + 0.18 * feats["deadline_fit"]
        + 0.08 * feats["has_portfolio"]
        + 0.09 * feats["creator_accept_rate"]
        + 0.05 * pc
        + 0.12 * feats["rating_score"]
        + 0.08 * feats["response_quality_score"]
        + 0.06 * feats["text_similarity_score"]
    )


def beta_random(rng: random.Random, a: float, b: float) -> float:
    """Simple Beta-like sample via Gamma ratio approx using uniform transforms (demo only)."""
    # Irwin–Hall / central limit quick approximation for skewed 0..1
    u = sum(rng.random() for _ in range(12)) / 12.0
    return clip(u * (a / (a + b)) + rng.gauss(0, 0.08), 0.0, 1.0)


def poisson_approx(rng: random.Random, lam: float) -> int:
    """Knuth for small lambda."""
    l = math.exp(-lam)
    k = 0
    p = 1.0
    while p > l:
        k += 1
        p *= rng.random()
    return max(0, k - 1)


def main() -> None:
    rng = random.Random(RNG_SEED)

    root = Path(__file__).resolve().parents[1]
    out_dir = root / "dataset"
    out_dir.mkdir(parents=True, exist_ok=True)
    out_path = out_dir / "creator_match_seed.csv"

    rows = []
    for _ in range(N_ROWS):
        creator_cat = rng.choice(CATEGORIES)
        offer_cat = rng.choice(CATEGORIES)
        category_match = categories_match(creator_cat, offer_cat)

        offer_budget = rng.uniform(300, 12000)
        jitter = rng.gauss(0, offer_budget * 0.25)
        creator_expected = max(100.0, offer_budget * (0.7 + 0.6 * rng.random()) + jitter)
        if category_match == 0:
            creator_expected *= rng.uniform(0.4, 2.2)

        budget_fit = compute_budget_fit(offer_budget, creator_expected)

        deadline_days = rng.uniform(3, 45)
        creator_avg_delivery = clip(
            rng.gammavariate(2.0, deadline_days / 6) + rng.uniform(-2, 8),
            1.0,
            120.0,
        )
        if category_match == 1 and rng.random() > 0.35:
            creator_avg_delivery = min(creator_avg_delivery, deadline_days * rng.uniform(0.5, 1.0))
        deadline_fit = compute_deadline_fit(deadline_days, creator_avg_delivery)

        has_portfolio = int(rng.random() < (0.72 if category_match else 0.35))

        creator_accept_rate = beta_random(rng, 2.5, 2.0)
        if category_match == 0:
            creator_accept_rate *= rng.uniform(0.4, 0.95)

        previous_collabs = min(poisson_approx(rng, 4.0), 20)
        if category_match == 0:
            previous_collabs = min(previous_collabs, rng.randint(0, 8))

        rating_score = beta_random(rng, 4.5, 2.0)
        response_quality_score = beta_random(rng, 3.5, 2.2)
        text_similarity_score = beta_random(rng, 3.0, 2.5)

        if category_match == 1:
            text_similarity_score = clip(text_similarity_score + rng.uniform(0.0, 0.25), 0.0, 1.0)

        feats = {
            "category_match": category_match,
            "budget_fit": round(budget_fit, 4),
            "deadline_fit": round(deadline_fit, 4),
            "has_portfolio": has_portfolio,
            "creator_accept_rate": round(creator_accept_rate, 4),
            "previous_collabs": previous_collabs,
            "rating_score": round(rating_score, 4),
            "response_quality_score": round(response_quality_score, 4),
            "text_similarity_score": round(text_similarity_score, 4),
        }

        signal = weighted_signal(feats)
        noise = rng.gauss(0, 0.07)
        threshold = 0.52 + rng.gauss(0, 0.02)
        result = 1 if (signal + noise) > threshold else 0

        if rng.random() < 0.04:
            result = 1 - result

        rows.append(
            {
                "creator_category": creator_cat,
                "offer_category": offer_cat,
                "category_match": feats["category_match"],
                "offer_budget": round(offer_budget, 2),
                "creator_expected_budget": round(creator_expected, 2),
                "budget_fit": feats["budget_fit"],
                "deadline_days": round(deadline_days, 2),
                "creator_avg_delivery_days": round(creator_avg_delivery, 2),
                "deadline_fit": feats["deadline_fit"],
                "has_portfolio": feats["has_portfolio"],
                "creator_accept_rate": feats["creator_accept_rate"],
                "previous_collabs": feats["previous_collabs"],
                "rating_score": feats["rating_score"],
                "response_quality_score": feats["response_quality_score"],
                "text_similarity_score": feats["text_similarity_score"],
                "result": result,
            }
        )

    fieldnames = [
        "creator_category",
        "offer_category",
        "category_match",
        "offer_budget",
        "creator_expected_budget",
        "budget_fit",
        "deadline_days",
        "creator_avg_delivery_days",
        "deadline_fit",
        "has_portfolio",
        "creator_accept_rate",
        "previous_collabs",
        "rating_score",
        "response_quality_score",
        "text_similarity_score",
        "result",
    ]

    with out_path.open("w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=fieldnames)
        w.writeheader()
        w.writerows(rows)

    print(f"Wrote {len(rows)} rows to {out_path}")


if __name__ == "__main__":
    main()
