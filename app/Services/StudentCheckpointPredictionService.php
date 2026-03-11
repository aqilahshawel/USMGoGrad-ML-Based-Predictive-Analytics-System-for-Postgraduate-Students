<?php

namespace App\Services;

use App\Models\StudentCheckpoint;
use App\Models\User;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;

class StudentCheckpointPredictionService
{
    /**
     * @return array{got_probability:?float,predicted_got:?bool,risk_level:string,model_version:string}
     */
    public function predictForStudent(User $student): array
    {
        $missing = $this->missingPrerequisites($student);
        if ($missing !== []) {
            throw new \InvalidArgumentException('Student is missing required fields: ' . implode(', ', $missing));
        }

        $checkpoint = $student->checkpoint ?: StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);

        $payload = [
            'Cluster' => (string) $student->cluster,
            'Type' => $this->typeLabel((string) $student->study_mode),
            'Registration Date' => optional($student->start_date)->format('Y-m-d'),
            'Proposal Review Date' => optional($checkpoint->proposal_review_date)->format('Y-m-d'),
            'Research Review Date' => optional($checkpoint->research_review_date)->format('Y-m-d'),
            'NOS Date' => optional($checkpoint->nos_date)->format('Y-m-d'),
            'Final Draft Submission Date' => optional($checkpoint->final_draft_submission_date)->format('Y-m-d'),
            'Viva Date' => optional($checkpoint->viva_date)->format('Y-m-d'),
        ];

        if (! $this->hasCheckpointData($checkpoint)) {
            $checkpoint->forceFill([
                'got_probability' => null,
                'predicted_got' => null,
                'risk_level' => 'pending',
                'model_version' => 'cold_start',
                'last_predicted_at' => now(),
                'prediction_input' => $payload,
            ])->save();

            return [
                'got_probability' => null,
                'predicted_got' => null,
                'risk_level' => 'pending',
                'model_version' => 'cold_start',
            ];
        }

        $process = new Process([
            (string) config('ml.python_binary'),
            (string) config('ml.predict_script'),
            '--model',
            (string) config('ml.model_path'),
            '--meta',
            (string) config('ml.meta_path'),
        ], base_path());

        $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR));
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Prediction process failed.';
            throw new \RuntimeException($message);
        }

        $decoded = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $probability = round((float) Arr::get($decoded, 'got_probability', 0.0), 4);
        $predictedGot = (bool) Arr::get($decoded, 'predicted_got', false);
        $modelVersion = (string) Arr::get($decoded, 'model_version', 'unknown');
        $riskLevel = $this->riskLevelForProbability($probability);

        $checkpoint->forceFill([
            'got_probability' => $probability,
            'predicted_got' => $predictedGot,
            'risk_level' => $riskLevel,
            'model_version' => $modelVersion,
            'last_predicted_at' => now(),
            'prediction_input' => $payload,
        ])->save();

        return [
            'got_probability' => $probability,
            'predicted_got' => $predictedGot,
            'risk_level' => $riskLevel,
            'model_version' => $modelVersion,
        ];
    }

    /**
     * @return array<int,string>
     */
    public function missingPrerequisites(User $student): array
    {
        $missing = [];

        if (! $student->cluster) {
            $missing[] = 'cluster';
        }

        if (! $student->start_date) {
            $missing[] = 'start_date';
        }

        if (! $student->study_mode) {
            $missing[] = 'study_mode';
        }

        return $missing;
    }

    private function typeLabel(string $studyMode): string
    {
        return match (trim(strtolower($studyMode))) {
            'full_time', 'full-time', 'full time' => 'Full Time',
            'part_time', 'part-time', 'part time' => 'Part Time',
            default => throw new \InvalidArgumentException('Unsupported study mode: ' . $studyMode),
        };
    }

    private function riskLevelForProbability(float $probability): string
    {
        if ($probability >= (float) config('ml.low_risk_threshold')) {
            return 'low';
        }

        if ($probability >= (float) config('ml.medium_risk_threshold')) {
            return 'medium';
        }

        return 'high';
    }

    private function hasCheckpointData(StudentCheckpoint $checkpoint): bool
    {
        return $checkpoint->proposal_review_date !== null
            || $checkpoint->research_review_date !== null
            || $checkpoint->nos_date !== null
            || $checkpoint->final_draft_submission_date !== null
            || $checkpoint->viva_date !== null;
    }
}