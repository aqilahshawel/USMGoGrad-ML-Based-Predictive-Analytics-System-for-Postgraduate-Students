"""Training script for my GoT model bundle.

I use this file to produce two artifacts:
- a pickled bundle (checkpoint gap regressors + GoT classifier)
- a metadata JSON (feature columns, evaluation report, chosen threshold, version)

Why I wrote it this way:
- I evaluate in chronological order (or walk-forward) to reduce leakage.
- I optionally tune the decision threshold on validation to balance precision/recall.
"""

from __future__ import annotations

from argparse import ArgumentParser
from pathlib import Path
import pickle
from typing import Any

import numpy as np
import pandas as pd
from imblearn.over_sampling import RandomOverSampler

from got_model import (
    CHECKPOINT_SEQUENCE,
    CLASSIFIER_FEATURE_COLUMNS,
    GAP_MINIMUMS,
    GAP_RECOMMENDATION_FEATURES,
    GAP_TARGET_COLUMNS,
    PREVIOUS_SEMESTER_COLUMN,
    calculate_got,
    prepare_training_frame,
    save_metadata,
    train_model_bundle,
)


MIN_CLASS_POS = 2
MIN_CLASS_NEG = 2
MIN_RECALL_FOR_THRESHOLD = 0.60


def class_counts_for_frame(frame: pd.DataFrame) -> tuple[int, int]:
    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    if got_frame.empty:
        return 0, 0

    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)
    y_true = got_frame["GOT"].to_numpy(dtype=int)
    positives = int((y_true == 1).sum())
    negatives = int((y_true == 0).sum())
    return positives, negatives


def has_min_class_counts(frame: pd.DataFrame, min_pos: int, min_neg: int) -> bool:
    positives, negatives = class_counts_for_frame(frame)
    return positives >= min_pos and negatives >= min_neg


def balance_got_training_frame(
    frame: pd.DataFrame,
    random_state: int = 42,
    use_random_oversampler: bool = False,
) -> pd.DataFrame:
    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    if got_frame.empty:
        return frame

    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)
    class_counts = got_frame["GOT"].value_counts()
    if len(class_counts) < 2:
        return frame

    if use_random_oversampler:
        features = got_frame.drop(columns=["GOT"]).copy()
        target = got_frame["GOT"].copy()
        sampler = RandomOverSampler(random_state=random_state)
        resampled_x, resampled_y = sampler.fit_resample(features, target)
        balanced_got = resampled_x.copy()
        balanced_got["GOT"] = resampled_y
        balanced_got = balanced_got.drop(columns=["GOT"])
    else:
        target_size = int(class_counts.max())
        balanced_parts = []
        for label, group in got_frame.groupby("GOT"):
            balanced_parts.append(group.sample(n=target_size, replace=True, random_state=random_state))

        balanced_got = pd.concat(balanced_parts, ignore_index=True).drop(columns=["GOT"])
    non_got = frame[frame["viva_sem"].isna()].copy()
    return pd.concat([non_got, balanced_got], ignore_index=True)


def build_parser() -> ArgumentParser:
    parser = ArgumentParser(description="Train the GoT XGBoost model.")
    parser.add_argument("--dataset", default="PhD Student Dataset.csv")
    parser.add_argument("--model-out", default="artifacts/got_xgb_model.pkl")
    parser.add_argument("--meta-out", default="artifacts/got_model_meta.json")
    parser.add_argument("--train-ratio", type=float, default=0.70)
    parser.add_argument("--val-ratio", type=float, default=0.15)
    parser.add_argument("--threshold", type=float, default=0.50)
    parser.add_argument(
        "--eval-mode",
        choices=["walk_forward", "chronological"],
        default="walk_forward",
        help="Evaluation strategy: walk-forward windows (default) or a single chronological split.",
    )
    parser.add_argument(
        "--wf-windows",
        type=int,
        default=4,
        help="Number of walk-forward windows when --eval-mode=walk_forward.",
    )
    parser.add_argument(
        "--wf-min-train-ratio",
        type=float,
        default=0.50,
        help="Minimum training ratio for the first walk-forward window.",
    )
    parser.add_argument(
        "--wf-threshold-min-positive",
        type=int,
        default=5,
        help="Minimum positive validation samples required for a walk-forward window to vote on threshold.",
    )
    parser.add_argument(
        "--wf-threshold-min-negative",
        type=int,
        default=5,
        help="Minimum negative validation samples required for a walk-forward window to vote on threshold.",
    )
    parser.add_argument(
        "--scale-pos-weight",
        type=float,
        default=None,
        help="Override scale_pos_weight for GoT classifier (default uses neg/pos ratio).",
    )
    parser.add_argument(
        "--use-ros",
        action="store_true",
        help="Use RandomOverSampler to balance GoT classes for classifier training.",
    )
    return parser


