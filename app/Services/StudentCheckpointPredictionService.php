<?php

namespace App\Services;

use App\Models\StudentCheckpoint;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class StudentCheckpointPredictionService
{
    private const CHECKPOINT_FIELDS = [
        'proposal_review_date',
        'research_review_date',
        'nos_date',
        'final_draft_submission_date',
        'viva_date',
    ];

    public function hasPredictiveCheckpointInput(?StudentCheckpoint $checkpoint): bool
    {
        if (! $checkpoint) {
            return false;
        }

        return $checkpoint->proposal_review_date !== null
            || $checkpoint->research_review_date !== null
            || $checkpoint->nos_date !== null
            || $checkpoint->final_draft_submission_date !== null
            || $checkpoint->viva_date !== null;
    }

    public function shouldRefreshOnProfileView(?StudentCheckpoint $checkpoint): bool
    {
        if (! (bool) config('ml.predict_on_profile_view', true)) {
            return false;
        }

        if (! $checkpoint) {
            return true;
        }

        if (is_null($checkpoint->got_probability) || $checkpoint->last_predicted_at === null) {
            return true;
        }

        if ($checkpoint->model_version === 'cold_start') {
            return true;
        }

        $cooldownSeconds = max(0, (int) config('ml.profile_refresh_cooldown_seconds', 0));
        if ($cooldownSeconds === 0) {
            return true;
        }

        return $checkpoint->last_predicted_at->diffInSeconds(now()) >= $cooldownSeconds;
    }

    /**
     * @return array{got_probability:?float,predicted_got:?bool,risk_level:string,model_version:string,recommended_checkpoint_dates:array<string,mixed>}
     */
    public function predictForStudent(User $student): array
    {
        $missing = $this->missingPrerequisites($student);
        if ($missing !== []) {
            throw new \InvalidArgumentException('Student is missing required fields: ' . implode(', ', $missing));
        }

        $checkpoint = $student->checkpoint ?: StudentCheckpoint::firstOrCreate(['user_id' => $student->id]);

        $payload = $this->buildPredictionPayload($student, $checkpoint, false);
        $decoded = $this->runPredictionProcess($payload, $student->id);
        $probability = round((float) Arr::get($decoded, 'got_probability', 0.0), 4);
        $predictedGot = (bool) Arr::get($decoded, 'predicted_got', false);
        $modelVersion = (string) Arr::get($decoded, 'model_version', 'unknown');
        $riskLevel = $this->riskLevelForProbability($probability);
        $normalizedLive = $this->normalizeRecommendedDates(
            $student,
            $checkpoint,
            (array) Arr::get($decoded, 'recommended_checkpoint_dates', []),
            (array) Arr::get($decoded, 'recommended_checkpoint_semesters', []),
            true
        );
        $recommendedDates = (array) Arr::get($normalizedLive, 'dates', []);

        $liveDiagnostics = array_merge(
            (array) Arr::get($decoded, 'recommendation_diagnostics', []),
            (array) Arr::get($normalizedLive, 'diagnostics', [])
        );

        $predictionInput = $payload;
        $predictionInput['model_recommended_checkpoint_semesters'] = (array) Arr::get($decoded, 'recommended_checkpoint_semesters', []);
        $predictionInput['normalized_recommended_checkpoint_semesters'] = (array) Arr::get($normalizedLive, 'semesters', []);
        $predictionInput['recommendation_diagnostics'] = $liveDiagnostics;

        $baselineFill = $this->buildBaselineRecommendationFill($student, $checkpoint, $modelVersion);

        $checkpoint->forceFill([
            'got_probability' => $probability,
            'predicted_got' => $predictedGot,
            'risk_level' => $riskLevel,
            'model_version' => $modelVersion,
            'last_predicted_at' => now(),
            'recommended_proposal_review_date' => $this->normalizeDateValue(Arr::get($recommendedDates, 'proposal_review_date')),
            'recommended_research_review_date' => $this->normalizeDateValue(Arr::get($recommendedDates, 'research_review_date')),
            'recommended_nos_date' => $this->normalizeDateValue(Arr::get($recommendedDates, 'nos_date')),
            'recommended_final_draft_submission_date' => $this->normalizeDateValue(Arr::get($recommendedDates, 'final_draft_submission_date')),
            'recommended_viva_date' => $this->normalizeDateValue(Arr::get($recommendedDates, 'viva_date')),
            'recommendation_generated_at' => now(),
            'prediction_input' => $predictionInput,
        ] + $baselineFill)->save();

        return [
            'got_probability' => $probability,
            'predicted_got' => $predictedGot,
            'risk_level' => $riskLevel,
            'model_version' => $modelVersion,
            'recommended_checkpoint_dates' => $recommendedDates,
        ];
    }

    private function buildPredictionPayload(User $student, StudentCheckpoint $checkpoint, bool $ignoreObserved): array
    {
        return [
            'Cluster' => (string) $student->cluster,
            'Type' => 'Full Time',
            'Registration Date' => optional($student->start_date)->format('Y-m-d'),
            'Proposal Review Date' => $ignoreObserved ? null : optional($checkpoint->proposal_review_date)->format('Y-m-d'),
            'Research Review Date' => $ignoreObserved ? null : optional($checkpoint->research_review_date)->format('Y-m-d'),
            'NOS Date' => $ignoreObserved ? null : optional($checkpoint->nos_date)->format('Y-m-d'),
            'Final Draft Submission Date' => $ignoreObserved ? null : optional($checkpoint->final_draft_submission_date)->format('Y-m-d'),
            'Viva Date' => $ignoreObserved ? null : optional($checkpoint->viva_date)->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function runPredictionProcess(array $payload, int $studentId): array
    {
        $process = new Process([
            (string) config('ml.got_python_binary', config('ml.python_binary')),
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
            $rawError = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Prediction process failed.';

            Log::error('GoT prediction process failed.', [
                'student_id' => $studentId,
                'python_binary' => (string) config('ml.python_binary'),
                'error' => $rawError,
            ]);

            throw new \RuntimeException($this->friendlyPredictionError($rawError));
        }

        return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string,mixed>
     */
    private function buildBaselineRecommendationFill(User $student, StudentCheckpoint $checkpoint, string $fallbackModelVersion): array
    {
        if (! $this->shouldGenerateBaselineRecommendation($checkpoint)) {
            return [];
        }

        try {
            $baselinePayload = $this->buildPredictionPayload($student, $checkpoint, true);
            $baselineDecoded = $this->runPredictionProcess($baselinePayload, $student->id);
            $baselineModelVersion = (string) Arr::get($baselineDecoded, 'model_version', $fallbackModelVersion);
            $normalizedBaseline = $this->normalizeRecommendedDates(
                $student,
                $checkpoint,
                (array) Arr::get($baselineDecoded, 'recommended_checkpoint_dates', []),
                (array) Arr::get($baselineDecoded, 'recommended_checkpoint_semesters', []),
                false
            );
            $baselineDates = (array) Arr::get($normalizedBaseline, 'dates', []);

            return [
                'baseline_recommended_proposal_review_date' => $this->normalizeDateValue(Arr::get($baselineDates, 'proposal_review_date')),
                'baseline_recommended_research_review_date' => $this->normalizeDateValue(Arr::get($baselineDates, 'research_review_date')),
                'baseline_recommended_nos_date' => $this->normalizeDateValue(Arr::get($baselineDates, 'nos_date')),
                'baseline_recommended_final_draft_submission_date' => $this->normalizeDateValue(Arr::get($baselineDates, 'final_draft_submission_date')),
                'baseline_recommended_viva_date' => $this->normalizeDateValue(Arr::get($baselineDates, 'viva_date')),
                'baseline_model_version' => $baselineModelVersion,
                'baseline_recommendation_generated_at' => now(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Baseline recommendation generation failed.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function shouldGenerateBaselineRecommendation(StudentCheckpoint $checkpoint): bool
    {
        return $checkpoint->baseline_recommendation_generated_at === null
            || $checkpoint->baseline_recommended_proposal_review_date === null
            || $checkpoint->baseline_recommended_research_review_date === null
            || $checkpoint->baseline_recommended_nos_date === null
            || $checkpoint->baseline_recommended_final_draft_submission_date === null
            || $checkpoint->baseline_recommended_viva_date === null;
    }

    /**
     * @param array<string,mixed> $recommendedDates
     * @param array<string,mixed> $recommendedSemesters
     * @return array{dates:array<string,mixed>,semesters:array<string,int>,diagnostics:array<string,mixed>}
     */
    private function normalizeRecommendedDates(
        User $student,
        StudentCheckpoint $checkpoint,
        array $recommendedDates,
        array $recommendedSemesters,
        bool $useObservedDates
    ): array
    {
        if (! $student->start_date) {
            return [
                'dates' => $recommendedDates,
                'semesters' => [],
                'diagnostics' => [],
            ];
        }

        try {
            /** @var CandidatureTimelineService $timelineService */
            $timelineService = app(CandidatureTimelineService::class);
            $mode = $student->study_mode ?: 'full_time';
            $rules = $timelineService->rulesForStudyMode($mode);
            $maxSemesters = max(1, (int) ($rules['max_semesters'] ?? 10));
            $registrationDate = CarbonImmutable::instance($student->start_date)->startOfDay();

            $semesterValues = [];
            $isFixed = [];
            $observedDates = [];

            $previousSemester = 1;
            foreach (self::CHECKPOINT_FIELDS as $field) {
                $observed = $checkpoint->{$field};
                if ($useObservedDates && $observed !== null) {
                    $observedDate = $observed instanceof CarbonInterface
                        ? CarbonImmutable::instance($observed)->startOfDay()
                        : CarbonImmutable::parse((string) $observed)->startOfDay();

                    $semester = $this->relativeSemesterFromDate($timelineService, $registrationDate, $observedDate);
                    $isFixed[$field] = true;
                    $observedDates[$field] = $observedDate->toDateString();
                } else {
                    $semester = $this->extractRecommendedSemester(
                        $field,
                        $recommendedSemesters,
                        $recommendedDates,
                        $timelineService,
                        $registrationDate,
                        $previousSemester
                    );

                    $isFixed[$field] = false;
                    $observedDates[$field] = null;
                }

                $semester = max(1, $previousSemester, $semester);
                $semesterValues[$field] = $semester;
                $previousSemester = $semester;
            }

            $lastField = self::CHECKPOINT_FIELDS[count(self::CHECKPOINT_FIELDS) - 1];
            $overflow = max(0, $semesterValues[$lastField] - $maxSemesters);
            $compressedByMaxSemester = $overflow > 0;

            if ($overflow > 0) {
                foreach (self::CHECKPOINT_FIELDS as $field) {
                    if ($isFixed[$field]) {
                        continue;
                    }

                    $semesterValues[$field] = max(1, $semesterValues[$field] - $overflow);
                }

                for ($i = 1; $i < count(self::CHECKPOINT_FIELDS); $i++) {
                    $field = self::CHECKPOINT_FIELDS[$i];
                    $previousField = self::CHECKPOINT_FIELDS[$i - 1];
                    if ($semesterValues[$field] < $semesterValues[$previousField]) {
                        $semesterValues[$field] = $semesterValues[$previousField];
                    }
                }

                $overflow = max(0, $semesterValues[$lastField] - $maxSemesters);
            }

            // Shift adjustable semesters backward first to fit max candidature while preserving order.
            for ($i = count(self::CHECKPOINT_FIELDS) - 1; $i >= 0 && $overflow > 0; $i--) {
                $field = self::CHECKPOINT_FIELDS[$i];
                if ($isFixed[$field]) {
                    continue;
                }

                $previousField = $i > 0 ? self::CHECKPOINT_FIELDS[$i - 1] : null;
                $minimumAllowed = $previousField ? $semesterValues[$previousField] : 1;
                $reducible = max(0, $semesterValues[$field] - $minimumAllowed);
                if ($reducible === 0) {
                    continue;
                }

                $delta = min($reducible, $overflow);
                $semesterValues[$field] -= $delta;
                $overflow -= $delta;
            }

            for ($i = 1; $i < count(self::CHECKPOINT_FIELDS); $i++) {
                $field = self::CHECKPOINT_FIELDS[$i];
                $previousField = self::CHECKPOINT_FIELDS[$i - 1];
                if ($semesterValues[$field] < $semesterValues[$previousField]) {
                    $semesterValues[$field] = $semesterValues[$previousField];
                }
            }

            $normalizedDates = [];
            $fieldsBySemester = [];

            foreach (self::CHECKPOINT_FIELDS as $field) {
                if ($useObservedDates && $observedDates[$field] !== null) {
                    $normalizedDates[$field] = $observedDates[$field];
                    continue;
                }

                $relativeSemester = (int) $semesterValues[$field];
                if ($relativeSemester > $maxSemesters) {
                    $relativeSemester = $maxSemesters;
                }

                $fieldsBySemester[$relativeSemester][] = $field;
            }

            foreach ($fieldsBySemester as $relativeSemester => $fields) {
                $period = $this->semesterPeriodForRelativeIndex($timelineService, $registrationDate, (int) $relativeSemester);
                $count = count($fields);

                foreach ($fields as $index => $field) {
                    $distributedDate = $this->distributedDateWithinSemester(
                        $period['start'],
                        $period['end'],
                        $index,
                        $count
                    );

                    $normalizedDates[$field] = $distributedDate->toDateString();
                }
            }

            $previousDate = null;
            foreach (self::CHECKPOINT_FIELDS as $field) {
                $value = $normalizedDates[$field] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    continue;
                }

                $currentDate = CarbonImmutable::parse($value)->startOfDay();
                if ($previousDate instanceof CarbonImmutable && $currentDate->lt($previousDate)) {
                    $currentDate = $previousDate;
                    $normalizedDates[$field] = $currentDate->toDateString();
                }

                $previousDate = $currentDate;
            }

            $normalizedSemesters = [];
            foreach (self::CHECKPOINT_FIELDS as $field) {
                if (isset($semesterValues[$field])) {
                    $normalizedSemesters[$field] = (int) $semesterValues[$field];
                }
            }

            return [
                'dates' => $normalizedDates,
                'semesters' => $normalizedSemesters,
                'diagnostics' => [
                    'compressed_by_max_semester' => $compressedByMaxSemester,
                    'same_semester_groups' => $this->sameSemesterGroups($normalizedSemesters),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'dates' => $recommendedDates,
                'semesters' => [],
                'diagnostics' => [],
            ];
        }
    }

    private function extractRecommendedSemester(
        string $field,
        array $recommendedSemesters,
        array $recommendedDates,
        CandidatureTimelineService $timelineService,
        CarbonImmutable $registrationDate,
        int $minimum
    ): int
    {
        $semesterKey = match ($field) {
            'proposal_review_date' => 'proposal_review_sem',
            'research_review_date' => 'research_review_sem',
            'nos_date' => 'nos_sem',
            'final_draft_submission_date' => 'final_draft_sem',
            'viva_date' => 'viva_sem',
            default => null,
        };

        if ($semesterKey && isset($recommendedSemesters[$semesterKey]) && is_numeric($recommendedSemesters[$semesterKey])) {
            return max($minimum, (int) round((float) $recommendedSemesters[$semesterKey]));
        }

        $dateValue = Arr::get($recommendedDates, $field);
        if (! is_string($dateValue) || trim($dateValue) === '') {
            return $minimum;
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', trim($dateValue));
        } catch (\Throwable $e) {
            try {
                $date = CarbonImmutable::parse((string) $dateValue);
            } catch (\Throwable $e) {
                return $minimum;
            }
        }

        return max($minimum, $this->relativeSemesterFromDate($timelineService, $registrationDate, $date->startOfDay()));
    }

    /**
     * @return array{start:CarbonImmutable,end:CarbonImmutable}
     */
    private function semesterPeriodForRelativeIndex(
        CandidatureTimelineService $timelineService,
        CarbonImmutable $registrationDate,
        int $relativeSemester
    ): array
    {
        if ($relativeSemester < 1) {
            $relativeSemester = 1;
        }

        $period = $timelineService->semesterPeriodForDate($registrationDate);
        for ($i = 1; $i < $relativeSemester; $i++) {
            $period = $timelineService->semesterPeriodForDate($period['end']->addDay());
        }

        return [
            'start' => CarbonImmutable::instance($period['start'])->startOfDay(),
            'end' => CarbonImmutable::instance($period['end'])->startOfDay(),
        ];
    }

    private function distributedDateWithinSemester(
        CarbonImmutable $semesterStart,
        CarbonImmutable $semesterEnd,
        int $index,
        int $count
    ): CarbonImmutable
    {
        $fractionsByCount = [
            1 => [0.65],
            2 => [0.45, 0.82],
            3 => [0.28, 0.56, 0.84],
            4 => [0.20, 0.44, 0.66, 0.86],
        ];

        if ($count <= 0) {
            return $semesterEnd;
        }

        if (isset($fractionsByCount[$count])) {
            $fractions = $fractionsByCount[$count];
            $fraction = $fractions[min($index, $count - 1)];
        } else {
            $position = min($index + 1, $count);
            $fraction = $position / ($count + 1);
        }

        $totalDays = max(1, $semesterStart->diffInDays($semesterEnd));
        $offset = (int) round($totalDays * $fraction);
        $candidate = $semesterStart->addDays($offset);

        if ($candidate->gt($semesterEnd)) {
            return $semesterEnd;
        }

        return $candidate;
    }

    /**
     * @param array<string,int> $semesterValues
     * @return array<int,array{semester:int,checkpoints:array<int,string>}>
     */
    private function sameSemesterGroups(array $semesterValues): array
    {
        $groups = [];

        foreach ($semesterValues as $field => $semester) {
            $groups[$semester][] = $this->checkpointLabel($field);
        }

        ksort($groups);

        $result = [];
        foreach ($groups as $semester => $labels) {
            if (count($labels) <= 1) {
                continue;
            }

            $result[] = [
                'semester' => (int) $semester,
                'checkpoints' => array_values($labels),
            ];
        }

        return $result;
    }

    private function checkpointLabel(string $field): string
    {
        return match ($field) {
            'proposal_review_date' => 'Proposal Review',
            'research_review_date' => 'Research Review',
            'nos_date' => 'NOS',
            'final_draft_submission_date' => 'Final Draft Submission',
            'viva_date' => 'Viva',
            default => $field,
        };
    }

    private function relativeSemesterFromDate(
        CandidatureTimelineService $timelineService,
        CarbonImmutable $registrationDate,
        CarbonImmutable $targetDate
    ): int
    {
        if ($targetDate->lt($registrationDate)) {
            return 1;
        }

        $period = $timelineService->semesterPeriodForDate($registrationDate);
        $semester = 1;

        while ($targetDate->gt($period['end'])) {
            $semester++;
            $period = $timelineService->semesterPeriodForDate($period['end']->addDay());

            if ($semester > 200) {
                break;
            }
        }

        return max(1, $semester);
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    private function friendlyPredictionError(string $rawError): string
    {
        if (preg_match('/No module named [\"\']([^\"\']+)[\"\']/', $rawError, $matches) === 1) {
            $module = $matches[1];

            return "ML prediction is unavailable because a Python dependency is missing ({$module}). Please install the ML requirements for the configured Python interpreter.";
        }

        if (str_contains(strtolower($rawError), 'no such file or directory')
            || str_contains(strtolower($rawError), 'is not recognized as an internal or external command')) {
            return 'ML prediction is unavailable because the configured Python executable cannot be found. Please verify ML_PYTHON_BINARY.';
        }

        return 'ML prediction could not be refreshed right now. Please try again later or contact an administrator.';
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

        return $missing;
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
}