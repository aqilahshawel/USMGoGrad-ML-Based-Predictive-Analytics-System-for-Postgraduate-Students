from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
import json
import pickle
from typing import Any
import warnings

import numpy as np
import pandas as pd

from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.impute import SimpleImputer
from sklearn.pipeline import Pipeline


DATE_COLUMNS = [
    "Registration Date",
    "Proposal Review Date",
    "Research Review Date",
    "NOS Date",
    "Final Draft Submission Date",
    "Viva Date",
]

CLUSTER_MAPPING = {
    "Data to Knowledge": 0,
    "Service Computing": 1,
    "Enabling Technologies & Infrastructures": 2,
}

CHECKPOINT_SEQUENCE = [
    ("proposal_review_sem", "Proposal Review Date", "proposal_review_date"),
    ("research_review_sem", "Research Review Date", "research_review_date"),
    ("nos_sem", "NOS Date", "nos_date"),
    ("final_draft_sem", "Final Draft Submission Date", "final_draft_submission_date"),
    ("viva_sem", "Viva Date", "viva_date"),
]

PREVIOUS_SEMESTER_COLUMN = {
    "proposal_review_sem": None,
    "research_review_sem": "proposal_review_sem",
    "nos_sem": "research_review_sem",
    "final_draft_sem": "nos_sem",
    "viva_sem": "final_draft_sem",
}

GAP_TARGET_COLUMNS = {
    "proposal_review_sem": "gap_to_proposal_sem",
    "research_review_sem": "gap_to_research_sem",
    "nos_sem": "gap_to_nos_sem",
    "final_draft_sem": "gap_to_final_draft_sem",
    "viva_sem": "gap_to_viva_sem",
}

GAP_MINIMUMS = {
    "proposal_review_sem": 0,
    "research_review_sem": 1,
    "nos_sem": 1,
    "final_draft_sem": 1,
    "viva_sem": 1,
}

GAP_RECOMMENDATION_FEATURES = {
    "proposal_review_sem": ["Cluster", "registration_year", "registration_month"],
    "research_review_sem": ["Cluster", "proposal_review_sem", "registration_year"],
    "nos_sem": ["Cluster", "proposal_review_sem", "research_review_sem"],
    "final_draft_sem": ["Cluster", "research_review_sem", "nos_sem"],
    "viva_sem": ["Cluster", "nos_sem", "final_draft_sem"],
}

CLASSIFIER_FEATURE_COLUMNS = [
    "Cluster",
    "proposal_review_sem",
    "research_review_sem",
    "nos_sem",
    "final_draft_sem",
    "gap_proposal_to_research",
    "gap_research_to_nos",
    "gap_nos_to_final_draft",
]

MIN_MODEL_ROWS = 8
# Business rule: Graduation on Time (GoT) = graduate within 42 months from registration.
GOT_MAX_MONTHS = 42

# Keep candidature duration constraints (used for reporting/metadata only).
FULL_TIME_MAX_SEMESTERS = 10


class MedianRegressor:
    def __init__(self) -> None:
        self.value = 1.0

    def fit(self, _: pd.DataFrame, target: pd.Series) -> "MedianRegressor":
        values = pd.Series(target).dropna()
        self.value = float(values.median()) if not values.empty else 1.0
        return self

    def predict(self, features: pd.DataFrame) -> np.ndarray:
        return np.full(len(features), float(self.value), dtype=float)


class ConstantClassifier:
    def __init__(self) -> None:
        self.constant = 0
        self.classes_ = np.array([0, 1], dtype=int)

    def fit(self, _: pd.DataFrame, target: pd.Series) -> "ConstantClassifier":
        values = pd.Series(target).dropna()
        if values.empty:
            self.constant = 0
            return self

        mode_series = values.mode(dropna=True)
        self.constant = int(mode_series.iloc[0]) if not mode_series.empty else int(values.iloc[0])
        self.constant = 1 if self.constant == 1 else 0
        return self

    def predict(self, features: pd.DataFrame) -> np.ndarray:
        return np.full(len(features), int(self.constant), dtype=int)

    def predict_proba(self, features: pd.DataFrame) -> np.ndarray:
        p1 = float(self.constant)
        p0 = 1.0 - p1
        return np.tile(np.array([p0, p1], dtype=float), (len(features), 1))


