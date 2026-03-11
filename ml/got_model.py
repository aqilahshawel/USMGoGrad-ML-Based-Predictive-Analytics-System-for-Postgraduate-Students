from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
import json

import joblib
import numpy as np
import pandas as pd


DATE_COLUMNS = [
    "Registration Date",
    "Proposal Review Date",
    "Research Review Date",
    "NOS Date",
    "Final Draft Submission Date",
    "Viva Date",
]

FEATURE_COLUMNS = [
    "Cluster",
    "Type",
    "proposal_sem",
    "research_sem",
    "nos_sem",
    "final_draft_sem",
    "gap_proposal_to_research",
    "gap_research_to_nos",
    "gap_nos_to_final_draft",
]

CLUSTER_MAPPING = {
    "Data to Knowledge": 0,
    "Service Computing": 1,
    "Enabling Technologies & Infrastructures": 2,
}

TYPE_MAPPING = {
    "Full Time": 0,
    "Part Time": 1,
    "full_time": 0,
    "part_time": 1,
    "full time": 0,
    "part time": 1,
}


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
            result[column] = pd.to_datetime(result[column], dayfirst=True, errors="coerce")
    return result


def semester_token(date_value: pd.Timestamp) -> SemesterToken:
    month = int(date_value.month)
    year = int(date_value.year)

    if month >= 9:
        return SemesterToken(year=year, number=1)

    if month <= 2:
        return SemesterToken(year=year - 1, number=1)

    return SemesterToken(year=year, number=2)


def get_semester_index(registration_date: pd.Timestamp, target_date: pd.Timestamp):
    if pd.isna(registration_date) or pd.isna(target_date):
        return np.nan

    registration_token = semester_token(registration_date)
    target_token = semester_token(target_date)
    return int(target_token.index() - registration_token.index() + 1)


def semester_gap(previous_semester, current_semester):
    if pd.isna(previous_semester) or pd.isna(current_semester):
        return np.nan
    return float(current_semester - previous_semester)


def encode_cluster(value: str) -> int:
    if value not in CLUSTER_MAPPING:
        raise ValueError(f"Unsupported cluster: {value}")
    return CLUSTER_MAPPING[value]


def encode_type(value: str) -> int:
    normalized = value.strip()
    if normalized not in TYPE_MAPPING:
        normalized = normalized.lower()
    if normalized not in TYPE_MAPPING:
        raise ValueError(f"Unsupported study type: {value}")
    return TYPE_MAPPING[normalized]


def add_semester_features(frame: pd.DataFrame) -> pd.DataFrame:
    result = parse_dates(frame)
    result["proposal_sem"] = result.apply(
        lambda row: get_semester_index(row["Registration Date"], row["Proposal Review Date"]), axis=1
    )
    result["research_sem"] = result.apply(
        lambda row: get_semester_index(row["Registration Date"], row["Research Review Date"]), axis=1
    )
    result["nos_sem"] = result.apply(
        lambda row: get_semester_index(row["Registration Date"], row["NOS Date"]), axis=1
    )
    result["final_draft_sem"] = result.apply(
        lambda row: get_semester_index(row["Registration Date"], row["Final Draft Submission Date"]), axis=1
    )
    result["viva_sem"] = result.apply(
        lambda row: get_semester_index(row["Registration Date"], row["Viva Date"]), axis=1
    )
    result["gap_proposal_to_research"] = result.apply(
        lambda row: semester_gap(row["proposal_sem"], row["research_sem"]), axis=1
    )
    result["gap_research_to_nos"] = result.apply(
        lambda row: semester_gap(row["research_sem"], row["nos_sem"]), axis=1
    )
    result["gap_nos_to_final_draft"] = result.apply(
        lambda row: semester_gap(row["nos_sem"], row["final_draft_sem"]), axis=1
    )
    return result


def prepare_training_frame(frame: pd.DataFrame) -> pd.DataFrame:
    result = add_semester_features(frame)
    result["Cluster"] = result["Cluster"].map(encode_cluster)
    result["Type"] = result["Type"].map(encode_type)
    return result


def calculate_got(row: pd.Series) -> int:
    duration = row["viva_sem"]
    if pd.isna(duration):
        return 0
    return 1 if duration <= (10 if int(row["Type"]) == 0 else 15) else 0


def prepare_prediction_frame(payload: dict) -> pd.DataFrame:
    frame = pd.DataFrame([payload])
    frame = add_semester_features(frame)
    frame["Cluster"] = frame["Cluster"].map(encode_cluster)
    frame["Type"] = frame["Type"].map(encode_type)
    return frame[FEATURE_COLUMNS]


def save_metadata(meta_path: Path) -> dict:
    metadata = {
        "feature_columns": FEATURE_COLUMNS,
        "clusters": CLUSTER_MAPPING,
        "types": TYPE_MAPPING,
        "feature_notes": {
            "timing_model": "Features focus on checkpoint semester positions and pre-viva gaps between checkpoints.",
            "ekc500": "Handled as a pass/check gate in the app and intentionally excluded from ML features because there is no historical EKC500 dataset.",
            "leakage_guard": "Viva-derived features are intentionally excluded so the model remains an early-warning predictor.",
        },
        "semester_definition": {
            "semester_1": "September-February",
            "semester_2": "March-August",
        },
        "model_version": datetime.utcnow().strftime("%Y%m%d%H%M%S"),
    }
    meta_path.parent.mkdir(parents=True, exist_ok=True)
    meta_path.write_text(json.dumps(metadata, indent=2), encoding="utf-8")
    return metadata


def load_model_bundle(model_path: Path, meta_path: Path):
    model = joblib.load(model_path)
    metadata = json.loads(meta_path.read_text(encoding="utf-8"))
    return model, metadata