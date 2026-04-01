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

        $recipients = $this->buildRecipients($student);
        if ($recipients->isEmpty()) {
            return 0;
        }

        $activeReminderKeys = $overdueStops
            ->map(fn (Timeline $stop) => $this->buildReminderKey($student, $stop))
            ->values()
            ->all();

        $this->markResolvedNotificationsAsRead($recipients, $student, $activeReminderKeys);

        if ($overdueStops->isEmpty()) {
            return 0;
        }

        $sent = 0;

        foreach ($overdueStops as $stop) {
            $reminderKey = $this->buildReminderKey($student, $stop);

            foreach ($recipients as $recipient) {
                if ($this->notificationAlreadySent($recipient, $reminderKey)) {
                    continue;
                }

                $defaultMessage = sprintf(
                    '%s is overdue for %s (target semester %d, current semester %d).',
                    (string) $student->name,
                    (string) $stop->title,
                    (int) $stop->semester_number,
                    $currentSemester
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
