"""GoT model bundle + feature engineering.

This file is the heart of my "intelligent" logic for the system.

What I do here (high level):
1) Convert raw dates into *relative semester indices* from Registration Date.
2) Train per-checkpoint *gap regressors* to recommend a realistic timeline.
3) Train a binary classifier (XGBoost) to estimate GoT probability.
4) At inference time, if a checkpoint is missing, I predict its gap and fill it
    forward while enforcing monotonic semesters.

Notes I keep in mind:
- My models are tabular (XGBoost), not neural.
- My time axis is discretized as semesters (2 per year).
- I include fallback models for tiny datasets so the system still runs.
"""

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
import xgboost as xgb


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
FULL_TIME_MAX_SEMESTERS = 10

# Fallback models for small datasets or single-class targets.
# (This prevents training from crashing and keeps inference stable.)
class MedianRegressor:
    def __init__(self) -> None:
        self.value = 1.0

    def fit(self, _: pd.DataFrame, target: pd.Series) -> "MedianRegressor":
        values = pd.Series(target).dropna()
        if values.empty:
            self.value = 1.0
        else:
            self.value = float(values.median())
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


class XgbTreeRegressor:
    def __init__(self) -> None:
        self.feature_columns: list[str] = []
        self.booster: xgb.Booster | None = None

    def fit(self, features: pd.DataFrame, target: pd.Series) -> "XgbTreeRegressor":
        self.feature_columns = [str(column) for column in features.columns]
        dtrain = xgb.DMatrix(
            features.to_numpy(dtype=float),
            label=target.to_numpy(dtype=float),
            feature_names=self.feature_columns,
        )
        self.booster = xgb.train(
            {
                "max_depth": 4,
                "eta": 0.05,
                "subsample": 0.9,
                "colsample_bytree": 0.9,
                "objective": "reg:squarederror",
                "seed": 42,
            },
            dtrain,
            num_boost_round=220,
        )
        return self

    def predict(self, features: pd.DataFrame) -> np.ndarray:
        if self.booster is None:
            return np.zeros(len(features), dtype=float)

        dtest = xgb.DMatrix(
            features[self.feature_columns].to_numpy(dtype=float),
            feature_names=self.feature_columns,
        )
        return self.booster.predict(dtest)


class XgbBinaryClassifier:
    def __init__(self, scale_pos_weight: float = 1.0) -> None:
        self.feature_columns: list[str] = []
        self.booster: xgb.Booster | None = None
        self.classes_ = np.array([0, 1], dtype=int)
        self.scale_pos_weight = float(max(1.0, scale_pos_weight))

    def fit(self, features: pd.DataFrame, target: pd.Series) -> "XgbBinaryClassifier":
        self.feature_columns = [str(column) for column in features.columns]
        dtrain = xgb.DMatrix(
            features.to_numpy(dtype=float),
            label=target.to_numpy(dtype=float),
            feature_names=self.feature_columns,
        )
        self.booster = xgb.train(
            {
                "max_depth": 4,
                "eta": 0.05,
                "subsample": 0.9,
                "colsample_bytree": 0.9,
                "objective": "binary:logistic",
                "eval_metric": ["logloss", "aucpr"],
                "scale_pos_weight": self.scale_pos_weight,
                "max_delta_step": 1,
                "seed": 42,
            },
            dtrain,
            num_boost_round=260,
        )
        return self

    def predict_proba(self, features: pd.DataFrame) -> np.ndarray:
        if self.booster is None:
            return np.tile(np.array([1.0, 0.0], dtype=float), (len(features), 1))

        dtest = xgb.DMatrix(
            features[self.feature_columns].to_numpy(dtype=float),
            feature_names=self.feature_columns,
        )
        p1 = self.booster.predict(dtest)
        p0 = 1.0 - p1
        return np.vstack([p0, p1]).T


@dataclass(frozen=True)
class SemesterToken:
    year: int
    number: int

    def index(self) -> int:
        return (self.year * 2) + (self.number - 1)