def split_chronological(
    frame: pd.DataFrame,
    train_ratio: float,
    val_ratio: float,
) -> tuple[pd.DataFrame, pd.DataFrame, pd.DataFrame]:
    # I split by registration date to mimic real deployment:
    # train on earlier cohorts, validate/test on later cohorts.
    ordered = frame.sort_values("Registration Date").reset_index(drop=True)
    total = len(ordered)
    if total < 3:
        raise ValueError("Dataset must have at least 3 rows for train/validation/test split.")

    base_train_size = int(round(total * train_ratio))
    val_size = int(round(total * val_ratio))

    base_train_size = max(1, min(base_train_size, total - 2))
    val_size = max(1, min(val_size, total - base_train_size - 1))
    max_train_size = total - val_size - 1
    max_train_size = max(1, max_train_size)

    candidates = list(range(1, max_train_size + 1))
    candidates.sort(key=lambda value: abs(value - base_train_size))

    for train_size in candidates:
        test_size = total - train_size - val_size
        if test_size < 1:
            continue

        train = ordered.iloc[:train_size].copy()
        val = ordered.iloc[train_size:train_size + val_size].copy()
        test = ordered.iloc[train_size + val_size:].copy()

        if not has_min_class_counts(train, MIN_CLASS_POS, MIN_CLASS_NEG):
            continue
        if not has_min_class_counts(val, MIN_CLASS_POS, MIN_CLASS_NEG):
            continue
        if not has_min_class_counts(test, MIN_CLASS_POS, MIN_CLASS_NEG):
            continue

        return train, val, test

    raise ValueError(
        "Unable to create a chronological split with minimum class counts "
        f"(min_pos={MIN_CLASS_POS}, min_neg={MIN_CLASS_NEG})."
    )


def build_walk_forward_splits(
    frame: pd.DataFrame,
    train_ratio: float,
    val_ratio: float,
    windows: int,
    min_train_ratio: float,
) -> tuple[list[tuple[pd.DataFrame, pd.DataFrame, pd.DataFrame]], dict[str, Any]]:
    # Walk-forward evaluation = expanding training set over time.
    # Each window trains on earlier rows and evaluates on later rows.
    ordered = frame.sort_values("Registration Date").reset_index(drop=True)
    total = len(ordered)
    if total < 5:
        raise ValueError("Dataset must have at least 5 rows for walk-forward evaluation.")

    requested_windows = max(1, int(windows))
    test_ratio = 1.0 - train_ratio - val_ratio

    base_train_size = int(round(total * train_ratio))
    val_size = int(round(total * val_ratio))
    test_size = int(round(total * test_ratio))

    val_size = max(1, min(val_size, total - 2))
    test_size = max(1, min(test_size, total - val_size - 1))

    max_train_size = total - val_size - test_size
    min_train_size = int(round(total * min_train_ratio))
    min_train_size = max(1, min(min_train_size, max_train_size))

    # If the requested baseline training size is larger than allowed, cap safely.
    base_train_size = max(1, min(base_train_size, max_train_size))
    min_train_size = min(min_train_size, base_train_size)

    if max_train_size < 1:
        raise ValueError("Unable to create walk-forward windows with current split ratios.")

    available_positions = max_train_size - min_train_size + 1
    used_windows = min(requested_windows, available_positions)

    if used_windows <= 1:
        train_end_points = [min_train_size]
    else:
        points = np.linspace(min_train_size, max_train_size, num=used_windows)
        train_end_points = sorted({int(round(point)) for point in points})

    splits: list[tuple[pd.DataFrame, pd.DataFrame, pd.DataFrame]] = []
    skipped_windows = 0
    for train_end in train_end_points:
        train_end = max(1, min(train_end, max_train_size))
        val_end = train_end + val_size
        test_end = val_end + test_size

        train = ordered.iloc[:train_end].copy()
        val = ordered.iloc[train_end:val_end].copy()
        test = ordered.iloc[val_end:test_end].copy()

        if train.empty or val.empty or test.empty:
            continue

        if not has_min_class_counts(train, MIN_CLASS_POS, MIN_CLASS_NEG):
            skipped_windows += 1
            continue
        if not has_min_class_counts(val, MIN_CLASS_POS, MIN_CLASS_NEG):
            skipped_windows += 1
            continue
        if not has_min_class_counts(test, MIN_CLASS_POS, MIN_CLASS_NEG):
            skipped_windows += 1
            continue

        splits.append((train, val, test))

    metadata = {
        "strategy": "walk_forward_expanding",
        "windows_requested": requested_windows,
        "windows_used": int(len(splits)),
        "windows_skipped_min_class": int(skipped_windows),
        "window_sizes": {
            "validation": int(val_size),
            "test": int(test_size),
        },
        "train_end_range": {
            "min": int(min_train_size),
            "max": int(max_train_size),
        },
        "min_train_ratio": float(min_train_ratio),
        "min_class_counts": {
            "positive": int(MIN_CLASS_POS),
            "negative": int(MIN_CLASS_NEG),
        },
    }

    return splits, metadata


