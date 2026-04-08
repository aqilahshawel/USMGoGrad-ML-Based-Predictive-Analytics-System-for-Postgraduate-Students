from __future__ import annotations

from argparse import ArgumentParser
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd

from rf_model import (
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
    dump_model_bundle,
)


def build_parser() -> ArgumentParser:
    parser = ArgumentParser(description="Train RandomForest (GoT) + RandomForest (gap) models.")
    parser.add_argument("--dataset", default="../ml/PhD Student Dataset.csv", help="Path to CSV dataset")
    parser.add_argument("--model-out", default="artifacts/got_rf_model.pkl")
    parser.add_argument("--meta-out", default="artifacts/got_rf_meta.json")

    parser.add_argument(
        "--eval-mode",
        choices=["walk_forward", "chronological"],
        default="walk_forward",
        help="Evaluation splitting strategy.",
    )
    parser.add_argument("--train-ratio", type=float, default=0.70)
    parser.add_argument("--val-ratio", type=float, default=0.15)
    parser.add_argument("--threshold", type=float, default=0.50)

    parser.add_argument("--wf-windows", type=int, default=4)
    parser.add_argument("--wf-min-train-ratio", type=float, default=0.50)
    parser.add_argument("--wf-threshold-min-positive", type=int, default=5)
    parser.add_argument("--wf-threshold-min-negative", type=int, default=5)

    parser.add_argument("--rf-estimators", type=int, default=400)
    parser.add_argument("--rf-max-depth", type=int, default=0, help="0 means None")

    return parser


def split_chronological(frame: pd.DataFrame, train_ratio: float, val_ratio: float) -> tuple[pd.DataFrame, pd.DataFrame, pd.DataFrame]:
    ordered = frame.sort_values("Registration Date").reset_index(drop=True)
    total = len(ordered)
    if total < 3:
        raise ValueError("Need at least 3 rows for train/val/test split")

    train_size = int(round(total * train_ratio))
    val_size = int(round(total * val_ratio))

    train_size = max(1, min(train_size, total - 2))
    val_size = max(1, min(val_size, total - train_size - 1))

    train = ordered.iloc[:train_size].copy()
    val = ordered.iloc[train_size : train_size + val_size].copy()
    test = ordered.iloc[train_size + val_size :].copy()
    return train, val, test


def build_walk_forward_splits(
    ordered: pd.DataFrame,
    *,
    windows: int,
    validation_size: int,
    test_size: int,
    min_train_ratio: float,
) -> list[dict[str, pd.DataFrame]]:
    total = int(len(ordered))
    if total < 3:
        return []

    min_train = int(round(total * float(min_train_ratio)))
    if min_train < 1:
        min_train = 1

    splits: list[dict[str, pd.DataFrame]] = []

    for i in range(int(windows)):
        remaining = total - (validation_size + test_size)
        if remaining <= min_train:
            train_end = remaining
        else:
            if windows <= 1:
                train_end = remaining
            else:
                train_end = min_train + int(round((remaining - min_train) * (i / (windows - 1))))

        train_end = max(min_train, min(train_end, remaining))

        train = ordered.iloc[:train_end].copy()
        val = ordered.iloc[train_end : train_end + validation_size].copy()
        test = ordered.iloc[train_end + validation_size : train_end + validation_size + test_size].copy()

        if len(train) == 0 or len(val) == 0 or len(test) == 0:
            continue

        splits.append({"train": train, "validation": val, "test": test, "train_end": train_end})

    return splits


def class_distribution(y_true: np.ndarray) -> dict[str, int | bool]:
    negative = int((y_true == 0).sum())
    positive = int((y_true == 1).sum())
    return {
        "negative": negative,
        "positive": positive,
        "has_both_classes": bool(negative > 0 and positive > 0),
    }


