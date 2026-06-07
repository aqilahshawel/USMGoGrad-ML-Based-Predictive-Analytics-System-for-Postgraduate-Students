<?php
// Bootstrap Laravel and call PaperRecommendationService::recommendForStudent
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \App\Services\PaperRecommendationService $svc */
$svc = app(\App\Services\PaperRecommendationService::class);
$user = \App\Models\User::find(20);
if (! $user) {
    echo json_encode(['error' => 'user not found']);
    exit(1);
}
// Debug: print whether PHP sees the Semantic Scholar API key
$ss_key = getenv('SEMANTIC_SCHOLAR_API_KEY');
fwrite(STDERR, "PHP getenv SEMANTIC_SCHOLAR_API_KEY=" . ($ss_key === false ? '<not set>' : $ss_key) . "\n");
$ss_enabled = getenv('SEMANTIC_SCHOLAR_ENABLED');
fwrite(STDERR, "PHP getenv SEMANTIC_SCHOLAR_ENABLED=" . ($ss_enabled === false ? '<not set>' : $ss_enabled) . "\n");

$results = $svc->recommendForStudent($user, 3);
echo json_encode($results, JSON_PRETTY_PRINT);

// --- Replicate the Process invocation to inspect stdout/stderr when PHP runs Python ---
fwrite(STDERR, "\n--- Running Python process directly via Symfony Process for debugging ---\n");
$pythonBinary = (string) config('ml.paper_python_binary', config('ml.python_binary'));
$scriptPath = (string) config('ml.paper_recommender_script');
$catalogPath = (string) config('ml.paper_catalog_path');

$researchTitle = trim((string) ($user->research_title ?? ''));
$researchObjective = trim((string) ($user->research_objective ?? ''));
if ($researchTitle !== '' && $researchObjective !== '') {
    $queryText = trim($researchTitle . ' Objective: ' . $researchObjective);
} elseif ($researchTitle !== '') {
    $queryText = $researchTitle;
} elseif ($researchObjective !== '') {
    $queryText = $researchObjective;
} else {
    $queryText = sprintf('Postgraduate research topics for cluster %s', $user->cluster ?? 'general');
}

$tempDir = sys_get_temp_dir();
$systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\Windows';
$comSpec = getenv('ComSpec') ?: getenv('COMSPEC') ?: $systemRoot . '\\System32\\cmd.exe';
fwrite(STDERR, "Student research_title='" . $researchTitle . "'\n");
fwrite(STDERR, "Student research_objective='" . $researchObjective . "'\n");
fwrite(STDERR, "Query passed to Python='" . $queryText . "'\n");
fwrite(STDERR, "Python binary from config: " . $pythonBinary . "\n");


$environment = [
    'SystemRoot' => $systemRoot,
    'SYSTEMROOT' => $systemRoot,
    'ComSpec' => $comSpec,
    'COMSPEC' => $comSpec,
    'TEMP' => $tempDir,
    'TMP' => $tempDir,
    'PYTHONNOUSERSITE' => '1',
    'PYTHONIOENCODING' => 'utf-8',
];

foreach (['PATH', 'Path', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'SEMANTIC_SCHOLAR_ENABLED', 'SEMANTIC_SCHOLAR_API_KEY', 'SEMANTIC_SCHOLAR_CACHE_PATH', 'SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS', 'SEMANTIC_SCHOLAR_SEARCH_LIMIT', 'SEMANTIC_SCHOLAR_FIELDS_OF_STUDY'] as $key) {
    $value = getenv($key);
    if (is_string($value) && trim($value) !== '') {
        $environment[$key] = $value;
    }
}

// Build and run the process
try {
    $process = new Symfony\Component\Process\Process([
        $pythonBinary,
        $scriptPath,
        '--catalog', $catalogPath,
        '--cluster', $user->cluster ?? 'general',
        '--query', $queryText,
        '--limit', '3',
    ], base_path(), $environment);
    $process->setTimeout(20);
    $process->run();

    fwrite(STDERR, "Process exit code: " . $process->getExitCode() . "\n");
    fwrite(STDERR, "Process stdout:\n" . $process->getOutput() . "\n");
    fwrite(STDERR, "Process stderr:\n" . $process->getErrorOutput() . "\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Process run failed: " . $e->getMessage() . "\n");
}
