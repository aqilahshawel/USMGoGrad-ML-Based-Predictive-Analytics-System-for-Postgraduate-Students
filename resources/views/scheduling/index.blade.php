@extends('layouts.app')

@section('title', 'Scheduling')

@section('styles')
<style>
    .scheduling-header {
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
        color: var(--n-900);
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }
    .scheduling-header h1 {
        font-family: var(--font-display);
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: var(--n-900);
    }
    .scheduling-header p {
        margin: 0;
        color: var(--n-700);
    }
    .calendar-status-card {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
    }
    .status-pill {
        display: inline-block;
        border-radius: 999px;
        padding: 0.15rem 0.65rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .status-connected {
        background: var(--lav-100);
        color: var(--lav-500);
    }
    .status-disconnected {
        background: var(--lav-200);
        color: var(--lav-500);
    }
    .status-revoked {
        background: var(--lav-100);
        color: var(--lav-500);
    }
    .status-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .status-actions .btn {
        border-radius: 10px;
    }
    #calendar {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.5rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
    }
    .fc .fc-button-primary {
        background: var(--lav-500) !important;
        border-color: var(--lav-500) !important;
    }
    .fc .fc-button-primary:hover {
        background: var(--lav-400) !important;
        border-color: var(--lav-400) !important;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--lav-400) !important;
        border-color: var(--lav-400) !important;
    }
    .fc .fc-daygrid-day.fc-day-today,
    .fc .fc-timegrid-col.fc-day-today {
        background: var(--lav-100) !important;
    }
    .fc-event {
        background: var(--lav-400) !important;
        border-color: var(--lav-500) !important;
        border-radius: 4px !important;
    }
</style>
@endsection

@section('content')
<head><meta name="csrf-token" content="{{ csrf_token() }}"></head>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="scheduling-header">
        <h1><i class="fas fa-calendar-alt"></i> Scheduling</h1>
        <p>
            @if($canCreateEvents)
                Manage your availability and sync events with Google Calendar.
            @else
                View your supervisor's schedule.
            @endif
        </p>
    </div>

    <div class="calendar-status-card">
            @if($supervisorMissing)
                <div class="alert alert-warning mb-0">No supervisor is assigned yet. Schedule is unavailable.</div>
            @elseif($scheduleOwner)
                @if($canCreateEvents)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">Your Calendar Sync</h5>
                            <p class="mb-1">Viewing schedule for <strong>{{ $scheduleOwner->name }}</strong></p>
                            <p class="mb-0">
                                Provider: <strong>Google</strong>
                                |
                                Calendar Email:
                                <strong>{{ $calendarIntegration?->calendar_email ?? 'Not connected' }}</strong>
                            </p>
                        </div>
                        @php
                            $status = $calendarIntegration?->status ?? 'disconnected';
                            $statusClass = match($status) {
                                'connected' => 'status-connected',
                                'revoked' => 'status-revoked',
                                default => 'status-disconnected',
                            };
                        @endphp
                        <span class="status-pill {{ $statusClass }}">{{ ucfirst($status) }}</span>
                    </div>

                    @if($calendarIntegration?->last_synced_at)
                        <small class="text-muted d-block mt-1">
                            Last sync: {{ $calendarIntegration->last_synced_at->format('Y-m-d H:i') }}
                        </small>
                    @endif

                    <div class="status-actions">
                        <a href="{{ route('calendar.google.connect') }}" class="btn btn-primary btn-sm">Connect / Reconnect Google</a>
                        @if($calendarIntegration && $calendarIntegration->status === 'connected')
                            <form method="POST" action="{{ route('calendar.google.disconnect') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Disconnect</button>
                            </form>
                        @endif
                    </div>
                @else
                    <h5 class="mb-1">Supervisor Schedule</h5>
                    <p class="mb-0">You are viewing <strong>{{ $scheduleOwner->name }}</strong>'s schedule.</p>
                @endif
            @endif
        </div>

    <div id="calendar"></div>

    <!-- Include FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const canCreateEvents = @json($canCreateEvents);

    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: canCreateEvents,
        editable: false,
        events: '/get-events',
        select: function (info) {
            if (!canCreateEvents) {
                return;
            }

            let title = prompt('Enter Event Title:');
            if (title) {
                saveEvent(title, info.startStr, info.endStr)
                    .then((data) => {
                        calendar.addEvent({
                            title: title,
                            start: info.startStr,
                            end: info.endStr
                        });

                        if (data.message) {
                            alert(data.message);
                        }
                    })
                    .catch((error) => {
                        alert(error.message);
                    });
            }
        }
    });

    calendar.render();
});


function saveEvent(title, start, end) {
    let csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        return Promise.reject(new Error('CSRF token missing.'));
    }

    return fetch('/save-event', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        },
        body: JSON.stringify({
            title: title,
            start: start,
            end: end
        })
    })
    .then(async (response) => {
        let data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to save event.');
        }

        return data;
    });
}



    </script>
@endsection
