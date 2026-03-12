<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
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
            'message' => sprintf(
                '%s is overdue for %s (target semester %d, current semester %d).',
                $this->studentName,
                $this->checkpointTitle,
                $this->targetSemester,
                $this->currentSemester
            ),
            'timeline_url' => route('timeline.index', [
                'student_id' => $this->resolveStudentIdForRoute($notifiable),
                'tab' => 'checkpoint',
            ]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('timeline.index', [
            'student_id' => $this->resolveStudentIdForRoute($notifiable),
            'tab' => 'checkpoint',
        ]);

        $line = sprintf(
            '%s is overdue for %s (target semester %d, current semester %d).',
            $this->studentName,
            $this->checkpointTitle,
            $this->targetSemester,
            $this->currentSemester
        );

        if ($this->targetDate) {
            $line .= ' Target date: ' . $this->targetDate . '.';
        }

        return (new MailMessage)
            ->subject('USMGoGrad Reminder: Overdue Checkpoint')
            ->greeting('USMGoGrad Checkpoint Reminder')
            ->line($line)
            ->action('Open Timeline', $url)
            ->line('Please update or review the checkpoint status in the system.');
    }

    private function resolveStudentIdForRoute(object $notifiable): ?int
    {
        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('lecturer')) {
            return $this->studentId;
        }

        return null;
    }
}
