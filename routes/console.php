<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Services\CandidatureTimelineService;

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
