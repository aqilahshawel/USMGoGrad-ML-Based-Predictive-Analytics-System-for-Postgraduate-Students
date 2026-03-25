<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarIntegration;
use App\Models\MeetingRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\MeetingRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly MeetingRequestService $meetingRequestService
    )
    {
    }

    /**
     * Show schedules based on user role.
     */
    public function index()
    {
        $user = Auth::user();
        $owner = $this->resolveScheduleOwner($user);
        $canCreateEvents = $user->hasRole('lecturer');
        $isStudent = $user->hasRole('student');

        $studentMeetingRequests = collect();
        $pendingMeetingRequests = collect();
        $reviewedMeetingRequests = collect();
        $recommendedMeetingSlots = [];

        if ($isStudent) {
            $studentMeetingRequests = MeetingRequest::query()
                ->with(['supervisor', 'reviewer'])
                ->where('student_id', $user->id)
                ->orderByDesc('created_at')
                ->take(12)
                ->get();

            $recommendedMeetingSlots = $this->meetingRequestService->recommendSlotsForStudent($user);
        }

        if ($canCreateEvents) {
            $pendingMeetingRequests = MeetingRequest::query()
                ->with('student')
                ->where('supervisor_id', $user->id)
                ->where('status', MeetingRequest::STATUS_PENDING)
                ->orderBy('requested_start')
                ->get();

            $reviewedMeetingRequests = MeetingRequest::query()
                ->with('student')
                ->where('supervisor_id', $user->id)
                ->whereIn('status', [MeetingRequest::STATUS_APPROVED, MeetingRequest::STATUS_REJECTED])
                ->orderByDesc('reviewed_at')
                ->take(10)
                ->get();
        }

        if (! $owner) {
            return view('scheduling.index', [
                'schedules' => collect(),
                'scheduleOwner' => null,
                'supervisorMissing' => $user->hasRole('student'),
                'calendarIntegration' => null,
                'isCalendarConnected' => false,
                'canCreateEvents' => $canCreateEvents,
                'isStudent' => $isStudent,
                'studentMeetingRequests' => $studentMeetingRequests,
                'pendingMeetingRequests' => $pendingMeetingRequests,
                'reviewedMeetingRequests' => $reviewedMeetingRequests,
                'recommendedMeetingSlots' => $recommendedMeetingSlots,
            ]);
        }

        $schedules = Schedule::where('user_id', $owner->id)->get();
        $calendarIntegration = $owner->googleCalendarIntegration()->first();
        $isCalendarConnected = $calendarIntegration
            && $calendarIntegration->status === CalendarIntegration::STATUS_CONNECTED;

        return view('scheduling.index', [
            'schedules' => $schedules,
            'scheduleOwner' => $owner,
            'supervisorMissing' => false,
            'calendarIntegration' => $calendarIntegration,
            'isCalendarConnected' => $isCalendarConnected,
            'canCreateEvents' => $canCreateEvents,
            'isStudent' => $isStudent,
            'studentMeetingRequests' => $studentMeetingRequests,
            'pendingMeetingRequests' => $pendingMeetingRequests,
            'reviewedMeetingRequests' => $reviewedMeetingRequests,
            'recommendedMeetingSlots' => $recommendedMeetingSlots,
        ]);
    }

    /**
     * Retrieve schedules for API (FullCalendar, etc.)
     */
    public function getEvents()
    {
        $user = Auth::user();

        $owner = $this->resolveScheduleOwner($user);

        if (! $owner) {
            return response()->json([]);
        }

        $events = [];

        try {
            $events = $this->googleCalendarService->getEventsForUser($owner);
        } catch (Throwable $exception) {
            Log::info('Falling back to local schedule events', [
                'user_id' => $user->id,
                'owner_id' => $owner->id,
                'message' => $exception->getMessage(),
            ]);

            $localEvents = Schedule::where('user_id', $owner->id)->get();

            $events = $localEvents->map(function ($schedule) {
                return [
                    'title' => $schedule->title,
                    'start' => $schedule->start_time,
                    'end' => $schedule->end_time,
                    'color' => 'blue',
                    'source' => 'local',
                ];
            })->values()->all();
        }

        $pendingRequestEvents = $this->buildPendingRequestCalendarEvents($user);

        return response()->json(array_values(array_merge($events, $pendingRequestEvents)));
    }

    public function getAvailability()
    {
        return $this->getEvents();
    }

    /**
     * Show the create schedule form (for lecturers only).
     */
    public function create()
    {
        return view('scheduling.create');
    }

    /**
     * Store a new schedule (for lecturers only).
     */
    public function saveEvent(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user->hasRole('lecturer')) {
                return response()->json(['error' => 'Only lecturers can create schedule events.'], 403);
            }

            // Validate incoming request
            $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date',
                'end' => 'required|date|after:start',
            ]);

            $googleEvent = null;

            if ($this->googleCalendarService->isConnected($user)) {
                $googleEvent = $this->googleCalendarService->createEventForUser($user, [
                    'title' => $request->title,
                    'start' => $request->start,
                    'end' => $request->end,
                ]);
            }
    
            // Create new schedule entry
            $schedule = Schedule::create([
                'title' => $request->title,
                'start_time' => $request->start,
                'end_time' => $request->end,
                'user_id' => Auth::id(), // Ensure authenticated user
            ]);
    
            $message = $googleEvent
                ? 'Event saved to Google Calendar and local schedule successfully!'
                : 'Event saved locally. Connect Google Calendar to sync automatically.';

            return response()->json([
                'message' => $message,
                'schedule' => $schedule,
                'google_event' => $googleEvent,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeMeetingRequest(Request $request)
    {
        $student = Auth::user();

        if (! $student->hasRole('student')) {
            abort(403, 'Only students can submit meeting requests.');
        }

        $supervisor = $student->supervisor;

        if (! $supervisor || ! $supervisor->hasRole('lecturer')) {
            return response()->json([
                'error' => 'No supervisor is assigned yet. You cannot apply for a meeting now.',
            ], 422);
        }

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'start' => ['required', 'date', 'after:now'],
            'end' => ['required', 'date', 'after:start'],
            'student_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = CarbonImmutable::parse((string) $validated['start']);
        $end = CarbonImmutable::parse((string) $validated['end']);

        $availability = $this->meetingRequestService->checkSlotAvailability($supervisor, $start, $end);
        if (! ($availability['available'] ?? false)) {
            return response()->json([
                'error' => $availability['reason'] ?? 'Selected time is unavailable. Please choose another slot.',
            ], 422);
        }

        $meetingRequest = MeetingRequest::query()->create([
            'student_id' => $student->id,
            'supervisor_id' => $supervisor->id,
            'requested_start' => $start,
            'requested_end' => $end,
            'status' => MeetingRequest::STATUS_PENDING,
            'topic' => (string) $validated['topic'],
            'student_note' => $validated['student_note'] ?? null,
        ]);

        $this->meetingRequestService->sendSupervisorNewRequestNotification(
            $meetingRequest->fresh(['student', 'supervisor'])
        );

        return response()->json([
            'message' => 'Meeting request submitted. Your supervisor will approve or reject it first.',
            'meeting_request' => $meetingRequest,
        ], 201);
    }

    public function approveMeetingRequest(Request $request, MeetingRequest $meetingRequest)
    {
        $lecturer = Auth::user();

        if (! $lecturer->hasRole('lecturer') || (int) $meetingRequest->supervisor_id !== (int) $lecturer->id) {
            abort(403, 'You can only approve requests assigned to you.');
        }

        if ($meetingRequest->status !== MeetingRequest::STATUS_PENDING) {
            return redirect('/scheduling')->with('info', 'This request is no longer pending.');
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $availability = $this->meetingRequestService->checkSlotAvailability(
            $lecturer,
            CarbonImmutable::instance($meetingRequest->requested_start),
            CarbonImmutable::instance($meetingRequest->requested_end),
            $meetingRequest->id
        );

        if (! ($availability['available'] ?? false)) {
            return redirect('/scheduling')->with('error', $availability['reason'] ?? 'Unable to approve because the slot is no longer available.');
        }

        $meetingRequest->status = MeetingRequest::STATUS_APPROVED;
        $meetingRequest->review_note = $validated['review_note'] ?? null;
        $meetingRequest->reviewed_by = $lecturer->id;
        $meetingRequest->reviewed_at = now();
        $meetingRequest->approved_event_source = 'local';

        $student = $meetingRequest->student()->first();
        $eventTitle = 'Supervision Meeting: ' . ($student?->name ?? 'Student');

        Schedule::create([
            'title' => $eventTitle,
            'start_time' => $meetingRequest->requested_start,
            'end_time' => $meetingRequest->requested_end,
            'user_type' => 'lecturer',
            'user_id' => $lecturer->id,
        ]);

        try {
            if ($this->googleCalendarService->isConnected($lecturer)) {
                $googleEvent = $this->googleCalendarService->createEventForUser($lecturer, [
                    'title' => $eventTitle,
                    'start' => CarbonImmutable::instance($meetingRequest->requested_start)->toIso8601String(),
                    'end' => CarbonImmutable::instance($meetingRequest->requested_end)->toIso8601String(),
                ]);

                $meetingRequest->approved_event_source = 'google';
                $meetingRequest->google_event_id = $googleEvent['id'] ?? null;
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to sync approved meeting request to Google Calendar', [
                'meeting_request_id' => $meetingRequest->id,
                'lecturer_id' => $lecturer->id,
                'message' => $exception->getMessage(),
            ]);
        }

        $meetingRequest->save();

        $this->meetingRequestService->sendStudentDecisionNotification(
            $meetingRequest->fresh(['student', 'supervisor'])
        );

        return redirect('/scheduling')->with('success', 'Meeting request approved. Student has been notified.');
    }

    public function rejectMeetingRequest(Request $request, MeetingRequest $meetingRequest)
    {
        $lecturer = Auth::user();

        if (! $lecturer->hasRole('lecturer') || (int) $meetingRequest->supervisor_id !== (int) $lecturer->id) {
            abort(403, 'You can only reject requests assigned to you.');
        }

        if ($meetingRequest->status !== MeetingRequest::STATUS_PENDING) {
            return redirect('/scheduling')->with('info', 'This request is no longer pending.');
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $meetingRequest->status = MeetingRequest::STATUS_REJECTED;
        $meetingRequest->review_note = $validated['review_note'] ?? null;
        $meetingRequest->reviewed_by = $lecturer->id;
        $meetingRequest->reviewed_at = now();
        $meetingRequest->save();

        $this->meetingRequestService->sendStudentDecisionNotification(
            $meetingRequest->fresh(['student', 'supervisor'])
        );

        return redirect('/scheduling')->with('success', 'Meeting request rejected. Student has been advised to apply again.');
    }

    private function resolveScheduleOwner(User $user): ?User
    {
        if ($user->hasRole('lecturer')) {
            return $user;
        }

        if (
            $user->hasRole('student')
            && $user->supervisor
            && $user->supervisor->hasRole('lecturer')
        ) {
            return $user->supervisor;
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPendingRequestCalendarEvents(User $viewer): array
    {
        $query = MeetingRequest::query()
            ->where('status', MeetingRequest::STATUS_PENDING)
            ->orderBy('requested_start');

        if ($viewer->hasRole('student')) {
            $query->where('student_id', $viewer->id);
        } elseif ($viewer->hasRole('lecturer')) {
            $query->where('supervisor_id', $viewer->id);
        } else {
            return [];
        }

        return $query
            ->get()
            ->map(function (MeetingRequest $request) {
                return [
                    'title' => 'Pending request: ' . $request->topic,
                    'start' => $request->requested_start,
                    'end' => $request->requested_end,
                    'color' => '#f0ad4e',
                    'source' => 'meeting_request',
                ];
            })
            ->values()
            ->all();
    }
    
}
