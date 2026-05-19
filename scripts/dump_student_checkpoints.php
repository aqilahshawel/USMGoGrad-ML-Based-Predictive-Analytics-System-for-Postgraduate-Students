<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\StudentCheckpoint;

$ids = [4,5,6,7,8,9,16];
$rows = StudentCheckpoint::whereIn('student_id', $ids)->get()->map->only(['student_id','id','checkpoint_type','baseline_recommended_proposal_review_date','forecast_recommended_proposal_review_date','proposal_review_date']);

echo json_encode($rows, JSON_PRETTY_PRINT) . PHP_EOL;
