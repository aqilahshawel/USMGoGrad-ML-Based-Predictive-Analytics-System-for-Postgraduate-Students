"""GoT inference entrypoint.

I call this script from Laravel (via a subprocess) to predict a single student's
GoT probability.

How I use it:
- Laravel passes `--model` and `--meta` (paths to the trained bundle + metadata)
- Laravel writes a JSON payload to stdin (one student)
- This script prints a JSON result to stdout

Note: I intentionally keep this file small. The real ML logic lives in
`got_model.py`.
"""

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
    """Pick the decision threshold (probability cutoff) from metadata.

    I default to 0.5. During training, I may run a threshold sweep on validation
    and store the chosen threshold inside the metadata JSON.
    """
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

    # Load the trained model bundle + metadata report (version, thresholds, features, etc.).
    bundle, metadata = load_model_bundle(Path(args.model), Path(args.meta))
    decision_threshold = resolve_decision_threshold(metadata)

    # Laravel (or CLI) writes the payload JSON to stdin.
    payload = json.loads(sys.stdin.read())

    # The prediction function returns both: (1) probability and (2) recommended checkpoints.
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

    # I keep the output JSON stable so the Laravel side can reliably parse it.
    sys.stdout.write(json.dumps(result))


if __name__ == "__main__":
    main()