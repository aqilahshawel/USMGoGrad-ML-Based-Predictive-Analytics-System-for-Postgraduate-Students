<?php

$projectRoot = dirname(base_path());
$windowsVenvPython = $projectRoot . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
$unixVenvPython = $projectRoot . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';

$defaultPythonBinary = 'python';

if (PHP_OS_FAMILY === 'Windows' && is_file($windowsVenvPython)) {
    $defaultPythonBinary = $windowsVenvPython;
} elseif (is_file($unixVenvPython)) {
    $defaultPythonBinary = $unixVenvPython;
}

return [
    'python_binary' => env('ML_PYTHON_BINARY', $defaultPythonBinary),
    'predict_script' => base_path('ml/predict_got.py'),
    'model_path' => base_path('ml/artifacts/got_xgb_model.pkl'),
    'meta_path' => base_path('ml/artifacts/got_model_meta.json'),

    'paper_recommender_script' => base_path('ml/recommend_papers.py'),
    'paper_catalog_path' => base_path('ml/papers_catalog.csv'),

    'predict_on_profile_view' => (bool) env('ML_PREDICT_ON_PROFILE_VIEW', true),
    'profile_refresh_cooldown_seconds' => (int) env('ML_PROFILE_REFRESH_COOLDOWN_SECONDS', 0),
    'low_risk_threshold' => (float) env('ML_LOW_RISK_THRESHOLD', 0.80),
    'medium_risk_threshold' => (float) env('ML_MEDIUM_RISK_THRESHOLD', 0.50),
];