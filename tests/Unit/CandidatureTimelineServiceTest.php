<?php

namespace Tests\Unit;

use App\Enums\StudyMode;
use App\Services\CandidatureTimelineService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CandidatureTimelineServiceTest extends TestCase
{
    public function test_semester_period_mapping_for_known_boundaries(): void
    {
        $service = new CandidatureTimelineService();

        // Semester 1: Oct–Feb
        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2025-10-01'));
        $this->assertSame('S1', $p['key']);
        $this->assertSame('2025-10-01', $p['start']->toDateString());
        $this->assertSame('2026-02-28', $p['end']->toDateString());

        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2026-02-15'));
        $this->assertSame('S1', $p['key']);
        $this->assertSame('2025-10-01', $p['start']->toDateString());
        $this->assertSame('2026-02-28', $p['end']->toDateString());

        // Semester 2: Mar–Sep
        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2026-03-01'));
        $this->assertSame('S2', $p['key']);
        $this->assertSame('2026-03-01', $p['start']->toDateString());
        $this->assertSame('2026-09-30', $p['end']->toDateString());
    }

    public function test_current_semester_number_increments_across_periods(): void
    {
        $service = new CandidatureTimelineService();
        $start = CarbonImmutable::parse('2025-10-01');

        $this->assertSame(1, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2025-10-15')));
        $this->assertSame(1, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-02-28')));
        $this->assertSame(2, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-03-01')));
        $this->assertSame(2, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-09-30')));
        $this->assertSame(3, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-10-01')));
    }
}