def classification_metrics(y_true: np.ndarray, y_score: np.ndarray, threshold: float) -> dict[str, Any]:
    if len(y_true) == 0:
        return {"samples": 0}

    y_pred = (y_score >= threshold).astype(int)

    tp = int(((y_pred == 1) & (y_true == 1)).sum())
    tn = int(((y_pred == 0) & (y_true == 0)).sum())
    fp = int(((y_pred == 1) & (y_true == 0)).sum())
    fn = int(((y_pred == 0) & (y_true == 1)).sum())

    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = (2.0 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0
    accuracy = (tp + tn) / max(1, len(y_true))

    dist = class_distribution(y_true)

    return {
        "samples": int(len(y_true)),
        "threshold": round(float(threshold), 4),
        "negative": int(dist["negative"]),
        "positive": int(dist["positive"]),
        "has_both_classes": bool(dist["has_both_classes"]),
        "accuracy": round(float(accuracy), 4),
        "precision": round(float(precision), 4),
        "recall": round(float(recall), 4),
        "f1": round(float(f1), 4),
        "tp": tp,
        "tn": tn,
        "fp": fp,
        "fn": fn,
    }


def regression_mae(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    if len(y_true) == 0:
        return 0.0
    return float(np.mean(np.abs(y_true - y_pred)))


def with_gap_targets(frame: pd.DataFrame) -> pd.DataFrame:
    frame_with_gaps = frame.copy()
    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        previous_column = PREVIOUS_SEMESTER_COLUMN[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]

        if previous_column is None:
            gap_values = frame_with_gaps[semester_column] - 1.0
        else:
            gap_values = frame_with_gaps[semester_column] - frame_with_gaps[previous_column]

        frame_with_gaps[gap_column] = gap_values.where(gap_values.notna()).clip(lower=float(GAP_MINIMUMS[semester_column]))

    return frame_with_gaps


def checkpoint_gap_metrics(bundle: dict[str, Any], frame: pd.DataFrame) -> dict[str, dict[str, float | int]]:
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


def positive_class_probability(model: Any, features: pd.DataFrame) -> np.ndarray:
    probabilities = model.predict_proba(features)
    classes = getattr(model, "classes_", np.array([0, 1], dtype=int))
    class_list = [int(v) for v in classes]
    if 1 in class_list:
        return np.asarray(probabilities[:, class_list.index(1)], dtype=float)
    return np.zeros(len(features), dtype=float)


def threshold_search(y_true: np.ndarray, y_score: np.ndarray, default_threshold: float) -> dict[str, Any]:
    dist = class_distribution(y_true)
    if len(y_true) == 0 or not dist["has_both_classes"]:
        return {
            "strategy": "fallback_default",
            "selected": round(float(default_threshold), 4),
            "default": round(float(default_threshold), 4),
            "reason": "Validation has only one class; threshold tuning skipped.",
            "grid": [],
        }

    candidate_thresholds = np.round(np.arange(0.10, 0.91, 0.05), 2)
    best_threshold = float(default_threshold)
    best_f1 = -1.0
    best_recall = -1.0
    grid: list[dict[str, float]] = []

    for threshold in candidate_thresholds:
        metrics = classification_metrics(y_true, y_score, float(threshold))
        grid.append(
            {
                "threshold": float(threshold),
                "f1": float(metrics.get("f1", 0.0)),
                "recall": float(metrics.get("recall", 0.0)),
                "precision": float(metrics.get("precision", 0.0)),
            }
        )

        f1 = float(metrics.get("f1", 0.0))
        recall = float(metrics.get("recall", 0.0))
        if f1 > best_f1 or (abs(f1 - best_f1) < 1e-12 and recall > best_recall):
            best_f1 = f1
            best_recall = recall
            best_threshold = float(threshold)

    return {
        "strategy": "maximize_f1_then_recall",
        "selected": round(best_threshold, 4),
        "default": round(float(default_threshold), 4),
        "reason": "Selected from validation threshold sweep.",
        "grid": grid,
    }


def evaluate_classifier(bundle: dict[str, Any], frame: pd.DataFrame, threshold: float) -> dict[str, float | int | bool | str]:
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

    raw_frame = pd.read_csv(dataset_path)
    frame = prepare_training_frame(raw_frame)

    rf_max_depth = None if int(args.rf_max_depth) <= 0 else int(args.rf_max_depth)

    if args.eval_mode == "chronological":
        train_frame, val_frame, test_frame = split_chronological(frame, args.train_ratio, args.val_ratio)

        eval_bundle, _ = train_model_bundle(
            train_frame,
            rf_estimators=int(args.rf_estimators),
            rf_max_depth=rf_max_depth,
        )
        threshold_tuning = calibrate_threshold(eval_bundle, val_frame, args.threshold)
        selected_threshold = float(threshold_tuning["selected"])

        evaluation = {
            "split": {
                "strategy": "chronological",
                "train_ratio": float(args.train_ratio),
                "val_ratio": float(args.val_ratio),
                "test_ratio": float(1.0 - float(args.train_ratio) - float(args.val_ratio)),
                "sizes": {
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

        final_bundle, report = train_model_bundle(
            frame,
            rf_estimators=int(args.rf_estimators),
            rf_max_depth=rf_max_depth,
        )
        report["evaluation"] = evaluation

        dump_model_bundle(model_path, final_bundle)
        save_metadata(meta_path, training_report=report)

        print(f"Saved model to: {model_path}")
        print(f"Saved meta to:  {meta_path}")
        print(f"GoT F1 (test)= {evaluation['classification']['test'].get('f1')}")
        return

    ordered = frame.sort_values("Registration Date").reset_index(drop=True)
    total = int(len(ordered))
    windows_requested = int(args.wf_windows)
    val_size = int(round(total * float(args.val_ratio)))
    test_size = int(round(total * float(1.0 - float(args.train_ratio) - float(args.val_ratio))))
    if test_size < 1:
        test_size = 1
    if val_size < 1:
        val_size = 1

    splits = build_walk_forward_splits(
        ordered,
        windows=windows_requested,
        validation_size=val_size,
        test_size=test_size,
        min_train_ratio=float(args.wf_min_train_ratio),
    )

    if not splits:
        raise ValueError("Unable to build walk-forward splits; try fewer windows or different ratios.")

    threshold_windows: list[dict[str, Any]] = []
    selected_thresholds: list[float] = []

    val_all: list[dict[str, Any]] = []
    test_all: list[dict[str, Any]] = []

    for idx, split in enumerate(splits, start=1):
        train_frame = split["train"]
        val_frame = split["validation"]
        test_frame = split["test"]

        eval_bundle, _ = train_model_bundle(
            train_frame,
            rf_estimators=int(args.rf_estimators),
            rf_max_depth=rf_max_depth,
        )

        dist_val = evaluation_class_distribution(val_frame)
        eligible = bool(
            dist_val["has_both_classes"]
            and int(dist_val["positive"]) >= int(args.wf_threshold_min_positive)
            and int(dist_val["negative"]) >= int(args.wf_threshold_min_negative)
        )

        threshold_tuning = calibrate_threshold(eval_bundle, val_frame, args.threshold)
        chosen = float(threshold_tuning["selected"])

        threshold_windows.append(
            {
                "window": int(idx),
                "eligible": bool(eligible),
                "selected_for_vote": float(chosen),
                "threshold": threshold_tuning,
                "validation_distribution": dist_val,
            }
        )

        if eligible:
            selected_thresholds.append(chosen)

        val_metrics = evaluate_classifier(eval_bundle, val_frame, chosen)
        test_metrics = evaluate_classifier(eval_bundle, test_frame, chosen)
        val_all.append(val_metrics)
        test_all.append(test_metrics)

    if selected_thresholds:
        final_threshold = float(np.median(np.asarray(selected_thresholds, dtype=float)))
        threshold_summary = {
            "strategy": "window_median_tuned_eligible",
            "selected": round(final_threshold, 4),
            "default": round(float(args.threshold), 4),
            "reason": f"Median selected from {len(selected_thresholds)}/{len(splits)} eligible walk-forward windows.",
            "eligibility": {
                "min_positive": int(args.wf_threshold_min_positive),
                "min_negative": int(args.wf_threshold_min_negative),
                "total_windows": int(len(splits)),
                "eligible_windows": int(len(selected_thresholds)),
                "ineligible_windows": int(len(splits) - len(selected_thresholds)),
            },
            "windows": threshold_windows,
        }
    else:
        final_threshold = float(args.threshold)
        threshold_summary = {
            "strategy": "fallback_default",
            "selected": round(final_threshold, 4),
            "default": round(float(args.threshold), 4),
            "reason": "No eligible walk-forward windows for threshold tuning.",
            "eligibility": {
                "min_positive": int(args.wf_threshold_min_positive),
                "min_negative": int(args.wf_threshold_min_negative),
                "total_windows": int(len(splits)),
                "eligible_windows": 0,
                "ineligible_windows": int(len(splits)),
            },
            "windows": threshold_windows,
        }

    def aggregate(metrics_list: list[dict[str, Any]]) -> dict[str, Any]:
        tp = int(sum(int(m.get("tp", 0)) for m in metrics_list))
        tn = int(sum(int(m.get("tn", 0)) for m in metrics_list))
        fp = int(sum(int(m.get("fp", 0)) for m in metrics_list))
        fn = int(sum(int(m.get("fn", 0)) for m in metrics_list))
        samples = int(sum(int(m.get("samples", 0)) for m in metrics_list))

        precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
        recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
        f1 = (2.0 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0
        accuracy = (tp + tn) / max(1, samples)

        positive = int(sum(int(m.get("positive", 0)) for m in metrics_list))
        negative = int(sum(int(m.get("negative", 0)) for m in metrics_list))

        return {
            "samples": samples,
            "threshold": "window_specific",
            "negative": negative,
            "positive": positive,
            "has_both_classes": bool(positive > 0 and negative > 0),
            "accuracy": round(float(accuracy), 4),
            "precision": round(float(precision), 4),
            "recall": round(float(recall), 4),
            "f1": round(float(f1), 4),
            "tp": tp,
            "tn": tn,
            "fp": fp,
            "fn": fn,
        }

    def aggregate_gap(metrics_list: list[dict[str, dict[str, float | int]]]) -> dict[str, Any]:
        out: dict[str, Any] = {}
        for semester_column, _, _ in CHECKPOINT_SEQUENCE:
            weighted_abs_error_sum = 0.0
            samples = 0
            for window_metrics in metrics_list:
                entry = window_metrics.get(semester_column, {})
                window_samples = int(entry.get("samples", 0))
                window_mae = float(entry.get("mae", 0.0))
                if window_samples <= 0:
                    continue

                samples += window_samples
                weighted_abs_error_sum += window_mae * float(window_samples)

            out[semester_column] = {
                "samples": int(samples),
                "mae": round(weighted_abs_error_sum / float(samples), 4) if samples > 0 else 0.0,
            }
        return out

    gap_val_all: list[dict[str, dict[str, float | int]]] = []
    gap_test_all: list[dict[str, dict[str, float | int]]] = []

    for idx, split in enumerate(splits, start=1):
        train_frame = split["train"]
        val_frame = split["validation"]
        test_frame = split["test"]

        eval_bundle, _ = train_model_bundle(
            train_frame,
            rf_estimators=int(args.rf_estimators),
            rf_max_depth=rf_max_depth,
        )

        gap_val_all.append(checkpoint_gap_metrics(eval_bundle, val_frame))
        gap_test_all.append(checkpoint_gap_metrics(eval_bundle, test_frame))

    evaluation = {
        "split": {
            "strategy": "walk_forward_expanding",
            "windows_requested": int(windows_requested),
            "windows_used": int(len(splits)),
            "window_sizes": {
                "validation": int(val_size),
                "test": int(test_size),
            },
            "min_train_ratio": float(args.wf_min_train_ratio),
            "mode": "walk_forward",
            "train_ratio": float(args.train_ratio),
            "val_ratio": float(args.val_ratio),
            "test_ratio": float(1.0 - float(args.train_ratio) - float(args.val_ratio)),
        },
        "threshold": threshold_summary,
        "classification": {
            "validation": aggregate(val_all),
            "test": aggregate(test_all),
        },
        "checkpoint_gap_mae": {
            "validation": aggregate_gap(gap_val_all),
            "test": aggregate_gap(gap_test_all),
        },
    }

    final_bundle, report = train_model_bundle(
        frame,
        rf_estimators=int(args.rf_estimators),
        rf_max_depth=rf_max_depth,
    )
    report["evaluation"] = evaluation

    dump_model_bundle(model_path, final_bundle)
    save_metadata(meta_path, training_report=report)

    print(f"Saved model to: {model_path}")
    print(f"Saved meta to:  {meta_path}")
    print(f"GoT F1 (test)= {evaluation['classification']['test'].get('f1')}")


if __name__ == "__main__":
    main()