class SklearnRfRegressor:
    def __init__(
        self,
        n_estimators: int = 400,
        max_depth: int | None = None,
        random_state: int = 42,
    ) -> None:
        self.feature_columns: list[str] = []
        self.pipeline: Pipeline | None = None
        self.n_estimators = int(n_estimators)
        self.max_depth = max_depth
        self.random_state = int(random_state)

    def fit(self, features: pd.DataFrame, target: pd.Series) -> "SklearnRfRegressor":
        self.feature_columns = [str(c) for c in features.columns]
        self.pipeline = Pipeline(
            steps=[
                ("imputer", SimpleImputer(strategy="median")),
                (
                    "model",
                    RandomForestRegressor(
                        n_estimators=self.n_estimators,
                        max_depth=self.max_depth,
                        random_state=self.random_state,
                        n_jobs=-1,
                    ),
                ),
            ]
        )

        x = features[self.feature_columns].to_numpy(dtype=float)
        y = target.to_numpy(dtype=float)
        self.pipeline.fit(x, y)
        return self

    def predict(self, features: pd.DataFrame) -> np.ndarray:
        if self.pipeline is None:
            return np.zeros(len(features), dtype=float)

        x = features[self.feature_columns].to_numpy(dtype=float)
        return np.asarray(self.pipeline.predict(x), dtype=float)


class SklearnRfClassifier:
    def __init__(
        self,
        n_estimators: int = 400,
        max_depth: int | None = None,
        random_state: int = 42,
    ) -> None:
        self.feature_columns: list[str] = []
        self.pipeline: Pipeline | None = None
        self.classes_ = np.array([0, 1], dtype=int)
        self.n_estimators = int(n_estimators)
        self.max_depth = max_depth
        self.random_state = int(random_state)

    def fit(self, features: pd.DataFrame, target: pd.Series) -> "SklearnRfClassifier":
        self.feature_columns = [str(c) for c in features.columns]
        self.pipeline = Pipeline(
            steps=[
                ("imputer", SimpleImputer(strategy="median")),
                (
                    "model",
                    RandomForestClassifier(
                        n_estimators=self.n_estimators,
                        max_depth=self.max_depth,
                        random_state=self.random_state,
                        n_jobs=-1,
                        class_weight="balanced",
                    ),
                ),
            ]
        )

        x = features[self.feature_columns].to_numpy(dtype=float)
        y = target.to_numpy(dtype=int)
        self.pipeline.fit(x, y)

        model = self.pipeline.named_steps["model"]
        self.classes_ = np.asarray(getattr(model, "classes_", np.array([0, 1])), dtype=int)
        return self

    def predict_proba(self, features: pd.DataFrame) -> np.ndarray:
        if self.pipeline is None:
            return np.tile(np.array([1.0, 0.0], dtype=float), (len(features), 1))

        x = features[self.feature_columns].to_numpy(dtype=float)
        probabilities = self.pipeline.predict_proba(x)
        return np.asarray(probabilities, dtype=float)


@dataclass(frozen=True)
class SemesterToken:
    year: int
    number: int

    def index(self) -> int:
        return (self.year * 2) + (self.number - 1)


def parse_dates(frame: pd.DataFrame) -> pd.DataFrame:
    result = frame.copy()
    for column in DATE_COLUMNS:
        if column in result.columns:
            with warnings.catch_warnings():
                warnings.simplefilter("ignore", category=UserWarning)
                iso_first = pd.to_datetime(result[column], errors="coerce")
            dayfirst_fallback = pd.to_datetime(result[column], dayfirst=True, errors="coerce")
            result[column] = iso_first.fillna(dayfirst_fallback)
    return result


def semester_token(date_value: pd.Timestamp) -> SemesterToken:
    month = int(date_value.month)
    year = int(date_value.year)

    if month >= 9:
        return SemesterToken(year=year, number=1)

    if month <= 2:
        return SemesterToken(year=year - 1, number=1)

    return SemesterToken(year=year, number=2)