def positive_class_probability(model: Any, features: pd.DataFrame) -> np.ndarray:
    # I normalize how I read probabilities across model types.
    probabilities = np.asarray(model.predict_proba(features), dtype=float)

    if probabilities.ndim == 1:
        return probabilities

    classes = getattr(model, "classes_", None)
    if classes is None:
        return probabilities[:, -1]

    class_list = [int(value) for value in classes]
    if 1 in class_list:
        return probabilities[:, class_list.index(1)]

    return np.zeros(len(features), dtype=float)


def class_distribution(y_true: np.ndarray) -> dict[str, int | bool]:
    negatives = int((y_true == 0).sum())
    positives = int((y_true == 1).sum())
    return {
        "negative": negatives,
        "positive": positives,
        "has_both_classes": negatives > 0 and positives > 0,
    }


def classification_metrics(
    y_true: np.ndarray,
    y_score: np.ndarray,
    threshold: float,
) -> dict[str, float | int | bool | str]:
    # I compute the usual confusion-matrix metrics at a chosen threshold.
    y_pred = (y_score >= threshold).astype(int)

    tp = int(((y_pred == 1) & (y_true == 1)).sum())
    tn = int(((y_pred == 0) & (y_true == 0)).sum())
    fp = int(((y_pred == 1) & (y_true == 0)).sum())
    fn = int(((y_pred == 0) & (y_true == 1)).sum())

    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = (2.0 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0
    accuracy = (tp + tn) / max(1, len(y_true))
    distribution = class_distribution(y_true)

    note = ""
    if not distribution["has_both_classes"]:
        note = "Single-class evaluation window; compare against multi-window metrics for reliable tuning."

    return {
        "samples": int(len(y_true)),
        "threshold": round(float(threshold), 4),
        "negative": int(distribution["negative"]),
        "positive": int(distribution["positive"]),
        "has_both_classes": bool(distribution["has_both_classes"]),
        "accuracy": round(float(accuracy), 4),
        "precision": round(float(precision), 4),
        "recall": round(float(recall), 4),
        "f1": round(float(f1), 4),
        "tp": tp,
        "tn": tn,
        "fp": fp,
        "fn": fn,
        "note": note,
    }


def threshold_search(
    y_true: np.ndarray,
    y_score: np.ndarray,
    default_threshold: float,
) -> dict[str, Any]:
    # I sweep thresholds and pick the one that maximizes F1,
    # but only if recall meets a minimum floor.
    distribution = class_distribution(y_true)
    if len(y_true) == 0:
        return {
            "strategy": "fallback_default",
            "selected": round(float(default_threshold), 4),
            "default": round(float(default_threshold), 4),
            "reason": "No validation samples available.",
            "grid": [],
        }

    if not distribution["has_both_classes"]:
        return {
            "strategy": "fallback_default",
            "selected": round(float(default_threshold), 4),
            "default": round(float(default_threshold), 4),
            "reason": "Validation has only one class; threshold tuning skipped.",
            "grid": [],
        }

    candidate_thresholds = np.round(np.arange(0.10, 0.91, 0.05), 2)
    grid: list[dict[str, Any]] = []

    best_threshold = float(default_threshold)
    best_f1 = -1.0
    best_recall = -1.0
    best_precision = -1.0
    recall_floor = float(MIN_RECALL_FOR_THRESHOLD)
    floor_met = False

    for threshold in candidate_thresholds:
        metrics = classification_metrics(y_true, y_score, float(threshold))
        f1 = float(metrics["f1"])
        recall = float(metrics["recall"])
        precision = float(metrics["precision"])
        grid.append(
            {
                "threshold": round(float(threshold), 4),
                "f1": round(f1, 4),
                "recall": round(recall, 4),
                "precision": round(precision, 4),
            }
        )

        if recall < recall_floor:
            continue

        floor_met = True

        better = False
        if f1 > best_f1:
            better = True
        elif f1 == best_f1 and recall > best_recall:
            better = True
        elif f1 == best_f1 and recall == best_recall and precision > best_precision:
            better = True

        if better:
            best_f1 = f1
            best_recall = recall
            best_precision = precision
            best_threshold = float(threshold)

    return {
        "strategy": "maximize_f1_with_recall_floor",
        "selected": round(best_threshold, 4),
        "default": round(float(default_threshold), 4),
        "reason": (
            f"Selected from validation threshold sweep with recall >= {recall_floor}."
            if floor_met
            else "No thresholds met recall floor; used fallback maximize_f1_then_recall."
        ),
        "grid": grid,
        "recall_floor": round(recall_floor, 4),
    }


def aggregate_classification(metrics_list: list[dict[str, Any]], split_name: str) -> dict[str, Any]:
    if not metrics_list:
        return {
            "samples": 0,
            "threshold": "window_specific",
            "negative": 0,
            "positive": 0,
            "has_both_classes": False,
            "accuracy": 0.0,
            "precision": 0.0,
            "recall": 0.0,
            "f1": 0.0,
            "tp": 0,
            "tn": 0,
            "fp": 0,
            "fn": 0,
            "note": f"No {split_name} windows available for evaluation.",
        }

    samples = int(sum(int(metric.get("samples", 0)) for metric in metrics_list))
    negative = int(sum(int(metric.get("negative", 0)) for metric in metrics_list))
    positive = int(sum(int(metric.get("positive", 0)) for metric in metrics_list))

    tp = int(sum(int(metric.get("tp", 0)) for metric in metrics_list))
    tn = int(sum(int(metric.get("tn", 0)) for metric in metrics_list))
    fp = int(sum(int(metric.get("fp", 0)) for metric in metrics_list))
    fn = int(sum(int(metric.get("fn", 0)) for metric in metrics_list))

    #Calculation
    accuracy = (tp + tn) / max(1, samples)
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = (2.0 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0

    single_class_windows = sum(
        1 for metric in metrics_list if not bool(metric.get("has_both_classes", False))
    )
    note = ""
    if single_class_windows > 0:
        note = (
            f"{single_class_windows}/{len(metrics_list)} {split_name} windows were single-class; "
            "treat threshold-sensitive metrics with care."
        )

    return {
        "samples": samples,
        "threshold": "window_specific",
        "negative": negative,
        "positive": positive,
        "has_both_classes": positive > 0 and negative > 0,
        "accuracy": round(float(accuracy), 4),
        "precision": round(float(precision), 4),
        "recall": round(float(recall), 4),
        "f1": round(float(f1), 4),
        "tp": tp,
        "tn": tn,
        "fp": fp,
        "fn": fn,
        "note": note,
    }


def classification_report_from_metrics(metrics: dict[str, Any]) -> dict[str, Any]:
    tn = int(metrics.get("tn", 0))
    fp = int(metrics.get("fp", 0))
    fn = int(metrics.get("fn", 0))
    tp = int(metrics.get("tp", 0))
    negatives = int(metrics.get("negative", 0))
    positives = int(metrics.get("positive", 0))

    precision_neg = tn / (tn + fn) if (tn + fn) > 0 else 0.0
    recall_neg = tn / (tn + fp) if (tn + fp) > 0 else 0.0
    f1_neg = (2.0 * precision_neg * recall_neg / (precision_neg + recall_neg)) if (precision_neg + recall_neg) > 0 else 0.0

    precision_pos = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall_pos = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1_pos = (2.0 * precision_pos * recall_pos / (precision_pos + recall_pos)) if (precision_pos + recall_pos) > 0 else 0.0

    samples = max(1, negatives + positives)
    accuracy = (tp + tn) / samples

    macro_precision = (precision_neg + precision_pos) / 2.0
    macro_recall = (recall_neg + recall_pos) / 2.0
    macro_f1 = (f1_neg + f1_pos) / 2.0

    weighted_precision = ((precision_neg * negatives) + (precision_pos * positives)) / samples
    weighted_recall = ((recall_neg * negatives) + (recall_pos * positives)) / samples
    weighted_f1 = ((f1_neg * negatives) + (f1_pos * positives)) / samples

    return {
        "confusion_matrix": [[tn, fp], [fn, tp]],
        "rows": [
            {"label": "0", "precision": precision_neg, "recall": recall_neg, "f1": f1_neg, "support": negatives},
            {"label": "1", "precision": precision_pos, "recall": recall_pos, "f1": f1_pos, "support": positives},
        ],
        "accuracy": accuracy,
        "macro_avg": {"precision": macro_precision, "recall": macro_recall, "f1": macro_f1, "support": samples},
        "weighted_avg": {"precision": weighted_precision, "recall": weighted_recall, "f1": weighted_f1, "support": samples},
    }


def aggregate_distributions(distributions: list[dict[str, Any]]) -> dict[str, Any]:
    samples = int(sum(int(item.get("samples", 0)) for item in distributions))
    negative = int(sum(int(item.get("negative", 0)) for item in distributions))
    positive = int(sum(int(item.get("positive", 0)) for item in distributions))
    return {
        "samples": samples,
        "negative": negative,
        "positive": positive,
        "has_both_classes": positive > 0 and negative > 0,
    }


def aggregate_checkpoint_mae(
    metric_sets: list[dict[str, dict[str, float | int]]],
) -> dict[str, dict[str, float | int]]:
    aggregate: dict[str, dict[str, float]] = {}

    for metric_set in metric_sets:
        for checkpoint_key, values in metric_set.items():
            samples = int(values.get("samples", 0))
            mae = float(values.get("mae", 0.0))
            if checkpoint_key not in aggregate:
                aggregate[checkpoint_key] = {
                    "samples": 0.0,
                    "weighted_mae": 0.0,
                }

            aggregate[checkpoint_key]["samples"] += float(samples)
            aggregate[checkpoint_key]["weighted_mae"] += float(samples) * mae

    result: dict[str, dict[str, float | int]] = {}
    for checkpoint_key, totals in aggregate.items():
        samples = int(round(totals["samples"]))
        if samples <= 0:
            result[checkpoint_key] = {"samples": 0, "mae": 0.0}
        else:
            result[checkpoint_key] = {
                "samples": samples,
                "mae": round(float(totals["weighted_mae"] / samples), 4),
            }

    # Ensure all checkpoints are present in a stable order.
    for checkpoint_key, _, _ in CHECKPOINT_SEQUENCE:
        if checkpoint_key not in result:
            result[checkpoint_key] = {"samples": 0, "mae": 0.0}

    return result


def aggregate_threshold_selection(
    threshold_windows: list[dict[str, Any]],
    default_threshold: float,
    min_positive: int,
    min_negative: int,
) -> dict[str, Any]:
    # In walk-forward mode, each window can produce its own tuned threshold.
    # Here I decide whether a window gets to "vote" (enough positives/negatives),
    # then I take the median of eligible window thresholds.
    tuned_values: list[float] = []
    reviewed_windows: list[dict[str, Any]] = []

    for index, window_payload in enumerate(threshold_windows, start=1):
        threshold_info = window_payload.get("threshold", {})
        if not isinstance(threshold_info, dict):
            threshold_info = {}

        validation_distribution = window_payload.get("validation_distribution", {})
        if not isinstance(validation_distribution, dict):
            validation_distribution = {}

        positives = int(validation_distribution.get("positive", 0))
        negatives = int(validation_distribution.get("negative", 0))
        strategy = str(threshold_info.get("strategy", ""))
        reasons: list[str] = []

        selected_value: float | None = None
        try:
            selected_value = float(threshold_info.get("selected", default_threshold))
        except (TypeError, ValueError):
            reasons.append("invalid_selected_threshold")

        if strategy != "maximize_f1_then_recall":
            reasons.append("strategy_not_tuned")
        if positives < min_positive:
            reasons.append(f"positive_below_min_{min_positive}")
        if negatives < min_negative:
            reasons.append(f"negative_below_min_{min_negative}")

        eligible = selected_value is not None and len(reasons) == 0
        if eligible and selected_value is not None:
            tuned_values.append(float(selected_value))

        reviewed_windows.append(
            {
                "window": int(window_payload.get("window", index)),
                "eligible": bool(eligible),
                "reasons": reasons,
                "selected_for_vote": round(float(selected_value), 4) if selected_value is not None else None,
                "threshold": threshold_info,
                "validation_distribution": validation_distribution,
            }
        )

    if tuned_values:
        selected = float(np.median(np.asarray(tuned_values, dtype=float)))
        reason = (
            f"Median selected from {len(tuned_values)}/{len(threshold_windows)} "
            "eligible walk-forward windows."
        )
        strategy = "window_median_tuned_eligible"
    else:
        selected = float(default_threshold)
        reason = (
            "No eligible walk-forward windows met threshold voting criteria "
            f"(min positive={min_positive}, min negative={min_negative}); using default threshold."
        )
        strategy = "fallback_default_eligible_filter"

    return {
        "strategy": strategy,
        "selected": round(float(selected), 4),
        "default": round(float(default_threshold), 4),
        "reason": reason,
        "eligibility": {
            "min_positive": int(min_positive),
            "min_negative": int(min_negative),
            "total_windows": int(len(threshold_windows)),
            "eligible_windows": int(len(tuned_values)),
            "ineligible_windows": int(len(threshold_windows) - len(tuned_values)),
        },
        "windows": reviewed_windows,
    }


def with_gap_targets(frame: pd.DataFrame) -> pd.DataFrame:
    # I rebuild gap targets (for evaluation) the same way as in `got_model.train_model_bundle`.
    result = frame.copy()

    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        previous_column = PREVIOUS_SEMESTER_COLUMN[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]

        if previous_column is None:
            gap_values = result[semester_column] - 1.0
        else:
            gap_values = result[semester_column] - result[previous_column]

        result[gap_column] = gap_values.where(gap_values.notna()).clip(
            lower=float(GAP_MINIMUMS[semester_column])
        )

    return result


def regression_mae(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    if len(y_true) == 0:
        return 0.0
    return float(np.mean(np.abs(y_true - y_pred)))


def checkpoint_gap_metrics(bundle: dict[str, Any], frame: pd.DataFrame) -> dict[str, dict[str, float | int]]:
    # This evaluates how good my gap regressors are (MAE per checkpoint).
    frame_with_gaps = with_gap_targets(frame)
    checkpoint_models = bundle["checkpoint_models"]
    results: dict[str, dict[str, float | int]] = {}

    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        features = GAP_RECOMMENDATION_FEATURES[semester_column]
        target_column = GAP_TARGET_COLUMNS[semester_column]
        eval_frame = frame_with_gaps[features + [target_column]].dropna(subset=features + [target_column])

        if eval_frame.empty:
            results[semester_column] = {"samples": 0, "mae": 0.0}
            continue

        x = eval_frame[features]
        y_true = eval_frame[target_column].to_numpy(dtype=float)
        y_pred = np.asarray(checkpoint_models[semester_column].predict(x), dtype=float)

        results[semester_column] = {
            "samples": int(len(eval_frame)),
            "mae": round(regression_mae(y_true, y_pred), 4),
        }

    return results


def evaluate_classifier(
    bundle: dict[str, Any],
    frame: pd.DataFrame,
    threshold: float,
) -> dict[str, float | int | bool | str]:
    # This evaluates my GoT classifier on rows that have viva_sem (i.e., a label exists).
    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    if got_frame.empty:
        return {
            "samples": 0,
            "threshold": round(float(threshold), 4),
            "negative": 0,
            "positive": 0,
            "has_both_classes": False,
            "accuracy": 0.0,
            "precision": 0.0,
            "recall": 0.0,
            "f1": 0.0,
            "tp": 0,
            "tn": 0,
            "fp": 0,
            "fn": 0,
            "note": "No evaluable rows with viva semester.",
        }

    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)
    x = got_frame[CLASSIFIER_FEATURE_COLUMNS]
    y_true = got_frame["GOT"].to_numpy(dtype=int)
    y_score = positive_class_probability(bundle["got_model"], x)
    return classification_metrics(y_true, y_score, threshold)


def evaluation_class_distribution(frame: pd.DataFrame) -> dict[str, int | bool]:
    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    if got_frame.empty:
        return {
            "samples": 0,
            "negative": 0,
            "positive": 0,
            "has_both_classes": False,
        }

    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)
    y_true = got_frame["GOT"].to_numpy(dtype=int)
    dist = class_distribution(y_true)
    return {
        "samples": int(len(y_true)),
        "negative": int(dist["negative"]),
        "positive": int(dist["positive"]),
        "has_both_classes": bool(dist["has_both_classes"]),
    }


def calibrate_threshold(bundle: dict[str, Any], val_frame: pd.DataFrame, default_threshold: float) -> dict[str, Any]:
    # Threshold tuning happens on validation only.
    got_frame = val_frame.dropna(subset=["viva_sem"]).copy()
    if got_frame.empty:
        return threshold_search(np.array([], dtype=int), np.array([], dtype=float), default_threshold)

    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)
    x = got_frame[CLASSIFIER_FEATURE_COLUMNS]
    y_true = got_frame["GOT"].to_numpy(dtype=int)
    y_score = positive_class_probability(bundle["got_model"], x)
    return threshold_search(y_true, y_score, default_threshold)


