<?php

namespace Tests\Unit;

use App\Enums\StudyMode;
use App\Models\StudentCheckpoint;
use App\Services\CandidatureTimelineService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CandidatureTimelineServiceTest extends TestCase
{
    public function test_semester_period_mapping_for_known_boundaries(): void
    {
        $service = new CandidatureTimelineService();

        // Semester 1: Sep-Feb
        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2025-09-01'));
        $this->assertSame('S1', $p['key']);
        $this->assertSame('2025-09-01', $p['start']->toDateString());
        $this->assertSame('2026-02-28', $p['end']->toDateString());

        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2026-02-15'));
        $this->assertSame('S1', $p['key']);
        $this->assertSame('2025-09-01', $p['start']->toDateString());
        $this->assertSame('2026-02-28', $p['end']->toDateString());

        // Semester 2: Mar-Aug
        $p = $service->semesterPeriodForDate(CarbonImmutable::parse('2026-03-01'));
        $this->assertSame('S2', $p['key']);
        $this->assertSame('2026-03-01', $p['start']->toDateString());
        $this->assertSame('2026-08-31', $p['end']->toDateString());
    }

    public function test_current_semester_number_increments_across_periods(): void
    {
        $service = new CandidatureTimelineService();
        $start = CarbonImmutable::parse('2025-09-01');

        $this->assertSame(1, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2025-09-15')));
        $this->assertSame(1, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-02-28')));
        $this->assertSame(2, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-03-01')));
        $this->assertSame(2, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-08-31')));
        $this->assertSame(3, $service->currentSemesterNumber($start, StudyMode::FullTime, CarbonImmutable::parse('2026-09-01')));
    }

    public function test_progress_snapshot_marks_pending_when_student_has_no_checkpoint_evidence(): void
    {
        $service = new CandidatureTimelineService();

        $snapshot = $service->progressSnapshot(
            CarbonImmutable::parse('2025-09-01'),
            StudyMode::FullTime,
            CarbonImmutable::parse('2025-10-10'),
            null
        );

        $this->assertSame('pending', $snapshot['risk_status']);
        $this->assertTrue($snapshot['show_ml_pending_badge']);
        $this->assertFalse($snapshot['has_checkpoint_evidence']);
    }

    public function test_progress_snapshot_escalates_to_at_risk_for_late_completed_checkpoint(): void
    {
        $service = new CandidatureTimelineService();
        $checkpoint = new StudentCheckpoint([
            'proposal_review_date' => CarbonImmutable::parse('2027-04-01'),
        ]);

        $snapshot = $service->progressSnapshot(
            CarbonImmutable::parse('2025-09-01'),
            StudyMode::FullTime,
            CarbonImmutable::parse('2027-04-01'),
            $checkpoint
        );

        $this->assertSame('at_risk', $snapshot['risk_status']);
        $this->assertTrue($snapshot['has_checkpoint_delay']);
        $this->assertSame(1, $snapshot['late_completed_count']);
    }

    public function test_progress_snapshot_escalates_to_high_risk_when_ml_risk_is_high(): void
    {
        $service = new CandidatureTimelineService();
        $checkpoint = new StudentCheckpoint([
            'proposal_review_date' => CarbonImmutable::parse('2025-10-01'),
            'risk_level' => 'high',
        ]);

        $snapshot = $service->progressSnapshot(
            CarbonImmutable::parse('2025-09-01'),
            StudyMode::FullTime,
            CarbonImmutable::parse('2025-10-15'),
            $checkpoint
        );

        $this->assertSame('high_risk', $snapshot['risk_status']);
        $this->assertSame('high', $snapshot['ml_state']);
    }

    public function test_progress_snapshot_keeps_overdue_as_highest_precedence_state(): void
    {
        $service = new CandidatureTimelineService();

        $snapshot = $service->progressSnapshot(
            CarbonImmutable::parse('2025-09-01'),
            StudyMode::FullTime,
            CarbonImmutable::parse('2031-01-10'),
            null
        );

        $this->assertSame('overdue', $snapshot['risk_status']);
        $this->assertTrue($snapshot['show_ml_pending_badge']);
    }
}
