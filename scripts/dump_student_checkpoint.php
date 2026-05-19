<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StudentCheckpoint;

$rows = StudentCheckpoint::where('student_id', 4)->get()->map->only(['id','checkpoint_type','baseline_recommended_proposal_review_date','forecast_recommended_proposal_review_date','proposal_review_date']);

echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
