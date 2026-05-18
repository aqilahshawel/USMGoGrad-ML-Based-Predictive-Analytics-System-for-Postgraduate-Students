<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TimelineEventSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $studentId,
        private readonly string $studentName,
        private readonly string $title,
        private readonly ?string $eventDate
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
        $message = sprintf(
            '%s submitted a timeline event: %s%s.',
            $this->studentName,
            $this->title,
            $this->eventDate ? ' (target ' . $this->eventDate . ')' : ''
        );

        return [
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'title' => $this->title,
            'event_date' => $this->eventDate,
            'message' => $message,
            'action_label' => 'Review',
            'action_url' => route('timeline.index', [
                'student_id' => $this->studentId,
            ]),
        ];
    }
}
