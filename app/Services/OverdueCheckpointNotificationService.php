<?php

namespace App\Services;

use App\Models\Timeline;
use App\Models\User;
use App\Notifications\CheckpointOverdueReminderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class OverdueCheckpointNotificationService
{
    public function __construct(
        private readonly CandidatureTimelineService $timelineService,
    ) {
    }

    public function dispatchForStudent(User $student): int
    {
        if (! $student->hasRole('student') || ! $student->start_date) {
            return 0;
        }

        $snapshot = $this->timelineService->progressSnapshot($student->start_date, $student->study_mode ?: 'full_time');
        $currentSemester = (int) ($snapshot['current_semester'] ?? 0);

        if ($currentSemester <= 0) {
            return 0;
        }

        $overdueStops = Timeline::query()
            ->where('user_id', $student->id)
            ->where('system_key', 'like', 'checkpoint:%')
            ->where('completed', false)
            ->whereNotNull('semester_number')
            ->orderBy('semester_number')
            ->get()
            ->filter(fn (Timeline $stop) => $currentSemester > (int) $stop->semester_number)
            ->values();

        $checkpoint = $student->checkpoint;
        $hasProgress = (bool) (
            $checkpoint?->ekc500_admin_approved
            || $checkpoint?->proposal_review_date
            || $checkpoint?->publish_paper
            || $checkpoint?->research_review_date
            || $checkpoint?->nos_date
            || $checkpoint?->final_draft_submission_date
            || $checkpoint?->viva_date
        );
        $needsProgressNudge = ! $hasProgress && $currentSemester >= 3;

        $recipients = $this->buildRecipients($student);
        if ($recipients->isEmpty()) {
            return 0;
        }

        $activeReminderKeys = $overdueStops
            ->map(fn (Timeline $stop) => $this->buildReminderKey($student, $stop))
            ->values()
            ->all();

        if ($needsProgressNudge) {
            $activeReminderKeys[] = $this->buildProgressNudgeKey($student);
        }

        $this->markResolvedNotificationsAsRead($recipients, $student, $activeReminderKeys);

        if ($overdueStops->isEmpty() && ! $needsProgressNudge) {
            return 0;
        }

        $sent = 0;

        foreach ($overdueStops as $stop) {
            $reminderKey = $this->buildReminderKey($student, $stop);

            foreach ($recipients as $recipient) {
                if ($this->notificationAlreadySent($recipient, $reminderKey)) {
                    continue;
                }

                $overdueSemesters = max(1, $currentSemester - (int) $stop->semester_number);
                $overdueMonths = $overdueSemesters * 6;
                $defaultMessage = sprintf(
                    'You are overdue by %d month%s for %s. Consider updating your timeline or meeting your supervisor.',
                    $overdueMonths,
                    $overdueMonths === 1 ? '' : 's',
                    (string) $stop->title
                );

                $personalizedMessage = $defaultMessage;

                $recipient->notify(new CheckpointOverdueReminderNotification(
                    studentId: (int) $student->id,
                    studentName: (string) $student->name,
                    checkpointTitle: (string) $stop->title,
                    targetDate: optional($stop->event_date)->format('Y-m-d'),
                    targetSemester: (int) $stop->semester_number,
                    currentSemester: $currentSemester,
                    reminderKey: $reminderKey,
                    message: $personalizedMessage,
                ));

                $sent++;
            }
        }

        if ($needsProgressNudge) {
            $reminderKey = $this->buildProgressNudgeKey($student);
            foreach ($recipients as $recipient) {
                if ($this->notificationAlreadySent($recipient, $reminderKey)) {
                    continue;
                }

                $recipient->notify(new CheckpointOverdueReminderNotification(
                    studentId: (int) $student->id,
                    studentName: (string) $student->name,
                    checkpointTitle: 'Progress update',
                    targetDate: null,
                    targetSemester: 0,
                    currentSemester: $currentSemester,
                    reminderKey: $reminderKey,
                    message: 'No checkpoint progress has been recorded after 2 semesters. Please schedule a meeting with your supervisor to plan next steps.',
                ));

                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param Collection<int, User> $recipients
     * @param array<int, string> $activeReminderKeys
     */
    private function markResolvedNotificationsAsRead(Collection $recipients, User $student, array $activeReminderKeys): void
    {
        $now = now();

        foreach ($recipients as $recipient) {
            $query = $recipient->unreadNotifications()
                ->where('type', CheckpointOverdueReminderNotification::class)
                ->where('data->student_id', (int) $student->id);

            if ($activeReminderKeys !== []) {
                $query->whereNotIn('data->reminder_key', $activeReminderKeys);
            }

            $query->update(['read_at' => $now]);
        }
    }

    private function buildReminderKey(User $student, Timeline $stop): string
    {
        return sprintf(
            'checkpoint_overdue:%d:%s:s%d',
            (int) $student->id,
            (string) $stop->system_key,
            (int) $stop->semester_number
        );
    }

    private function buildProgressNudgeKey(User $student): string
    {
        return sprintf('checkpoint_progress_nudge:%d', (int) $student->id);
    }

    /**
     * @return Collection<int, User>
     */
    private function buildRecipients(User $student): Collection
    {
        $recipients = collect([$student]);

        if ($student->supervisor) {
            $recipients->push($student->supervisor);
        }

        return $recipients->unique('id')->values();
    }

    private function notificationAlreadySent(User $recipient, string $reminderKey): bool
    {
        return $recipient->notifications()
            ->where('type', CheckpointOverdueReminderNotification::class)
            ->where('data->reminder_key', $reminderKey)
            ->exists();
    }
}