def main() -> None:
    args = build_parser().parse_args()
    dataset_path = Path(args.dataset)
    model_path = Path(args.model_out)
    meta_path = Path(args.meta_out)

    if not (0.0 < args.train_ratio < 1.0):
        raise ValueError("--train-ratio must be between 0 and 1.")
    if not (0.0 < args.val_ratio < 1.0):
        raise ValueError("--val-ratio must be between 0 and 1.")
    if args.train_ratio + args.val_ratio >= 1.0:
        raise ValueError("--train-ratio + --val-ratio must be less than 1.")
    if not (0.0 <= args.threshold <= 1.0):
        raise ValueError("--threshold must be between 0 and 1.")
    if args.eval_mode == "walk_forward" and not (0.0 < args.wf_min_train_ratio < 1.0):
        raise ValueError("--wf-min-train-ratio must be between 0 and 1 when walk-forward mode is used.")
    if args.eval_mode == "walk_forward" and args.wf_threshold_min_positive < 0:
        raise ValueError("--wf-threshold-min-positive must be >= 0 when walk-forward mode is used.")
    if args.eval_mode == "walk_forward" and args.wf_threshold_min_negative < 0:
        raise ValueError("--wf-threshold-min-negative must be >= 0 when walk-forward mode is used.")

    # Load CSV and build model-ready numeric features.
    raw_frame = pd.read_csv(dataset_path)
    frame = prepare_training_frame(raw_frame)
    if args.eval_mode == "chronological":
        train_frame, val_frame, test_frame = split_chronological(frame, args.train_ratio, args.val_ratio)

        # For evaluation I train only on the training split (balanced per window).
        balanced_train = balance_got_training_frame(train_frame, use_random_oversampler=args.use_ros)
        eval_bundle, _ = train_model_bundle(balanced_train, scale_pos_weight_override=args.scale_pos_weight)
        threshold_tuning = calibrate_threshold(eval_bundle, val_frame, args.threshold)
        selected_threshold = float(threshold_tuning["selected"])

        eval_report = {
            "split": {
                "strategy": "chronological_by_registration_date",
                "mode": "chronological",
                "train_ratio": args.train_ratio,
                "val_ratio": args.val_ratio,
                "test_ratio": round(1.0 - args.train_ratio - args.val_ratio, 4),
                "rows": {
                    "train": int(len(train_frame)),
                    "validation": int(len(val_frame)),
                    "test": int(len(test_frame)),
                },
            },
            "threshold": threshold_tuning,
            "class_distribution": {
                "train": evaluation_class_distribution(train_frame),
                "validation": evaluation_class_distribution(val_frame),
                "test": evaluation_class_distribution(test_frame),
            },
            "classification": {
                "validation": evaluate_classifier(eval_bundle, val_frame, selected_threshold),
                "test": evaluate_classifier(eval_bundle, test_frame, selected_threshold),
            },
            "checkpoint_gap_mae": {
                "validation": checkpoint_gap_metrics(eval_bundle, val_frame),
                "test": checkpoint_gap_metrics(eval_bundle, test_frame),
            },
        }
    else:
        splits, split_meta = build_walk_forward_splits(
            frame,
            args.train_ratio,
            args.val_ratio,
            args.wf_windows,
            args.wf_min_train_ratio,
        )

        if not splits:
            raise ValueError("No walk-forward windows could be constructed with current settings.")

        threshold_windows: list[dict[str, Any]] = []
        train_distributions: list[dict[str, Any]] = []
        val_distributions: list[dict[str, Any]] = []
        test_distributions: list[dict[str, Any]] = []
        val_classifications: list[dict[str, Any]] = []
        test_classifications: list[dict[str, Any]] = []
        val_gap_sets: list[dict[str, dict[str, float | int]]] = []
        test_gap_sets: list[dict[str, dict[str, float | int]]] = []
        windows_report: list[dict[str, Any]] = []

        for index, (train_window, val_window, test_window) in enumerate(splits, start=1):
            # Each window simulates a point-in-time model trained on history.
            balanced_train = balance_got_training_frame(train_window, use_random_oversampler=args.use_ros)
            eval_bundle, _ = train_model_bundle(balanced_train, scale_pos_weight_override=args.scale_pos_weight)
            window_threshold = calibrate_threshold(eval_bundle, val_window, args.threshold)
            window_selected_threshold = float(window_threshold["selected"])

            train_dist = evaluation_class_distribution(train_window)
            val_dist = evaluation_class_distribution(val_window)
            test_dist = evaluation_class_distribution(test_window)

            val_cls = evaluate_classifier(eval_bundle, val_window, window_selected_threshold)
            test_cls = evaluate_classifier(eval_bundle, test_window, window_selected_threshold)

            val_gap = checkpoint_gap_metrics(eval_bundle, val_window)
            test_gap = checkpoint_gap_metrics(eval_bundle, test_window)

            threshold_windows.append(
                {
                    "window": index,
                    "threshold": window_threshold,
                    "validation_distribution": val_dist,
                }
            )
            train_distributions.append(train_dist)
            val_distributions.append(val_dist)
            test_distributions.append(test_dist)
            val_classifications.append(val_cls)
            test_classifications.append(test_cls)
            val_gap_sets.append(val_gap)
            test_gap_sets.append(test_gap)

            windows_report.append(
                {
                    "window": index,
                    "rows": {
                        "train": int(len(train_window)),
                        "validation": int(len(val_window)),
                        "test": int(len(test_window)),
                    },
                    "threshold": window_threshold,
                    "class_distribution": {
                        "train": train_dist,
                        "validation": val_dist,
                        "test": test_dist,
                    },
                    "classification": {
                        "validation": val_cls,
                        "test": test_cls,
                    },
                    "checkpoint_gap_mae": {
                        "validation": val_gap,
                        "test": test_gap,
                    },
                }
            )

        threshold_tuning = aggregate_threshold_selection(
            threshold_windows,
            args.threshold,
            args.wf_threshold_min_positive,
            args.wf_threshold_min_negative,
        )
        selected_threshold = float(threshold_tuning["selected"])

        eval_report = {
            "split": {
                **split_meta,
                "mode": "walk_forward",
                "train_ratio": args.train_ratio,
                "val_ratio": args.val_ratio,
                "test_ratio": round(1.0 - args.train_ratio - args.val_ratio, 4),
            },
            "threshold": threshold_tuning,
            "class_distribution": {
                "train": aggregate_distributions(train_distributions),
                "validation": aggregate_distributions(val_distributions),
                "test": aggregate_distributions(test_distributions),
            },
            "classification": {
                "validation": aggregate_classification(val_classifications, "validation"),
                "test": aggregate_classification(test_classifications, "test"),
            },
            "checkpoint_gap_mae": {
                "validation": aggregate_checkpoint_mae(val_gap_sets),
                "test": aggregate_checkpoint_mae(test_gap_sets),
            },
            "windows": windows_report,
        }

    # Final production artifact is trained on the full historical dataset (balanced).
    # (I still keep the evaluation report from the chronological/walk-forward run.)
    balanced_full = balance_got_training_frame(frame, use_random_oversampler=args.use_ros)
    bundle, training_report = train_model_bundle(balanced_full, scale_pos_weight_override=args.scale_pos_weight)
    training_report["evaluation"] = eval_report

    print("Training rows:", training_report["total_rows"])
    print("Study mode:", training_report.get("mode", "full_time"))
    print("Evaluation mode:", args.eval_mode)
    if args.eval_mode == "walk_forward":
        split_meta = eval_report.get("split", {})
        print(
            "Walk-forward windows:",
            split_meta.get("windows_used", 0),
            "used /",
            split_meta.get("windows_requested", 0),
            "requested",
        )
    print(
        "Selected decision threshold:",
        round(selected_threshold, 4),
        f"({threshold_tuning['strategy']}: {threshold_tuning['reason']})",
    )
    for model_name, row_count in training_report["row_counts"].items():
        print(f"  - {model_name}: {row_count} rows")
    print("\nValidation classification:", eval_report["classification"]["validation"])
    print("Test classification:", eval_report["classification"]["test"])

    test_report = classification_report_from_metrics(eval_report["classification"]["test"])
    cm = test_report["confusion_matrix"]
    print("\nTest confusion matrix:")
    print(f"[[{cm[0][0]} {cm[0][1]}]\n [{cm[1][0]} {cm[1][1]}]]")
    print("Test classification report:")
    print("precision  recall  f1-score  support")
    for row in test_report["rows"]:
        print(
            f"{row['label']:>5}"
            f"     {row['precision']:.2f}"
            f"   {row['recall']:.2f}"
            f"     {row['f1']:.2f}"
            f"     {row['support']:>6}"
        )
    print(f"\naccuracy  {test_report['accuracy']:.2f}     {test_report['macro_avg']['support']:>6}")
    print(
        f"macro avg {test_report['macro_avg']['precision']:.2f}"
        f"   {test_report['macro_avg']['recall']:.2f}"
        f"     {test_report['macro_avg']['f1']:.2f}"
        f"     {test_report['macro_avg']['support']:>6}"
    )
    print(
        f"weighted avg {test_report['weighted_avg']['precision']:.2f}"
        f" {test_report['weighted_avg']['recall']:.2f}"
        f"     {test_report['weighted_avg']['f1']:.2f}"
        f"     {test_report['weighted_avg']['support']:>6}"
    )

    print("\nValidation checkpoint MAE:")
    for key, value in eval_report["checkpoint_gap_mae"]["validation"].items():
        print(f"  - {key}: {value}")

    print("Test checkpoint MAE:")
    for key, value in eval_report["checkpoint_gap_mae"]["test"].items():
        print(f"  - {key}: {value}")

    model_path.parent.mkdir(parents=True, exist_ok=True)
    with model_path.open("wb") as handle:
        pickle.dump(bundle, handle)
    metadata = save_metadata(meta_path, training_report=training_report)
    print(f"\nSaved model to {model_path}")
    print(f"Saved metadata to {meta_path}")
    print(f"Model version: {metadata['model_version']}")


if __name__ == "__main__":
    main()