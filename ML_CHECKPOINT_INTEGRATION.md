# ML Checkpoint Integration

This repository now has the first production-oriented path for GoT prediction.

## Workflow

1. Admin registers a student account and assigns the cluster.
2. Admin sets the student's registration date and study type from the student management page.
3. Student opens the Checkpoints page and updates actual milestone dates.
4. Laravel stores those dates in `student_checkpoints`.
5. Laravel calls `ml/predict_got.py` through Symfony Process.
6. Python converts milestone dates into semester-based features and returns a GoT probability.
7. Laravel stores the latest probability, risk level, prediction timestamp, and model version.

## Semester Definition

- Semester I: September-February
- Semester II: March-August

The Python scripts and Laravel semester service must stay aligned.

## Python Commands

Install the Python packages:

```bash
pip install -r ml/requirements.txt
```

Train the model and save artifacts:

```bash
python ml/train_got_model.py --dataset ml/PhD Student Dataset.csv --model-out ml/artifacts/got_xgb_model.joblib --meta-out ml/artifacts/got_model_meta.json
```

## Laravel Command

Run predictions for one student:

```bash
php artisan ml:predict-got 1
```

Run predictions for all students:

```bash
php artisan ml:predict-got --all
```

## Notes

- The current prediction path is ML-first and uses the checkpoint dates as the main signal.
- The existing timeline service can still be used for display and semester progression, but the GoT probability now comes from the trained model.
- If `cluster`, `start_date`, or `study_mode` is missing, prediction is skipped until the student profile is complete.