def parse_dates(frame: pd.DataFrame) -> pd.DataFrame:
    """Parse date columns robustly.

    My dataset can contain mixed date formats, so I try ISO-first, then a
    day-first fallback.
    """
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
    """Map a calendar date to my academic-semester token.

    Semester definition (USM-like):
    - Semester 1: Sep–Feb
    - Semester 2: Mar–Aug
    """
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
    # I return the *end date* of the semester to make recommendations concrete.
    if token.number == 1:
        return pd.Timestamp(year=token.year + 1, month=2, day=1) + pd.offsets.MonthEnd(0)

    return pd.Timestamp(year=token.year, month=8, day=31)


def get_semester_index(registration_date: pd.Timestamp, target_date: pd.Timestamp) -> float:
    """Convert a date into a relative semester index from registration.

    Example: registration semester itself is index 1.
    """
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
    # I encode clusters as integers so XGBoost can consume them as features.
    if value not in CLUSTER_MAPPING:
        raise ValueError(f"Unsupported cluster: {value}")
    return CLUSTER_MAPPING[value]


def add_semester_features(frame: pd.DataFrame) -> pd.DataFrame:
    """Add semester-index columns + derived gap features."""
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
    result["gap_nos_to_final_draft"] = result.apply(
        lambda row: semester_gap(row["nos_sem"], row["final_draft_sem"]), axis=1
    )

    return result


def add_context_features(frame: pd.DataFrame) -> pd.DataFrame:
    # Lightweight context features from registration date.
    result = frame.copy()
    registration = pd.to_datetime(result["Registration Date"], errors="coerce")
    result["registration_year"] = registration.dt.year.astype(float)
    result["registration_month"] = registration.dt.month.astype(float)
    return result


def prepare_training_frame(frame: pd.DataFrame) -> pd.DataFrame:
    """Training preprocessing: dates -> semesters -> numeric features."""
    result = add_semester_features(frame)
    result = add_context_features(result)
    result["Cluster"] = result["Cluster"].map(encode_cluster)
    return result


def prepare_prediction_frame(payload: dict[str, Any]) -> pd.DataFrame:
    """Inference preprocessing for a single student JSON payload."""
    frame = pd.DataFrame([payload])
    frame = add_semester_features(frame)
    frame = add_context_features(frame)
    frame["Cluster"] = frame["Cluster"].map(encode_cluster)
    return frame


def calculate_got(row: pd.Series) -> int:
    # My label: GoT = 1 if viva_sem <= max allowed semesters, else 0.
    duration = row["viva_sem"]
    if pd.isna(duration):
        return 0

    return 1 if duration <= FULL_TIME_MAX_SEMESTERS else 0


def _build_regressor(train_rows: int, target: pd.Series) -> Any:
    # If I don't have enough data (or it's degenerate), I fall back to MedianRegressor.
    if train_rows < MIN_MODEL_ROWS or target.nunique(dropna=True) <= 1:
        return MedianRegressor()
    else:
        return XgbTreeRegressor()


def _build_classifier(train_rows: int, target: pd.Series) -> Any:
    # Same idea: if data is too small or single-class, I fall back to a constant baseline.
    if train_rows < MIN_MODEL_ROWS or target.nunique(dropna=True) <= 1:
        return ConstantClassifier()
    else:
        positive = int((target == 1).sum())
        negative = int((target == 0).sum())
        if positive <= 0 or negative <= 0:
            return ConstantClassifier()

        scale_pos_weight = negative / positive
        return XgbBinaryClassifier(scale_pos_weight=scale_pos_weight)


