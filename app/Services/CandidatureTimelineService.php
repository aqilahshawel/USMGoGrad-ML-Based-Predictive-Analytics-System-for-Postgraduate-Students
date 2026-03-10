<?php

namespace App\Services;

use App\Enums\CandidatureRiskStatus;
use App\Enums\StudyMode;
use App\Models\Timeline;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class CandidatureTimelineService
{
    /**
     * Returns the academic semester period (Oct–Feb or Mar–Sep) that contains $date.
     *
     * @return array{key:string,start:CarbonImmutable,end:CarbonImmutable,label:string}
     */
    public function semesterPeriodForDate(CarbonInterface $date): array
    {
        $d = CarbonImmutable::instance($date)->startOfDay();
        $year = (int) $d->year;
        $month = (int) $d->month;

        // Semester 1: Oct–Feb (spans year boundary)
        if ($month >= 10) {
            $start = CarbonImmutable::create($year, 10, 1);
            $end = CarbonImmutable::create($year + 1, 2, 1)->endOfMonth();
            $label = sprintf('S1 %d/%d', $year, $year + 1);

            return ['key' => 'S1', 'start' => $start, 'end' => $end, 'label' => $label];
        }

        if ($month <= 2) {
            $start = CarbonImmutable::create($year - 1, 10, 1);
            $end = CarbonImmutable::create($year, 2, 1)->endOfMonth();
            $label = sprintf('S1 %d/%d', $year - 1, $year);

            return ['key' => 'S1', 'start' => $start, 'end' => $end, 'label' => $label];
        }

        // Semester 2: Mar–Sep (same year)
        $start = CarbonImmutable::create($year, 3, 1);
        $end = CarbonImmutable::create($year, 9, 30);
        $label = sprintf('S2 %d', $year);

        return ['key' => 'S2', 'start' => $start, 'end' => $end, 'label' => $label];
    }

    /**
     * @return array{min_semesters:int,expected_semesters:int,max_semesters:int,stages:array<int,array<string,mixed>>}
     */
    public function rulesForStudyMode(StudyMode|string $studyMode): array
    {
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);

        $rules = config('candidature_timeline.study_modes.' . $mode->value);
        if (! is_array($rules)) {
            throw new \RuntimeException('Missing config for study mode: ' . $mode->value);
        }

        foreach (['min_semesters', 'expected_semesters', 'max_semesters', 'stages'] as $key) {
            if (! array_key_exists($key, $rules)) {
                throw new \RuntimeException("candidature_timeline missing key: {$key} for {$mode->value}");
            }
        }

        return [
            'min_semesters' => (int) $rules['min_semesters'],
            'expected_semesters' => (int) $rules['expected_semesters'],
            'max_semesters' => (int) $rules['max_semesters'],
            'stages' => (array) $rules['stages'],
        ];
    }

    public function riskStatusForSemester(int $semesterNumber, StudyMode|string $studyMode): CandidatureRiskStatus
    {
        $rules = $this->rulesForStudyMode($studyMode);

        if ($semesterNumber <= $rules['expected_semesters']) {
            return CandidatureRiskStatus::OnTrack;
        }

        if ($semesterNumber <= $rules['max_semesters']) {
            return CandidatureRiskStatus::AtRisk;
        }

        return CandidatureRiskStatus::Overdue;
    }

    /**
     * Returns the configured stage that contains a semester number.
     *
     * @return array{key:string,title:string,from:int,to:int}|null
     */
    public function stageForSemester(int $semesterNumber, StudyMode|string $studyMode): ?array
    {
        $rules = $this->rulesForStudyMode($studyMode);
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);

        foreach ($rules['stages'] as $stage) {
            $from = (int) ($stage['from'] ?? 0);
            $to = (int) ($stage['to'] ?? 0);

            if ($semesterNumber >= $from && $semesterNumber <= $to) {
                return [
                    'key' => (string) $stage['key'],
                    'title' => (string) $stage['title'],
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        $risk = $this->riskStatusForSemester($semesterNumber, $mode);
        if ($risk === CandidatureRiskStatus::AtRisk) {
            return [
                'key' => 'extension',
                'title' => 'At-risk period (extension)',
                'from' => (int) $rules['expected_semesters'] + 1,
                'to' => (int) $rules['max_semesters'],
            ];
        }

        if ($risk === CandidatureRiskStatus::Overdue) {
            return [
                'key' => 'overdue',
                'title' => 'Overdue / critical risk',
                'from' => (int) $rules['max_semesters'] + 1,
                'to' => PHP_INT_MAX,
            ];
        }

        return null;
    }

    /**
     * Builds academic semesters (with date ranges) up to maximum duration.
     *
     * @return array<int,array{semester_number:int,semester_key:string,semester_label:string,start:CarbonImmutable,end:CarbonImmutable,stage_key:string|null,stage_title:string|null,risk_status:string}>
     */
    public function buildSemesterTimeline(CarbonInterface $startDate, StudyMode|string $studyMode): array
    {
        $rules = $this->rulesForStudyMode($studyMode);
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);

        $first = $this->semesterPeriodForDate($startDate);
        $periodKey = $first['key'];
        $periodStart = $first['start'];
        $periodEnd = $first['end'];
        $periodLabel = $first['label'];

        $timeline = [];

        for ($n = 1; $n <= $rules['max_semesters']; $n++) {
            $stage = $this->stageForSemester($n, $mode);
            $risk = $this->riskStatusForSemester($n, $mode);

            $timeline[] = [
                'semester_number' => $n,
                'semester_key' => $periodKey,
                'semester_label' => $periodLabel,
                'start' => $periodStart,
                'end' => $periodEnd,
                'stage_key' => $stage['key'] ?? null,
                'stage_title' => $stage['title'] ?? null,
                'risk_status' => $risk->value,
            ];

            $nextAnchor = $periodEnd->addDay();
            $next = $this->semesterPeriodForDate($nextAnchor);
            $periodKey = $next['key'];
            $periodStart = $next['start'];
            $periodEnd = $next['end'];
            $periodLabel = $next['label'];
        }

        return $timeline;
    }

    /**
     * Calculates the current semester number relative to a student's start date.
     * Returns 0 if today is before start_date.
     */
    public function currentSemesterNumber(CarbonInterface $startDate, StudyMode|string $studyMode, ?CarbonInterface $today = null): int
    {
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);
        $rules = $this->rulesForStudyMode($mode);

        $todayDate = CarbonImmutable::instance($today ?? CarbonImmutable::today())->startOfDay();
        $startDate = CarbonImmutable::instance($startDate)->startOfDay();

        if ($todayDate->lt($startDate)) {
            return 0;
        }

        $period = $this->semesterPeriodForDate($startDate);
        $count = 1;

        while ($todayDate->gt($period['end'])) {
            $count++;
            $period = $this->semesterPeriodForDate($period['end']->addDay());

            // Safety guard against accidental infinite loops
            if ($count > ($rules['max_semesters'] + 50)) {
                break;
            }
        }

        return $count;
    }

    /**
     * @return array{current_semester:int,stage_key:string|null,stage_title:string|null,risk_status:string,expected_semesters:int,max_semesters:int}
     */
    public function progressSnapshot(CarbonInterface $startDate, StudyMode|string $studyMode, ?CarbonInterface $today = null): array
    {
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);
        $rules = $this->rulesForStudyMode($mode);

        $currentSemester = $this->currentSemesterNumber($startDate, $mode, $today);

        $risk = $this->riskStatusForSemester($currentSemester === 0 ? 1 : $currentSemester, $mode);
        if ($currentSemester === 0) {
            $risk = CandidatureRiskStatus::OnTrack;
        }

        $stage = $currentSemester > 0 ? $this->stageForSemester($currentSemester, $mode) : null;

        return [
            'current_semester' => $currentSemester,
            'stage_key' => $stage['key'] ?? null,
            'stage_title' => $stage['title'] ?? null,
            'risk_status' => $risk->value,
            'expected_semesters' => $rules['expected_semesters'],
            'max_semesters' => $rules['max_semesters'],
        ];
    }

    /**
     * Generates default professional checkpoints (one per stage + max duration marker).
     * Stored in the Timeline roadmap (NOT the student's personal milestones).
     *
     * @return array<int,array{system_key:string,stage_key:string,title:string,description:string|null,event_date:CarbonImmutable,target_semester_number:int}>
     */
    public function defaultProfessionalCheckpoints(CarbonInterface $startDate, StudyMode|string $studyMode): array
    {
        $mode = $studyMode instanceof StudyMode ? $studyMode : StudyMode::fromMixed($studyMode);
        $rules = $this->rulesForStudyMode($mode);
        $timeline = $this->buildSemesterTimeline($startDate, $mode);

        $checkpoints = [];

        foreach ($rules['stages'] as $stage) {
            $to = (int) $stage['to'];
            $dueSemester = Arr::first($timeline, fn ($s) => (int) $s['semester_number'] === $to);
            if (! $dueSemester) {
                continue;
            }

            $checkpoints[] = [
                'system_key' => 'stage:' . $stage['key'],
                'stage_key' => (string) $stage['key'],
                'title' => (string) ($stage['milestone_title'] ?? $stage['title']),
                'description' => (string) ($stage['title'] ?? null),
                'event_date' => $dueSemester['end'],
                'target_semester_number' => $to,
            ];
        }

        $maxSemester = Arr::last($timeline);
        if ($maxSemester) {
            $checkpoints[] = [
                'system_key' => 'max_duration',
                'stage_key' => 'max_duration',
                'title' => 'Maximum candidature duration',
                'description' => 'Crossing this date is considered overdue / critical risk.',
                'event_date' => $maxSemester['end'],
                'target_semester_number' => (int) $maxSemester['semester_number'],
            ];
        }

        return $checkpoints;
    }

    /**
        * Persists (upserts) the generated semesters + professional checkpoints into `timelines`.
        * Existing manual records are untouched. Generator-locked rows are never overwritten.
     */
    public function syncForStudent(User $student, ?CarbonInterface $today = null): array
    {
        if (! $student->start_date || ! $student->study_mode) {
            throw new \InvalidArgumentException('Student must have start_date and study_mode set to generate timeline.');
        }

        $mode = StudyMode::fromMixed((string) $student->study_mode);
        $startDate = $student->start_date instanceof CarbonInterface
            ? $student->start_date
            : CarbonImmutable::parse((string) $student->start_date);

        $timeline = $this->buildSemesterTimeline($startDate, $mode);
        $todayDate = CarbonImmutable::instance($today ?? CarbonImmutable::today())->startOfDay();

        foreach ($timeline as $semester) {
            $semesterNumber = (int) $semester['semester_number'];
            $systemKey = 'semester:' . $semesterNumber;

            $existing = Timeline::query()
                ->where('user_id', $student->id)
                ->where('system_key', $systemKey)
                ->first();

            if ($existing && $existing->is_generator_locked) {
                continue;
            }

            $title = sprintf(
                'Semester %d (%s) — %s',
                $semesterNumber,
                $semester['semester_label'],
                $semester['stage_title'] ?? 'Progress'
            );

            Timeline::updateOrCreate(
                ['user_id' => $student->id, 'system_key' => $systemKey],
                [
                    'title' => $title,
                    'event_date' => $semester['end']->toDateString(),
                    'completed' => $todayDate->gt($semester['end']),
                    'is_system_generated' => true,
                    'status' => 'approved',
                    'semester_number' => $semesterNumber,
                    'semester_start' => $semester['start']->toDateString(),
                    'semester_end' => $semester['end']->toDateString(),
                    'stage_key' => $semester['stage_key'],
                    'risk_status' => $semester['risk_status'],
                ]
            );
        }

        $checkpoints = $this->defaultProfessionalCheckpoints($startDate, $mode);
        foreach ($checkpoints as $checkpoint) {
            $systemKey = $checkpoint['system_key'];

            $existing = Timeline::query()
                ->where('user_id', $student->id)
                ->where('system_key', $systemKey)
                ->first();

            if ($existing && $existing->is_generator_locked) {
                continue;
            }

            Timeline::updateOrCreate(
                ['user_id' => $student->id, 'system_key' => $systemKey],
                [
                    'title' => $checkpoint['title'],
                    'event_date' => $checkpoint['event_date']->toDateString(),
                    'completed' => $todayDate->gt($checkpoint['event_date']),
                    'is_system_generated' => true,
                    'status' => 'approved',
                    'semester_number' => (int) $checkpoint['target_semester_number'],
                    'stage_key' => $checkpoint['stage_key'],
                ]
            );
        }

        return $this->progressSnapshot($startDate, $mode, $todayDate);
    }
}
