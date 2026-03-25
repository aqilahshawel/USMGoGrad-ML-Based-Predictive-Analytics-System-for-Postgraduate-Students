<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\User;
use App\Notifications\CheckpointOverdueReminderNotification;
use App\Notifications\MeetingRequestWorkflowNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $globalNotificationItems = collect();
            $globalUnreadNotificationCount = 0;

            $user = Auth::user();
            if ($user) {
                $workflowNotifications = $user->unreadNotifications()
                    ->whereIn('type', [
                        CheckpointOverdueReminderNotification::class,
                        MeetingRequestWorkflowNotification::class,
                    ])
                    ->latest()
                    ->get();

                $workflowItems = $workflowNotifications->map(function ($notice) {
                    $data = $notice->data ?? [];

                    if ($notice->type === CheckpointOverdueReminderNotification::class) {
                        return [
                            'type' => 'overdue',
                            'message' => $data['message'] ?? 'An overdue checkpoint reminder is available.',
                            'created_at' => $notice->created_at,
                            'action_type' => 'post',
                            'action_url' => route('notifications.openTimeline', $notice->id),
                            'action_label' => 'Open',
                            'dismiss_url' => route('notifications.dismiss', $notice->id),
                        ];
                    }

                    return [
                        'type' => 'meeting',
                        'message' => $data['message'] ?? 'A meeting request update is available.',
                        'created_at' => $notice->created_at,
                        'action_type' => 'post',
                        'action_url' => route('notifications.open', $notice->id),
                        'action_label' => $data['action_label'] ?? 'Open schedule',
                        'dismiss_url' => route('notifications.dismiss', $notice->id),
                    ];
                });

                $chatUnreadGroups = Message::query()
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->selectRaw('sender_id, COUNT(*) as unread_count, MAX(created_at) as latest_created_at')
                    ->groupBy('sender_id')
                    ->get();

                $senderNames = User::query()
                    ->whereIn('id', $chatUnreadGroups->pluck('sender_id')->filter()->all())
                    ->pluck('name', 'id');

                $chatItems = $chatUnreadGroups->map(function ($group) use ($senderNames, $user) {
                    $senderId = (int) $group->sender_id;
                    $unreadCount = (int) $group->unread_count;
                    $senderName = $senderNames[$senderId] ?? 'Someone';

                    $actionUrl = route('home');
                    if (method_exists($user, 'hasRole') && $user->hasRole('lecturer')) {
                        $actionUrl = route('lecturer.messages.student.chat', ['studentId' => $senderId]);
                    } elseif (method_exists($user, 'hasRole') && $user->hasRole('student')) {
                        $actionUrl = route('student.messages.index');
                    }

                    $latestCreatedAt = $group->latest_created_at
                        ? Carbon::parse($group->latest_created_at)
                        : null;

                    return [
                        'type' => 'chat',
                        'message' => $unreadCount === 1
                            ? $senderName . ' sent you 1 unread chat message.'
                            : $senderName . ' sent you ' . $unreadCount . ' unread chat messages.',
                        'created_at' => $latestCreatedAt,
                        'action_type' => 'link',
                        'action_url' => $actionUrl,
                        'action_label' => 'Open chat',
                    ];
                });

                $globalNotificationItems = $workflowItems
                    ->concat($chatItems)
                    ->sortByDesc(function (array $item) {
                        $createdAt = $item['created_at'] ?? null;

                        return $createdAt ? $createdAt->timestamp : 0;
                    })
                    ->take(8)
                    ->values();

                $globalUnreadNotificationCount = (int) $workflowNotifications->count()
                    + (int) $chatUnreadGroups->sum('unread_count');
            }

            $view
                ->with('globalNotificationItems', $globalNotificationItems)
                ->with('globalUnreadNotificationCount', $globalUnreadNotificationCount);
        });
    }
}
