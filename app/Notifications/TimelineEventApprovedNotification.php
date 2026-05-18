<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TimelineEventApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
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
            'Your timeline event was approved: %s%s.',
            $this->title,
            $this->eventDate ? ' (target ' . $this->eventDate . ')' : ''
        );

        return [
            'title' => $this->title,
            'event_date' => $this->eventDate,
            'message' => $message,
            'action_label' => 'View timeline',
            'action_url' => route('timeline.index'),
        ];
    }
}
