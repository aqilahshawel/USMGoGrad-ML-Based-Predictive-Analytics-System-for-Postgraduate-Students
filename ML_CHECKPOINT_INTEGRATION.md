# ML Checkpoint Integration

This repository now uses a dual-task machine-learning flow:

1. Recommend target dates for each checkpoint (proposal, research, NOS, final draft, viva) from historical cohort patterns.
2. Predict graduation-on-time (GoT) probability using current student progress plus historical trajectories.

## Workflow

1. Admin registers a student account and assigns the cluster.
2. Admin sets the student's registration date from the student management page.
3. Student opens the Checkpoints page and updates actual milestone dates.
4. Laravel stores those dates in `student_checkpoints`.
5. Laravel calls `ml/predict_got.py` through Symfony Process.
6. Python converts milestone dates into semester-based features and returns:
    - GoT probability
    - predicted GoT label
    - decision threshold used for predicted label
    - ML-recommended checkpoint target dates
7. Laravel stores the latest probability, risk level, recommendation timestamps, model version, and recommended target dates.

## Semester Definition

- Semester I: September-February
- Semester II: March-August

The Python scripts and Laravel semester service must stay aligned.

## Python Commands

Install the Python packages:

```bash
pip install -r ml/requirements.txt
```

Train the model bundle and save artifacts:

```bash
python ml/train_got_model.py --dataset ml/PhD Student Dataset.csv --model-out ml/artifacts/got_xgb_model.pkl --meta-out ml/artifacts/got_model_meta.json
```

The training script now performs walk-forward evaluation by default (multiple chronological windows), prints classification metrics (accuracy, precision, recall, F1, confusion counts), and reports checkpoint gap MAE for validation and test.

The classifier decision threshold is tuned from validation windows when both classes are present. In walk-forward mode, only windows with sufficient validation class counts (controlled by `--wf-threshold-min-positive` and `--wf-threshold-min-negative`) are allowed to vote on the final threshold.
If no window meets these quality checks, threshold tuning safely falls back to the provided `--threshold` value.

Optional split controls:

```bash
python ml/train_got_model.py --dataset "ml/PhD Student Dataset.csv" --train-ratio 0.70 --val-ratio 0.15 --threshold 0.50
```

Optional evaluation mode controls:

```bash
python ml/train_got_model.py --dataset "ml/PhD Student Dataset.csv" --eval-mode walk_forward --wf-windows 4
python ml/train_got_model.py --dataset "ml/PhD Student Dataset.csv" --eval-mode walk_forward --wf-windows 5 --wf-min-train-ratio 0.50
python ml/train_got_model.py --dataset "ml/PhD Student Dataset.csv" --eval-mode walk_forward --wf-windows 5 --wf-min-train-ratio 0.50 --wf-threshold-min-positive 5 --wf-threshold-min-negative 5
python ml/train_got_model.py --dataset "ml/PhD Student Dataset.csv" --eval-mode chronological
```

## Laravel Command

Run prediction + checkpoint target recommendation for one student:

```bash
php artisan ml:predict-got 1
```

Run predictions for all complete-profile students:

```bash
php artisan ml:predict-got --all
```

## Notes

- Candidature mode is fixed to `full_time` across the platform.
- Timeline checkpoint target dates can now come from the stored ML recommendations in `student_checkpoints`.
- If `cluster` or `start_date` is missing, prediction is skipped until the student profile is complete.
- Training/evaluation is script-based (`.py`), and notebook artifacts are not required for runtime prediction.