def train_model_bundle(frame: pd.DataFrame) -> tuple[dict[str, Any], dict[str, Any]]:
    """Train the full bundle: checkpoint gap regressors + GoT classifier."""
    checkpoint_models: dict[str, Any] = {}
    row_counts: dict[str, int] = {}

    # Step 1: create gap targets, e.g. gap_to_nos_sem = nos_sem - research_review_sem.
    frame_with_gaps = frame.copy()
    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        previous_column = PREVIOUS_SEMESTER_COLUMN[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]

        if previous_column is None:
            gap_values = frame_with_gaps[semester_column] - 1.0
        else:
            gap_values = frame_with_gaps[semester_column] - frame_with_gaps[previous_column]

        frame_with_gaps[gap_column] = gap_values.where(gap_values.notna()).clip(
            lower=float(GAP_MINIMUMS[semester_column])
        )

    # Step 2: train one regressor per checkpoint gap.
    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        feature_columns = GAP_RECOMMENDATION_FEATURES[semester_column]
        gap_column = GAP_TARGET_COLUMNS[semester_column]
        train_frame = frame_with_gaps[feature_columns + [gap_column]].dropna(subset=[gap_column])
        train_frame = train_frame.dropna(subset=feature_columns)
        x = train_frame[feature_columns]
        y = train_frame[gap_column]

        regressor = _build_regressor(len(train_frame), y)
        regressor.fit(x, y)

        checkpoint_models[semester_column] = regressor
        row_counts[f"{semester_column}_gap_model"] = int(len(train_frame))

    # Step 3: train the GoT classifier (only on rows where viva_sem exists).
    got_frame = frame.dropna(subset=["viva_sem"]).copy()
    got_frame["GOT"] = got_frame.apply(calculate_got, axis=1)

    x_cls = got_frame[CLASSIFIER_FEATURE_COLUMNS]
    y_cls = got_frame["GOT"]
    classifier = _build_classifier(len(got_frame), y_cls)
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
        # I allow features to come from either:
        # - already-filled semester_values (predicted checkpoints), or
        # - raw base_row columns (Cluster/registration_year/month).
        if column in semester_values:
            payload[column] = float(semester_values[column])
            continue

        value = base_row[column] if column in base_row else np.nan
        if pd.isna(value):
            payload[column] = np.nan
        else:
            payload[column] = float(value)

    return payload


def _build_classifier_row(base_row: pd.Series, semester_values: dict[str, int]) -> dict[str, float]:
    # I explicitly compute the gap features to match CLASSIFIER_FEATURE_COLUMNS.
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
    # This handles both sklearn-style classifiers and my custom wrappers.
    probabilities = model.predict_proba(feature_frame)[0]
    classes = getattr(model, "classes_", None)

    if classes is None:
        return float(probabilities[-1])

    classes_list = [int(c) for c in classes]
    if 1 in classes_list:
        return float(probabilities[classes_list.index(1)])

    return 0.0


def _semester_end_for_relative_index(registration_date: pd.Timestamp, relative_index: int) -> pd.Timestamp | None:
    # Convert a relative semester number (1..N) into an actual calendar date.
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
    """Predict GoT probability + recommend checkpoint dates for one student."""
    frame = prepare_prediction_frame(payload)
    row = frame.iloc[0]

    checkpoint_models = bundle["checkpoint_models"]

    semester_values: dict[str, int] = {}
    minimum = 1

    for semester_column, _, _ in CHECKPOINT_SEQUENCE:
        observed = row[semester_column]
        if pd.isna(observed):
            # If the student hasn't reached this checkpoint yet, I *predict the gap*.
            feature_columns = GAP_RECOMMENDATION_FEATURES[semester_column]
            feature_payload = _build_recommendation_feature_payload(row, feature_columns, semester_values)

            feature_frame = pd.DataFrame([feature_payload])
            predicted_gap = float(checkpoint_models[semester_column].predict(feature_frame)[0])
            gap = _coerce_gap_value(predicted_gap, GAP_MINIMUMS[semester_column])

            # I enforce monotonicity: each checkpoint semester must be >= previous.
            semester_values[semester_column] = max(minimum, minimum + gap)
        else:
            # If the student already has an observed date, I trust it (after coercion).
            semester_values[semester_column] = _coerce_semester_value(float(observed), minimum)

        minimum = semester_values[semester_column]

    classifier_row = _build_classifier_row(row, semester_values)
    classifier_frame = pd.DataFrame([classifier_row])[CLASSIFIER_FEATURE_COLUMNS]
    # Probability is model output; predicted_got is a thresholded decision.
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
            "generation_mode": "gap_regression_v2",
            "same_semester_groups": same_semester_groups,
        },
    }


def save_metadata(meta_path: Path, training_report: dict[str, Any] | None = None) -> dict[str, Any]:
    metadata = {
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