def semester_token_from_index(index_value: int) -> SemesterToken:
    return SemesterToken(year=int(index_value // 2), number=int(index_value % 2) + 1)


def semester_end(token: SemesterToken) -> pd.Timestamp:
    if token.number == 1:
        return pd.Timestamp(year=token.year + 1, month=2, day=1) + pd.offsets.MonthEnd(0)

    return pd.Timestamp(year=token.year, month=8, day=31)


def get_semester_index(registration_date: pd.Timestamp, target_date: pd.Timestamp) -> float:
    if pd.isna(registration_date) or pd.isna(target_date):
        return np.nan

    registration_token = semester_token(registration_date)
    target_token = semester_token(target_date)
    return float(target_token.index() - registration_token.index() + 1)


def semester_gap(previous_semester: float, current_semester: float) -> float:
    if pd.isna(previous_semester) or pd.isna(current_semester):
        return np.nan
    return float(current_semester - previous_semester)


def encode_cluster(value: str) -> int:
    if value not in CLUSTER_MAPPING:
        raise ValueError(f"Unsupported cluster: {value}")
    return CLUSTER_MAPPING[value]


def add_semester_features(frame: pd.DataFrame) -> pd.DataFrame:
    result = parse_dates(frame)

    for semester_column, date_column, _ in CHECKPOINT_SEQUENCE:
        result[semester_column] = result.apply(
            lambda row, target_column=date_column: get_semester_index(row["Registration Date"], row[target_column]),
            axis=1,
        )

    result["gap_proposal_to_research"] = result.apply(
        lambda row: semester_gap(row["proposal_review_sem"], row["research_review_sem"]), axis=1
    )
    result["gap_research_to_nos"] = result.apply(
        lambda row: semester_gap(row["research_review_sem"], row["nos_sem"]), axis=1
    )
    result["gap_nos_to_final_draft"] = result.apply(lambda row: semester_gap(row["nos_sem"], row["final_draft_sem"]), axis=1)

    return result


def add_context_features(frame: pd.DataFrame) -> pd.DataFrame:
    result = frame.copy()
    registration = pd.to_datetime(result["Registration Date"], errors="coerce")
    result["registration_year"] = registration.dt.year.astype(float)
    result["registration_month"] = registration.dt.month.astype(float)
    return result


def prepare_training_frame(frame: pd.DataFrame) -> pd.DataFrame:
    result = add_semester_features(frame)
    result = add_context_features(result)
    result["Cluster"] = result["Cluster"].map(encode_cluster)
    return result


def prepare_prediction_frame(payload: dict[str, Any]) -> pd.DataFrame:
    frame = pd.DataFrame([payload])
    frame = add_semester_features(frame)
    frame = add_context_features(frame)
    frame["Cluster"] = frame["Cluster"].map(encode_cluster)
    return frame


def calculate_got(row: pd.Series) -> int:
    """Binary GoT label.

    Rule: GoT = 1 if the student completes within GOT_MAX_MONTHS months
    from registration. The official definition uses senate endorsement date;
    this dataset currently uses Viva Date as the completion proxy.
    """
    registration = row.get("Registration Date")
    completion = row.get("Viva Date")

    if pd.isna(registration) or pd.isna(completion):
        return 0

    registration_ts = pd.to_datetime(registration, errors="coerce")
    completion_ts = pd.to_datetime(completion, errors="coerce")
    if pd.isna(registration_ts) or pd.isna(completion_ts):
        return 0

    cutoff = registration_ts + pd.DateOffset(months=GOT_MAX_MONTHS)
    return 1 if completion_ts <= cutoff else 0


def _build_regressor(train_rows: int, target: pd.Series, *, n_estimators: int, max_depth: int | None) -> Any:
    if train_rows < MIN_MODEL_ROWS or target.nunique(dropna=True) <= 1:
        return MedianRegressor()

    return SklearnRfRegressor(n_estimators=n_estimators, max_depth=max_depth)


def _build_classifier(train_rows: int, target: pd.Series, *, n_estimators: int, max_depth: int | None) -> Any:
    if train_rows < MIN_MODEL_ROWS or target.nunique(dropna=True) <= 1:
        return ConstantClassifier()

    positive = int((target == 1).sum())
    negative = int((target == 0).sum())
    if positive <= 0 or negative <= 0:
        return ConstantClassifier()

    return SklearnRfClassifier(n_estimators=n_estimators, max_depth=max_depth)


def train_model_bundle(
    frame: pd.DataFrame,
    *,
    rf_estimators: int = 400,
    rf_max_depth: int | None = None,
) -> tuple[dict[str, Any], dict[str, Any]]:
    checkpoint_models: dict[str, Any] = {}
    row_counts: dict[str, int] = {}

    frame_with_gaps = frame.copy()
    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        previous_column = PREVIOUS_SEMESTER_COLUMN[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]

        if previous_column is None:
            gap_values = frame_with_gaps[semester_column] - 1.0
        else:
            gap_values = frame_with_gaps[semester_column] - frame_with_gaps[previous_column]

        frame_with_gaps[gap_column] = gap_values.where(gap_values.notna()).clip(lower=float(GAP_MINIMUMS[semester_column]))

    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        feature_columns = GAP_RECOMMENDATION_FEATURES[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]
        train_frame = frame_with_gaps[feature_columns + [gap_column]].dropna(subset=[gap_column])
        train_frame = train_frame.dropna(subset=feature_columns)
        x = train_frame[feature_columns]
        y = train_frame[gap_column]

        regressor = _build_regressor(len(train_frame), y, n_estimators=rf_estimators, max_depth=rf_max_depth)
        regressor.fit(x, y)

        checkpoint_models[semester_column] = regressor
        row_counts[f"{semester_column}_gap_model"] = int(len(train_frame))

    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)

    x_cls = got_frame[CLASSIFIER_FEATURE_COLUMNS]
    y_cls = got_frame["GOT"]
    classifier = _build_classifier(len(got_frame), y_cls, n_estimators=rf_estimators, max_depth=rf_max_depth)
    classifier.fit(x_cls, y_cls)

    row_counts["got_classifier"] = int(len(got_frame))

    bundle = {
        "checkpoint_models": checkpoint_models,
        "got_model": classifier,
    }

    report = {
        "total_rows": int(len(frame)),
        "row_counts": row_counts,
        "mode": "full_time",
        "family": "sklearn_random_forest",
        "constraints": {
            "min_semesters": 4,
            "max_semesters": FULL_TIME_MAX_SEMESTERS,
        },
    }

    return bundle, report


def _coerce_semester_value(value: float, minimum: int) -> int:
    if pd.isna(value):
        return minimum

    return max(minimum, int(round(float(value))))


def _coerce_gap_value(value: float, minimum_gap: int) -> int:
    if pd.isna(value):
        return minimum_gap

    return max(minimum_gap, int(round(float(value))))


def _build_recommendation_feature_payload(
    base_row: pd.Series,
    feature_columns: list[str],
    semester_values: dict[str, int],
) -> dict[str, float]:
    payload: dict[str, float] = {}
    for column in feature_columns:
        if column in semester_values:
            payload[column] = float(semester_values[column])
            continue

        value = base_row[column] if column in base_row else np.nan
        payload[column] = np.nan if pd.isna(value) else float(value)

    return payload


def _build_classifier_row(base_row: pd.Series, semester_values: dict[str, int]) -> dict[str, float]:
    row = {
        "Cluster": float(base_row["Cluster"]),
        "proposal_review_sem": float(semester_values["proposal_review_sem"]),
        "research_review_sem": float(semester_values["research_review_sem"]),
        "nos_sem": float(semester_values["nos_sem"]),
        "final_draft_sem": float(semester_values["final_draft_sem"]),
    }
    row["gap_proposal_to_research"] = row["research_review_sem"] - row["proposal_review_sem"]
    row["gap_research_to_nos"] = row["nos_sem"] - row["research_review_sem"]
    row["gap_nos_to_final_draft"] = row["final_draft_sem"] - row["nos_sem"]
    return row


def _extract_positive_probability(model: Any, feature_frame: pd.DataFrame) -> float:
    probabilities = model.predict_proba(feature_frame)[0]
    classes = getattr(model, "classes_", None)

    if classes is None:
        return float(probabilities[-1])

    classes_list = [int(c) for c in classes]
    if 1 in classes_list:
        return float(probabilities[classes_list.index(1)])

    return 0.0


def _semester_end_for_relative_index(registration_date: pd.Timestamp, relative_index: int) -> pd.Timestamp | None:
    if pd.isna(registration_date):
        return None

    if relative_index < 1:
        relative_index = 1

    base_token = semester_token(registration_date)
    target_token = semester_token_from_index(base_token.index() + int(relative_index) - 1)
    return semester_end(target_token)


def predict_student_payload(
    payload: dict[str, Any],
    bundle: dict[str, Any],
    decision_threshold: float = 0.5,
) -> dict[str, Any]:
    frame = prepare_prediction_frame(payload)
    row = frame.iloc[0]

    checkpoint_models = bundle["checkpoint_models"]

    semester_values: dict[str, int] = {}
    minimum = 1

    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        observed = row[semester_column]
        if pd.isna(observed):
            feature_columns = GAP_RECOMMENDATION_FEATURES[semester_column]
            feature_payload = _build_recommendation_feature_payload(row, feature_columns, semester_values)

            feature_frame = pd.DataFrame([feature_payload])
            predicted_gap = float(checkpoint_models[semester_column].predict(feature_frame)[0])
            gap = _coerce_gap_value(predicted_gap, GAP_MINIMUMS[semester_column])
            semester_values[semester_column] = max(minimum, minimum + gap)
        else:
            semester_values[semester_column] = _coerce_semester_value(float(observed), minimum)

        minimum = semester_values[semester_column]

    classifier_row = _build_classifier_row(row, semester_values)
    classifier_frame = pd.DataFrame([classifier_row])[CLASSIFIER_FEATURE_COLUMNS]
    got_probability = _extract_positive_probability(bundle["got_model"], classifier_frame)

    registration_date = row["Registration Date"]
    recommended_dates: dict[str, str | None] = {}
    recommended_semesters: dict[str, int] = {}
    semester_groups: dict[int, list[str]] = {}

    for semester_column, _, output_key in CHECKPOINT_SEQUENCE:
        semester_index = int(semester_values[semester_column])
        recommended_semesters[semester_column] = semester_index
        semester_groups.setdefault(semester_index, []).append(output_key)
        recommended_date = _semester_end_for_relative_index(registration_date, semester_index)
        recommended_dates[output_key] = None if recommended_date is None else recommended_date.strftime("%Y-%m-%d")

    same_semester_groups = [
        {"semester": int(semester), "checkpoints": checkpoints}
        for semester, checkpoints in sorted(semester_groups.items(), key=lambda item: item[0])
        if len(checkpoints) > 1
    ]

    return {
        "study_mode_model": "full_time",
        "got_probability": float(got_probability),
        "predicted_got": bool(got_probability >= decision_threshold),
        "decision_threshold": float(decision_threshold),
        "recommended_checkpoint_semesters": recommended_semesters,
        "recommended_checkpoint_dates": recommended_dates,
        "recommendation_diagnostics": {
            "generation_mode": "gap_regression_random_forest",
            "same_semester_groups": same_semester_groups,
        },
    }


def save_metadata(meta_path: Path, training_report: dict[str, Any] | None = None) -> dict[str, Any]:
    metadata: dict[str, Any] = {
        "classifier_feature_columns": CLASSIFIER_FEATURE_COLUMNS,
        "recommendation_sequence": [step[2] for step in CHECKPOINT_SEQUENCE],
        "recommendation_gap_features": GAP_RECOMMENDATION_FEATURES,
        "recommendation_gap_targets": GAP_TARGET_COLUMNS,
        "clusters": CLUSTER_MAPPING,
        "study_mode": "full_time",
        "semester_definition": {
            "semester_1": "September-February",
            "semester_2": "March-August",
        },
        "candidature_constraints": {
            "min_semesters": 4,
            "max_semesters": FULL_TIME_MAX_SEMESTERS,
        },
        "model_family": "sklearn_random_forest",
        "model_version": datetime.utcnow().strftime("%Y%m%d%H%M%S"),
    }

    if training_report is not None:
        metadata["training_report"] = training_report

    meta_path.parent.mkdir(parents=True, exist_ok=True)
    meta_path.write_text(json.dumps(metadata, indent=2), encoding="utf-8")
    return metadata


def load_model_bundle(model_path: Path, meta_path: Path) -> tuple[dict[str, Any], dict[str, Any]]:
    with model_path.open("rb") as handle:
        model = pickle.load(handle)
    metadata = json.loads(meta_path.read_text(encoding="utf-8"))
    return model, metadata


def dump_model_bundle(model_path: Path, bundle: dict[str, Any]) -> None:
    model_path.parent.mkdir(parents=True, exist_ok=True)
    with model_path.open("wb") as handle:
        pickle.dump(bundle, handle)
