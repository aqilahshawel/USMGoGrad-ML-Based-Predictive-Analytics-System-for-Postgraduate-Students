<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Services\CandidatureTimelineService;
use App\Services\MeetingRequestService;
use App\Services\OverdueCheckpointNotificationService;
use App\Services\StudentCheckpointPredictionService;
use App\Services\PaperRecommendationService;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('candidature:sync-timeline {studentId? : Student user id} {--all : Sync for all students}', function () {
    /** @var ClosureCommand $this */
    $service = app(CandidatureTimelineService::class);

    $studentId = $this->argument('studentId');
    $all = (bool) $this->option('all');

    if (! $all && ! $studentId) {
        $this->error('Provide a studentId or use --all');
        return self::FAILURE;
    }

    $query = User::role('student');
    if (! $all) {
        $query->whereKey($studentId);
    }

    $students = $query->get();
    if ($students->isEmpty()) {
        $this->error('No matching student(s) found.');
        return self::FAILURE;
    }

    $synced = 0;
    $skipped = 0;

    foreach ($students as $student) {
        try {
            $snapshot = $service->syncForStudent($student);
            $synced++;
            $this->line(sprintf(
                'Synced student %d: semester=%d, risk=%s',
                $student->id,
                $snapshot['current_semester'],
                $snapshot['risk_status']
            ));
        } catch (\Throwable $e) {
            $skipped++;
            $this->warn(sprintf('Skipped student %d: %s', $student->id, $e->getMessage()));
        }
    }

    $this->info("Done. Synced={$synced}, skipped={$skipped}");

    return self::SUCCESS;
})->purpose('Generate/update default candidature timeline checkpoints');

Artisan::command('ml:predict-got {studentId? : Student user id} {--all : Predict for all students}', function () {
    /** @var ClosureCommand $this */
    $service = app(StudentCheckpointPredictionService::class);
    $timelineService = app(CandidatureTimelineService::class);

    $studentId = $this->argument('studentId');
    $all = (bool) $this->option('all');

    if (! $all && ! $studentId) {
        $this->error('Provide a studentId or use --all');
        return self::FAILURE;
    }

    $query = User::role('student');
    if (! $all) {
        $query->whereKey($studentId);
    } else {
        $query
            ->whereNotNull('cluster')
            ->whereNotNull('start_date');
    }

    $students = $query->get();
    if ($students->isEmpty()) {
        $this->error('No matching student(s) found.');
        return self::FAILURE;
    }

    $predicted = 0;
    $skipped = 0;

    foreach ($students as $student) {
        try {
            $student = $student->fresh('checkpoint');
            $missingPrerequisites = $service->missingPrerequisites($student);
            if ($missingPrerequisites !== []) {
                $skipped++;
                $this->line(sprintf(
                    'Skipped student %d: missing prerequisites (%s).',
                    $student->id,
                    implode(', ', $missingPrerequisites)
                ));
                continue;
            }

            $result = $service->predictForStudent($student);

            if ($student->start_date) {
                $timelineService->syncForStudent($student->fresh('checkpoint'));
            }

            $predicted++;

            $recommendedProposal = $result['recommended_checkpoint_dates']['proposal_review_date'] ?? 'n/a';

            $this->line(sprintf(
                'Predicted student %d: probability=%.4f, label=%s, risk=%s, proposal_target=%s',
                $student->id,
                $result['got_probability'],
                $result['predicted_got'] ? 'GoT' : 'Not GoT',
                $result['risk_level'],
                $recommendedProposal
            ));
        } catch (\Throwable $e) {
            $skipped++;
            $this->warn(sprintf('Skipped student %d: %s', $student->id, $e->getMessage()));
        }
    }

    $this->info("Done. Predicted={$predicted}, skipped={$skipped}");

    return self::SUCCESS;
})->purpose('Run GoT ML predictions for student checkpoints');

Artisan::command('assistant:dispatch-reminders {studentId? : Student user id} {--all : Send reminders for all students}', function () {
    /** @var ClosureCommand $this */
    $service = app(OverdueCheckpointNotificationService::class);

    $studentId = $this->argument('studentId');
    $all = (bool) $this->option('all');

    if (! $all && ! $studentId) {
        $this->error('Provide a studentId or use --all');
        return self::FAILURE;
    }

    $query = User::role('student');
    if (! $all) {
        $query->whereKey($studentId);
    }

    $students = $query->get();
    if ($students->isEmpty()) {
        $this->error('No matching student(s) found.');
        return self::FAILURE;
    }

    $processed = 0;
    $sent = 0;
    $failed = 0;

    foreach ($students as $student) {
        try {
            $processed++;
            $dispatched = $service->dispatchForStudent($student->fresh('supervisor'));
            $sent += $dispatched;

            $this->line(sprintf(
                'Student %d (%s): dispatched %d reminder(s).',
                $student->id,
                $student->name,
                $dispatched
            ));
        } catch (\Throwable $e) {
            $failed++;
            $this->warn(sprintf('Skipped student %d: %s', $student->id, $e->getMessage()));
        }
    }

    $this->info(sprintf('Done. Processed=%d, sent=%d, failed=%d', $processed, $sent, $failed));

    return self::SUCCESS;
})->purpose('Dispatch overdue checkpoint reminders for students and supervisors');

Artisan::command('assistant:dispatch-meeting-notifications', function () {
    /** @var ClosureCommand $this */
    $service = app(MeetingRequestService::class);

    $upcomingSent = $service->dispatchUpcomingApprovedMeetingReminders();
    $monthlySent = $service->dispatchMonthlyStudentEngagementReminders();

    $this->info(sprintf(
        'Done. Upcoming reminders sent=%d, monthly reminders sent=%d',
        $upcomingSent,
        $monthlySent
    ));

    return self::SUCCESS;
})->purpose('Dispatch approved meeting reminders and monthly supervisor-meeting nudges');

Schedule::command('assistant:dispatch-reminders --all')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::command('assistant:dispatch-meeting-notifications')
    ->dailyAt('08:45')
    ->withoutOverlapping();

Schedule::command('ml:predict-got --all')
    ->dailyAt('08:30')
    ->withoutOverlapping();

Artisan::command('ml:recommend-papers {studentId : Student user id} {--limit=3 : Number of papers}', function () {
    /** @var ClosureCommand $this */
    $service = app(PaperRecommendationService::class);
    $studentId = (int) $this->argument('studentId');
    $limit = max(1, (int) $this->option('limit'));

    $student = User::role('student')->find($studentId);
    if (! $student) {
        $this->error('Student not found.');
        return self::FAILURE;
    }

    $papers = $service->recommendForStudent($student, $limit);
    if ($papers === []) {
        $this->warn('No papers returned. Check ml/papers_catalog.csv');
        return self::SUCCESS;
    }

    foreach ($papers as $paper) {
        $this->line(sprintf('- %s (%s)', $paper['title'] ?? 'Untitled', $paper['url'] ?? 'n/a'));
    }

    return self::SUCCESS;
})->purpose('Recommend research readings for a student (TF-IDF baseline)');

