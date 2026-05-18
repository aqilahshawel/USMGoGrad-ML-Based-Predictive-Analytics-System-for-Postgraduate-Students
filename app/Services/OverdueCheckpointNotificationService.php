<?php

namespace App\Services;

use App\Models\Timeline;
use App\Models\User;
use App\Notifications\CheckpointOverdueReminderNotification;
use App\Models\StudentCheckpoint;
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

        $upcomingReminders = $this->buildUpcomingReminders($student, $checkpoint);
        foreach ($upcomingReminders as $reminder) {
            $activeReminderKeys[] = $reminder['reminder_key'];
        }

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

        foreach ($upcomingReminders as $reminder) {
            foreach ($recipients as $recipient) {
                if ($this->notificationAlreadySent($recipient, $reminder['reminder_key'])) {
                    continue;
                }

                $recipient->notify(new CheckpointOverdueReminderNotification(
                    studentId: (int) $student->id,
                    studentName: (string) $student->name,
                    checkpointTitle: (string) $reminder['title'],
                    targetDate: $reminder['target_date'],
                    targetSemester: (int) $reminder['target_semester'],
                    currentSemester: $currentSemester,
                    reminderKey: $reminder['reminder_key'],
                    message: $reminder['message'],
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
     * @return array<int, array{reminder_key:string,title:string,target_date:?string,target_semester:int,message:string}>
     */
    private function buildUpcomingReminders(User $student, ?StudentCheckpoint $checkpoint): array
    {
        if (! $checkpoint) {
            return [];
        }

        $today = Carbon::today();
        $reminders = [];

        foreach ($this->upcomingReminderDefinitions() as $definition) {
            $recommendedDate = $checkpoint->{$definition['recommended_field']} ?? null;
            if (! $recommendedDate) {
                continue;
            }

            $recommendedDate = $recommendedDate instanceof Carbon
                ? $recommendedDate->copy()->startOfDay()
                : Carbon::parse((string) $recommendedDate)->startOfDay();

            if ($recommendedDate->lte($today)) {
                continue;
            }

            $daysUntil = $today->diffInDays($recommendedDate, false);
            if (! in_array($daysUntil, [14, 7], true)) {
                continue;
            }

            if (! empty($definition['observed_field']) && ! empty($checkpoint->{$definition['observed_field']})) {
                continue;
            }

            $windowLabel = $daysUntil === 14 ? '2 weeks' : '1 week';
            $reminderKey = sprintf(
                'checkpoint_upcoming:%d:%s:d%d',
                (int) $student->id,
                $definition['recommended_field'],
                $daysUntil
            );

            $reminders[] = [
                'reminder_key' => $reminderKey,
                'title' => $definition['title'],
                'target_date' => $recommendedDate->format('Y-m-d'),
                'target_semester' => (int) ($definition['target_semester'] ?? 0),
                'message' => sprintf(
                    'Your model-suggested deadline for %s is in %s on %s. Please prepare early and check in with your supervisor if needed.',
                    $definition['title'],
                    $windowLabel,
                    $recommendedDate->format('d M Y')
                ),
            ];
        }

        return $reminders;
    }

    /**
     * @return array<int, array{recommended_field:string,observed_field:string,title:string,target_semester:int}>
     */
    private function upcomingReminderDefinitions(): array
    {
        return [
            [
                'recommended_field' => 'recommended_proposal_review_date',
                'observed_field' => 'proposal_review_date',
                'title' => 'Proposal Review Date',
                'target_semester' => 0,
            ],
            [
                'recommended_field' => 'recommended_research_review_date',
                'observed_field' => 'research_review_date',
                'title' => 'Research Review Date',
                'target_semester' => 0,
            ],
            [
                'recommended_field' => 'recommended_nos_date',
                'observed_field' => 'nos_date',
                'title' => 'NOS Date',
                'target_semester' => 0,
            ],
            [
                'recommended_field' => 'recommended_final_draft_submission_date',
                'observed_field' => 'final_draft_submission_date',
                'title' => 'Final Draft Submission Date',
                'target_semester' => 0,
            ],
            [
                'recommended_field' => 'recommended_viva_date',
                'observed_field' => 'viva_date',
                'title' => 'Viva Date',
                'target_semester' => 0,
            ],
        ];
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
