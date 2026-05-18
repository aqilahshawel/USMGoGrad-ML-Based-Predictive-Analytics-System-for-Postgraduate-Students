<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CheckpointOverdueReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $studentId,
        private readonly string $studentName,
        private readonly string $checkpointTitle,
        private readonly ?string $targetDate,
        private readonly int $targetSemester,
        private readonly int $currentSemester,
        private readonly string $reminderKey,
        private readonly ?string $message = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_key' => $this->reminderKey,
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'checkpoint_title' => $this->checkpointTitle,
            'target_date' => $this->targetDate,
            'target_semester' => $this->targetSemester,
            'current_semester' => $this->currentSemester,
            'message' => $this->message ?: $this->defaultMessage(),
            'timeline_url' => route('timeline.index', [
                'student_id' => $this->resolveStudentIdForRoute($notifiable),
                'tab' => 'checkpoint',
            ]),
        ];
    }

    private function defaultMessage(): string
    {
        $overdueSemesters = max(1, $this->currentSemester - $this->targetSemester);
        $overdueMonths = $overdueSemesters * 6;

        return sprintf(
            'You are overdue by %d month%s for %s. Consider updating your timeline or meeting your supervisor.',
            $overdueMonths,
            $overdueMonths === 1 ? '' : 's',
            $this->checkpointTitle
        );
    }

    private function resolveStudentIdForRoute(object $notifiable): ?int
    {
        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('lecturer')) {
            return $this->studentId;
        }

        return null;
    }
}
