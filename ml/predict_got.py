from __future__ import annotations

from argparse import ArgumentParser
from pathlib import Path
import json
import sys

from got_model import load_model_bundle, predict_student_payload


def build_parser() -> ArgumentParser:
    parser = ArgumentParser(description="Predict GoT probability for a single student payload.")
    parser.add_argument("--model", required=True)
    parser.add_argument("--meta", required=True)
    return parser


def resolve_decision_threshold(metadata: dict) -> float:
    default_threshold = 0.5
    training_report = metadata.get("training_report", {})
    evaluation = training_report.get("evaluation", {})
    threshold_block = evaluation.get("threshold", default_threshold)

    if isinstance(threshold_block, dict):
        selected = threshold_block.get("selected", threshold_block.get("default", default_threshold))
    else:
        selected = threshold_block

    try:
        value = float(selected)
    except (TypeError, ValueError):
        return default_threshold

    if value < 0.0 or value > 1.0:
        return default_threshold

    return value


def main() -> None:
    args = build_parser().parse_args()
    bundle, metadata = load_model_bundle(Path(args.model), Path(args.meta))
    decision_threshold = resolve_decision_threshold(metadata)

    payload = json.loads(sys.stdin.read())
    prediction = predict_student_payload(payload, bundle, decision_threshold=decision_threshold)

    result = {
        "got_probability": prediction["got_probability"],
        "predicted_got": prediction["predicted_got"],
        "study_mode_model": prediction["study_mode_model"],
        "recommended_checkpoint_dates": prediction["recommended_checkpoint_dates"],
        "recommended_checkpoint_semesters": prediction["recommended_checkpoint_semesters"],
        "recommendation_diagnostics": prediction.get("recommendation_diagnostics", {}),
        "decision_threshold": decision_threshold,
        "model_version": metadata.get("model_version", "unknown"),
        "feature_columns": metadata.get("classifier_feature_columns", []),
    }
    sys.stdout.write(json.dumps(result))


if __name__ == "__main__":
    main()