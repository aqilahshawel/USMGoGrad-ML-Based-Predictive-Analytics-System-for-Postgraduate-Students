<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarIntegration;
use App\Models\Schedule;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduleController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $googleCalendarService)
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

        if (! $owner) {
            return view('scheduling.index', [
                'schedules' => collect(),
                'scheduleOwner' => null,
                'supervisorMissing' => $user->hasRole('student'),
                'calendarIntegration' => null,
                'isCalendarConnected' => false,
                'canCreateEvents' => $canCreateEvents,
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

        try {
            return response()->json($this->googleCalendarService->getEventsForUser($owner));
        } catch (Throwable $exception) {
            Log::info('Falling back to local schedule events', [
                'user_id' => $user->id,
                'owner_id' => $owner->id,
                'message' => $exception->getMessage(),
            ]);
        }

        $events = Schedule::where('user_id', $owner->id)->get();

        return response()->json($events->map(function ($schedule) {
            return [
                'title' => $schedule->title,
                'start' => $schedule->start_time,
                'end' => $schedule->end_time,
                'color' => 'blue',
                'source' => 'local',
            ];
        }));
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
    
}
