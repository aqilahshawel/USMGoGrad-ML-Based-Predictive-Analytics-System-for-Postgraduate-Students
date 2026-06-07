<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PaperRecommendationService
{
    /**
     * @return array<int, array{title:string,url:string,score?:float}>
     */
    public function recommendForStudent(User $student, int $limit = 3): array
    {
        $limit = max(1, min(10, $limit));

        $pythonBinary = (string) config('ml.paper_python_binary', config('ml.python_binary'));
        $scriptPath = (string) config('ml.paper_recommender_script');
        $catalogPath = (string) config('ml.paper_catalog_path');

        $cluster = (string) ($student->cluster ?? 'general');
        $researchTitle = trim((string) ($student->research_title ?? ''));
        $researchObjective = trim((string) ($student->research_objective ?? ''));

        if ($researchTitle !== '' && $researchObjective !== '') {
            $queryText = trim($researchTitle . ' Objective: ' . $researchObjective);
        } elseif ($researchTitle !== '') {
            $queryText = $researchTitle;
        } elseif ($researchObjective !== '') {
            $queryText = $researchObjective;
        } else {
            $queryText = sprintf('Postgraduate research topics for cluster %s', $cluster);
        }

        // Simple per-process throttle to respect Semantic Scholar rate limits.
        // For multi-worker deployments use a distributed limiter (Redis).
        $ssMinInterval = (float) (getenv('SEMANTIC_SCHOLAR_MIN_INTERVAL_SECONDS') ?: getenv('SEMANTIC_SCHOLAR_MIN_INTERVAL') ?: 1.0);
        $tempDir = sys_get_temp_dir();
        $lastTsFile = $tempDir . DIRECTORY_SEPARATOR . 'usmg_ss_last_request_ts';
        if (is_file($lastTsFile)) {
            $last = (float) @file_get_contents($lastTsFile);
            $elapsed = microtime(true) - $last;
            if ($elapsed < $ssMinInterval) {
                usleep((int)(($ssMinInterval - $elapsed) * 1e6));
            }
        }
        // record timestamp for this process
        @file_put_contents($lastTsFile, (string) microtime(true));

        $process = new Process([
            $pythonBinary,
            $scriptPath,
            '--catalog',
            $catalogPath,
            '--cluster',
            $cluster,
            '--query',
            $queryText,
            '--limit',
            (string) $limit,
        ], base_path(), $this->pythonProcessEnvironment());

        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Paper recommendation failed', [
                'student_id' => $student->id,
                'exit_code' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);
            return [];
        }

        $raw = trim($process->getOutput());
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            Log::warning('Paper recommendation returned invalid JSON', [
                'student_id' => $student->id,
                'output' => $raw,
            ]);
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            $url = (string) ($row['url'] ?? '');
            if ($title === '' || $url === '') {
                continue;
            }

            $item = ['title' => $title, 'url' => $url];
            if (isset($row['score']) && is_numeric($row['score'])) {
                $item['score'] = (float) $row['score'];
            }
            $items[] = $item;
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * @return array<string,string>
     */
    private function pythonProcessEnvironment(): array
    {
        $tempDir = sys_get_temp_dir();
        $systemRoot = getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: 'C:\\Windows';
        $comSpec = getenv('ComSpec') ?: getenv('COMSPEC') ?: $systemRoot . '\\System32\\cmd.exe';

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

        foreach (['PATH', 'Path', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'SEMANTIC_SCHOLAR_ENABLED', 'SEMANTIC_SCHOLAR_API_KEY', 'SEMANTIC_SCHOLAR_CACHE_PATH', 'SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS', 'SEMANTIC_SCHOLAR_SEARCH_LIMIT', 'SEMANTIC_SCHOLAR_FIELDS_OF_STUDY', 'SEMANTIC_SCHOLAR_MIN_INTERVAL_SECONDS'] as $key) {
            $value = getenv($key);
            if (is_string($value) && trim($value) !== '') {
                $environment[$key] = $value;
            }
        }

        return $environment;
    }
}
