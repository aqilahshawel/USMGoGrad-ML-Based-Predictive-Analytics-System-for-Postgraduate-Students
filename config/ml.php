<?php

return [
    'python_binary' => env('ML_PYTHON_BINARY', 'python'),
    'predict_script' => base_path('ml/predict_got.py'),
    'model_path' => base_path('ml/artifacts/got_xgb_model.joblib'),
    'meta_path' => base_path('ml/artifacts/got_model_meta.json'),
    'low_risk_threshold' => (float) env('ML_LOW_RISK_THRESHOLD', 0.80),
    'medium_risk_threshold' => (float) env('ML_MEDIUM_RISK_THRESHOLD', 0.50),
];