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

        $pythonBinary = (string) config('ml.python_binary');
        $scriptPath = (string) config('ml.paper_recommender_script');
        $catalogPath = (string) config('ml.paper_catalog_path');

        $cluster = (string) ($student->cluster ?? 'general');
        $researchTitle = trim((string) ($student->research_title ?? ''));

        $queryText = $researchTitle !== ''
            ? $researchTitle
            : sprintf('Postgraduate research topics for cluster %s', $cluster);

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
        ]);

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
}
