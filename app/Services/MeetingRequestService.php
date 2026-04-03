<?php

namespace App\Services;

use App\Models\MeetingRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\MeetingRequestWorkflowNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class MeetingRequestService
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
    ) {
    }

    /**
     * @return array{available: bool, reason?: string}
     */
    public function checkSlotAvailability(
        User $supervisor,
        CarbonImmutable $requestedStart,
        CarbonImmutable $requestedEnd,
        ?int $ignoreMeetingRequestId = null
    ): array {
        if (! $supervisor->hasRole('lecturer')) {
            return ['available' => false, 'reason' => 'Supervisor account is not valid for scheduling.'];
        }

        if ($requestedStart->gte($requestedEnd)) {
            return ['available' => false, 'reason' => 'Meeting end time must be later than start time.'];
        }

        if ($requestedStart->lt(now()->addMinutes(15))) {
            return ['available' => false, 'reason' => 'Meeting time must be at least 15 minutes from now.'];
        }

        $busyIntervals = $this->collectBusyIntervals(
            $supervisor,
            $requestedStart->subDay()->startOfDay(),
            $requestedEnd->addDay()->endOfDay(),
            $ignoreMeetingRequestId
        );

        foreach ($busyIntervals as $interval) {
            if ($this->intervalsOverlap($requestedStart, $requestedEnd, $interval['start'], $interval['end'])) {
                return ['available' => false, 'reason' => 'Selected slot overlaps with supervisor availability. Please choose another time.'];
            }
        }

        return ['available' => true];
    }

    /**
     * @return array<int, array{start: string, end: string, label: string}>
     */
    public function recommendSlotsForStudent(
        User $student,
        int $daysAhead = 14,
        int $maxSlots = 4,
        int $durationMinutes = 60
    ): array {
        $supervisor = $student->supervisor;

        if (! $supervisor || ! $supervisor->hasRole('lecturer')) {
            return [];
        }

        $now = CarbonImmutable::now();
        $windowStart = $now->addDay()->startOfDay();
        $windowEnd = $windowStart->addDays(max(1, $daysAhead))->endOfDay();

        $busyIntervals = $this->collectBusyIntervals($supervisor, $windowStart, $windowEnd);
        $candidateHours = [9, 10, 11, 14, 15, 16];

        $suggestions = [];

        for ($offset = 0; $offset < $daysAhead; $offset++) {
            $day = $windowStart->addDays($offset);

            if ($day->isWeekend()) {
                continue;
            }

            foreach ($candidateHours as $hour) {
                $slotStart = $day->setTime($hour, 0);
                $slotEnd = $slotStart->addMinutes($durationMinutes);

                if ($slotStart->lte($now)) {
                    continue;
                }

                $overlap = collect($busyIntervals)->contains(function (array $interval) use ($slotStart, $slotEnd) {
                    return $this->intervalsOverlap($slotStart, $slotEnd, $interval['start'], $interval['end']);
                });

                if ($overlap) {
                    continue;
                }

                $suggestions[] = [
                    'start' => $slotStart->toIso8601String(),
                    'end' => $slotEnd->toIso8601String(),
                    'label' => $slotStart->format('D, d M Y H:i') . ' - ' . $slotEnd->format('H:i'),
                ];

                if (count($suggestions) >= $maxSlots) {
                    return $suggestions;
                }
            }
        }

        return $suggestions;
    }

    public function sendSupervisorNewRequestNotification(MeetingRequest $meetingRequest): void
    {
        $supervisor = $meetingRequest->supervisor;
        $student = $meetingRequest->student;

        if (! $supervisor || ! $student) {
            return;
        }

        $notificationKey = 'meeting-request-pending:' . $meetingRequest->id;

        if ($this->notificationAlreadySent($supervisor, $notificationKey)) {
            return;
        }

        $message = sprintf(
            '%s requested a supervision meeting on %s.',
            $student->name,
            $meetingRequest->requested_start->format('d M Y H:i')
        );

        // Use default message directly (personalization removed)

        $supervisor->notify(new MeetingRequestWorkflowNotification(
            notificationKey: $notificationKey,
            message: $message,
            actionUrl: url('/scheduling'),
            actionLabel: 'Review request',
            workflowType: 'pending_request'
        ));
    }

    public function sendStudentDecisionNotification(MeetingRequest $meetingRequest): void
    {
        $student = $meetingRequest->student;
        $supervisor = $meetingRequest->supervisor;

        if (! $student || ! $supervisor) {
            return;
        }

        $status = $meetingRequest->status;
        $notificationKey = 'meeting-request-decision:' . $meetingRequest->id . ':' . $status;

        if ($this->notificationAlreadySent($student, $notificationKey)) {
            return;
        }

        if ($status === MeetingRequest::STATUS_APPROVED) {
            $message = sprintf(
                'Your meeting request with %s on %s was approved. You will receive a reminder before the meeting.',
                $supervisor->name,
                $meetingRequest->requested_start->format('d M Y H:i')
            );
        } else {
            $message = sprintf(
                'Your meeting request with %s on %s was rejected. Please apply for another suitable time.',
                $supervisor->name,
                $meetingRequest->requested_start->format('d M Y H:i')
            );
        }

        $student->notify(new MeetingRequestWorkflowNotification(
            notificationKey: $notificationKey,
            message: $message,
            actionUrl: url('/scheduling'),
            actionLabel: 'Open schedule',
            workflowType: $status
        ));
    }

    public function dispatchUpcomingApprovedMeetingReminders(): int
    {
        $now = CarbonImmutable::now();
        $horizon = $now->addHours(24);

        $requests = MeetingRequest::query()
            ->with(['student', 'supervisor'])
            ->where('status', MeetingRequest::STATUS_APPROVED)
            ->whereNull('reminder_sent_at')
            ->whereBetween('requested_start', [$now->toDateTimeString(), $horizon->toDateTimeString()])
            ->get();

        $sent = 0;

        foreach ($requests as $request) {
            $student = $request->student;
            $supervisor = $request->supervisor;

            if (! $student || ! $supervisor) {
                continue;
            }

            $notificationKey = 'meeting-request-reminder:' . $request->id . ':' . $request->requested_start->format('YmdHi');

            if (! $this->notificationAlreadySent($student, $notificationKey)) {
                $message = sprintf(
                    'Reminder: Your approved meeting with %s is at %s.',
                    $supervisor->name,
                    $request->requested_start->format('d M Y H:i')
                );

                $student->notify(new MeetingRequestWorkflowNotification(
                    notificationKey: $notificationKey,
                    message: $message,
                    actionUrl: url('/scheduling'),
                    actionLabel: 'View schedule',
                    workflowType: 'meeting_reminder'
                ));
                $sent++;
            }

            $request->forceFill(['reminder_sent_at' => now()])->save();
        }

        return $sent;
    }

    public function dispatchMonthlyStudentEngagementReminders(): int
    {
        $now = CarbonImmutable::now();
        $monthKey = $now->format('Y-m');

        $students = User::role('student')->with('supervisor')->get();
        $sent = 0;

        foreach ($students as $student) {
            $supervisor = $student->supervisor;
            if (! $supervisor || ! $supervisor->hasRole('lecturer')) {
                continue;
            }

            $hasRecentApprovedMeeting = MeetingRequest::query()
                ->where('student_id', $student->id)
                ->where('status', MeetingRequest::STATUS_APPROVED)
                ->where('requested_start', '>=', $now->subDays(30)->toDateTimeString())
                ->exists();

            if ($hasRecentApprovedMeeting) {
                continue;
            }

            $notificationKey = 'meeting-monthly-reminder:' . $student->id . ':' . $monthKey;
            if ($this->notificationAlreadySent($student, $notificationKey)) {
                continue;
            }

            $message = 'Monthly reminder: no approved meeting with your supervisor is recorded in the last 30 days. Consider applying for a meeting this month.';

            $student->notify(new MeetingRequestWorkflowNotification(
                notificationKey: $notificationKey,
                message: $message,
                actionUrl: url('/scheduling'),
                actionLabel: 'Apply now',
                workflowType: 'monthly_meeting_reminder'
            ));

            $sent++;
        }

        return $sent;
    }

    /**
     * @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable, source: string}>
     */
    private function collectBusyIntervals(
        User $supervisor,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
        ?int $ignoreMeetingRequestId = null
    ): Collection {
        $busyIntervals = collect();

        try {
            $events = collect($this->googleCalendarService->getEventsForUser(
                $supervisor,
                $windowStart->toDateString(),
                $windowEnd->toDateString()
            ));

            $googleIntervals = $events
                ->map(function (array $event) {
                    $start = $this->parseEventTime($event['start'] ?? null, false);
                    $end = $this->parseEventTime($event['end'] ?? null, true);

                    if (! $start || ! $end || $end->lte($start)) {
                        return null;
                    }

                    return [
                        'start' => $start,
                        'end' => $end,
                        'source' => 'google',
                    ];
                })
                ->filter()
                ->values();

            $busyIntervals = $busyIntervals->concat($googleIntervals);
        } catch (\Throwable) {
            // Fallback to local schedules when Google Calendar is unavailable.
        }

        $localSchedules = Schedule::query()
            ->where('user_id', $supervisor->id)
            ->where('end_time', '>', $windowStart->toDateTimeString())
            ->where('start_time', '<', $windowEnd->toDateTimeString())
            ->get()
            ->map(function (Schedule $schedule) {
                $start = CarbonImmutable::parse($schedule->start_time);
                $end = CarbonImmutable::parse($schedule->end_time);

                return [
                    'start' => $start,
                    'end' => $end,
                    'source' => 'local',
                ];
            });

        $pendingOrApprovedRequests = MeetingRequest::query()
            ->where('supervisor_id', $supervisor->id)
            ->whereIn('status', [MeetingRequest::STATUS_PENDING, MeetingRequest::STATUS_APPROVED])
            ->where('requested_end', '>', $windowStart->toDateTimeString())
            ->where('requested_start', '<', $windowEnd->toDateTimeString())
            ->when($ignoreMeetingRequestId, fn ($query) => $query->whereKeyNot($ignoreMeetingRequestId))
            ->get()
            ->map(function (MeetingRequest $request) {
                return [
                    'start' => CarbonImmutable::parse($request->requested_start),
                    'end' => CarbonImmutable::parse($request->requested_end),
                    'source' => 'request',
                ];
            });

        return $busyIntervals
            ->concat($localSchedules)
            ->concat($pendingOrApprovedRequests)
            ->filter(fn (array $interval) => $interval['end']->gt($interval['start']))
            ->values();
    }

    private function parseEventTime(mixed $value, bool $endOfDay): ?CarbonImmutable
    {
        if (is_array($value)) {
            $value = $value['dateTime'] ?? $value['date'] ?? null;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parsed = CarbonImmutable::parse($value);

        if (strlen($value) === 10) {
            return $endOfDay ? $parsed->endOfDay() : $parsed->startOfDay();
        }

        return $parsed;
    }

    private function intervalsOverlap(
        CarbonImmutable $startA,
        CarbonImmutable $endA,
        CarbonImmutable $startB,
        CarbonImmutable $endB
    ): bool {
        return $startA->lt($endB) && $endA->gt($startB);
    }

    private function notificationAlreadySent(User $recipient, string $notificationKey): bool
    {
        return $recipient->notifications()
            ->where('type', MeetingRequestWorkflowNotification::class)
            ->where('data->notification_key', $notificationKey)
            ->exists();
    }
}
