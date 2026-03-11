from __future__ import annotations

from argparse import ArgumentParser
from pathlib import Path
import json
import sys

from got_model import load_model_bundle, prepare_prediction_frame


def build_parser() -> ArgumentParser:
    parser = ArgumentParser(description="Predict GoT probability for a single student payload.")
    parser.add_argument("--model", required=True)
    parser.add_argument("--meta", required=True)
    return parser


def main() -> None:
    args = build_parser().parse_args()
    model, metadata = load_model_bundle(Path(args.model), Path(args.meta))

    payload = json.loads(sys.stdin.read())
    frame = prepare_prediction_frame(payload)

    probability = float(model.predict_proba(frame)[0][1])
    predicted_got = bool(probability >= 0.5)

    result = {
        "got_probability": probability,
        "predicted_got": predicted_got,
        "model_version": metadata.get("model_version", "unknown"),
        "feature_columns": metadata.get("feature_columns", []),
    }
    sys.stdout.write(json.dumps(result))


if __name__ == "__main__":
    main()