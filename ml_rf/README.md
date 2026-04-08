# ml_rf (Random Forest)

This folder contains a RandomForest baseline for comparison/justification against the existing XGBoost pipeline.

## Files

- `rf_model.py` — feature engineering + training/prediction bundle (RandomForestClassifier + RandomForestRegressor)
- `train_rf_model.py` — trains model bundle and writes artifacts + evaluation into metadata JSON
- `predict_rf_model.py` — reads JSON payload from stdin and outputs JSON prediction

## Train

From the Laravel app root (the folder that contains `ml/` and `ml_rf/`):

```powershell
C:\Users\ASUS\USM\CAT405\USMGoGrad\.venv\Scripts\python.exe ml_rf\train_rf_model.py `
  --dataset "ml\PhD Student Dataset.csv" `
  --model-out "ml_rf\artifacts\got_rf_model.pkl" `
  --meta-out "ml_rf\artifacts\got_rf_meta.json"
```

## Print metrics (accuracy / F1 / MAE)

```powershell
C:\Users\ASUS\USM\CAT405\USMGoGrad\.venv\Scripts\python.exe -c "import json; from pathlib import Path; m=json.loads(Path('ml_rf/artifacts/got_rf_meta.json').read_text()); print(json.dumps(m['training_report']['evaluation']['classification'], indent=2)); print(json.dumps(m['training_report']['evaluation']['checkpoint_gap_mae'], indent=2))"
```

## Predict

```powershell
Get-Content .\payload.json | C:\Users\ASUS\USM\CAT405\USMGoGrad\.venv\Scripts\python.exe ml_rf\predict_rf_model.py `
  --model "ml_rf\artifacts\got_rf_model.pkl" `
  --meta "ml_rf\artifacts\got_rf_meta.json"
```
