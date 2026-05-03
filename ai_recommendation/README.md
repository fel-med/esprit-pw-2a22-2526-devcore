# Cre8Connect — creator–offer match model (V24.1 demo)

This folder contains a **local-only** training pipeline for a small **logistic regression** model that scores how well a creator profile fits a brand offer. It uses **synthetic seed data** so you can train on any Windows machine without cloud services or paid APIs.

Cre8Pilot will later consume the exported JSON model to recommend creators; **PHP and MySQL are unchanged** in this version.

## What the model does

- **Input:** numeric features derived from category alignment, budget/deadline fit, portfolio flag, behaviour signals, and text similarity (all between 0 and 1 after scaling, except binary flags).
- **Output:** probability of a **good match** (`result` = 1 in training). The demo script shows this as a **0–100%** match score.

## Dataset columns (`dataset/creator_match_seed.csv`)

| Column | Description |
|--------|-------------|
| `creator_category` / `offer_category` | Label categories used to derive `category_match`. |
| `category_match` | 1 if categories match or are “close”, else 0. |
| `offer_budget` / `creator_expected_budget` | Raw budget numbers (seed generator). |
| `budget_fit` | 0–1 closeness of expected vs offer budget. |
| `deadline_days` / `creator_avg_delivery_days` | Raw timing (seed generator). |
| `deadline_fit` | 0–1 fit of delivery capacity vs deadline. |
| `has_portfolio` | 0 or 1. |
| `creator_accept_rate` | 0–1. |
| `previous_collabs` | Integer 0–20 (scaled to `previous_collabs_scaled` in training). |
| `rating_score` | 0–1. |
| `response_quality_score` | 0–1. |
| `text_similarity_score` | 0–1. |
| `result` | 1 = good match, 0 = weak/bad match (label). |

## Install (Windows)

```bat
cd ai_recommendation
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

**Training dependency:** `scripts/train_model.py` only needs **NumPy** (installs quickly with wheels on most setups). `pandas` and `scikit-learn` are listed for ecosystem consistency and future notebooks; if `pip install -r requirements.txt` fails on a very new Python build, install at least `numpy` and run the scripts above.

**Recommended:** Python **3.10–3.12 (64-bit)** for the smoothest `pip` experience on Windows.

## Generate seed data

```bat
python scripts\build_seed_dataset.py
```

Writes `dataset\creator_match_seed.csv` (~420 rows). Uses **stdlib only** (no pip packages required).

## Train

```bat
python scripts\train_model.py
```

Prints accuracy, precision, recall, F1, and confusion matrix. Saves:

- `models\creator_match_model.json` — intercept, feature weights, metadata for inference.
- `models\metrics.json` — test metrics.

Uses **NumPy** logistic regression (gradient descent), not scikit-learn, so training stays lightweight and portable.

## Demo prediction

```bat
python scripts\predict_demo.py
```

Loads `models\creator_match_model.json` and prints three sample creators (strong / medium / weak) with rule-based “reasons” from feature values.

## Notes

- This is an **initial demo** trained on **generated** data. Quality will improve when you **retrain on real** accepted/refused candidatures and negotiation outcomes from MySQL.
- Dependencies are limited to **pandas**, **numpy**, and **scikit-learn** (no heavy deep learning stack).
