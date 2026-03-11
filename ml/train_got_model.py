from __future__ import annotations

from argparse import ArgumentParser
from pathlib import Path

import joblib
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import train_test_split
from xgboost import XGBClassifier

from got_model import FEATURE_COLUMNS, calculate_got, prepare_training_frame, save_metadata


def build_parser() -> ArgumentParser:
    parser = ArgumentParser(description="Train the GoT XGBoost model.")
    parser.add_argument("--dataset", default="PhD Student Dataset.csv")
    parser.add_argument("--model-out", default="artifacts/got_xgb_model.joblib")
    parser.add_argument("--meta-out", default="artifacts/got_model_meta.json")
    return parser


def main() -> None:
    args = build_parser().parse_args()
    dataset_path = Path(args.dataset)
    model_path = Path(args.model_out)
    meta_path = Path(args.meta_out)

    frame = prepare_training_frame(__import__("pandas").read_csv(dataset_path))
    frame["GOT"] = frame.apply(calculate_got, axis=1)

    x = frame[FEATURE_COLUMNS]
    y = frame["GOT"]

    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=0.2,
        random_state=42,
        stratify=y if y.nunique() > 1 else None,
    )

    model = XGBClassifier(
        n_estimators=300,
        max_depth=4,
        learning_rate=0.05,
        subsample=0.9,
        colsample_bytree=0.9,
        objective="binary:logistic",
        eval_metric="logloss",
        random_state=42,
    )

    model.fit(x_train, y_train)
    predictions = model.predict(x_test)

    print(f"Accuracy: {accuracy_score(y_test, predictions):.4f}")
    print("\nClassification Report")
    print(classification_report(y_test, predictions, zero_division=0))
    print("\nConfusion Matrix")
    print(confusion_matrix(y_test, predictions))

    model_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(model, model_path)
    metadata = save_metadata(meta_path)
    print(f"\nSaved model to {model_path}")
    print(f"Saved metadata to {meta_path}")
    print(f"Model version: {metadata['model_version']}")


if __name__ == "__main__":
    main()