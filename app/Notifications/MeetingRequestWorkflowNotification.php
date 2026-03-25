<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingRequestWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $notificationKey,
        private readonly string $message,
        private readonly string $actionUrl,
        private readonly string $actionLabel = 'Open schedule',
        private readonly ?string $workflowType = null,
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
            'notification_key' => $this->notificationKey,
            'workflow_type' => $this->workflowType,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }
}
