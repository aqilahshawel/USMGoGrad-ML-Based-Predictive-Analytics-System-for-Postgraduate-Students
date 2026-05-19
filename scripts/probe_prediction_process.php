<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\Process\Process;

$payload = json_encode([
    'Cluster' => 'Service Computing',
    'Type' => 'Full Time',
    'Registration Date' => '2025-05-01',
    'Proposal Review Date' => null,
    'Research Review Date' => null,
    'NOS Date' => null,
    'Final Draft Submission Date' => null,
    'Viva Date' => null,
], JSON_THROW_ON_ERROR);

$process = new Process([
    (string) config('ml.got_python_binary', config('ml.python_binary')),
    (string) config('ml.predict_script'),
    '--model',
    (string) config('ml.model_path'),
    '--meta',
    (string) config('ml.meta_path'),
], base_path());

$process->setInput($payload);
$process->setTimeout(20);
$process->run();

echo "Exit code: " . $process->getExitCode() . PHP_EOL;
echo "Success: " . ($process->isSuccessful() ? 'yes' : 'no') . PHP_EOL;
echo "STDOUT:\n" . $process->getOutput() . PHP_EOL;
echo "STDERR:\n" . $process->getErrorOutput() . PHP_EOL;
