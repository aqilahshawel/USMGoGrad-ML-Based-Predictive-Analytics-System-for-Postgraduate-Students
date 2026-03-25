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
    .calendar-container {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    #calendar {
        flex: 1;
        min-width: 0;
        background: var(--surface);
        border-radius: 14px;
        padding: 1.5rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
    }
    .calendar-sidebar {
        width: 350px;
        flex-shrink: 0;
    }
    @media (max-width: 1200px) {
        .calendar-container {
            flex-direction: column;
        }
        .calendar-sidebar {
            width: 100%;
        }
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
    .meeting-request-panel,
    .meeting-history-card {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
    }
    .calendar-sidebar .meeting-request-panel:first-child,
    .calendar-sidebar .meeting-history-card:first-child {
        margin-top: 0;
    }
    .meeting-request-panel h5,
    .meeting-history-card h5 {
        margin-bottom: 0.4rem;
        color: var(--n-900);
    }
    .slot-chip {
        border-radius: 999px;
        margin: 0.2rem 0.35rem 0.2rem 0;
    }
    .meeting-status-pill {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .meeting-status-pending {
        background: #fff3cd;
        color: #8a6d3b;
    }
    .meeting-status-approved {
        background: #d1e7dd;
        color: #0f5132;
    }
    .meeting-status-rejected {
        background: #f8d7da;
        color: #842029;
    }
    .meeting-request-item {
        border-top: 1px solid var(--lav-200);
        padding: 0.75rem 0;
    }
    .meeting-request-item:first-child {
        border-top: none;
        padding-top: 0;
    }
    .meeting-request-meta {
        font-size: 0.84rem;
        color: var(--muted);
    }
    .meeting-request-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
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
        <h1><i class="fas fa-calendar-alt me-2"></i> Manage Schedule </h1>
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

    <div class="calendar-container">
        <div id="calendar"></div>

        @if($isStudent && ! $supervisorMissing)
        <div class="calendar-sidebar">
            <div class="meeting-request-panel">
            <h5><i class="fas fa-hand-paper me-1"></i> Apply for a Supervisor Meeting</h5>
            <p class="mb-2">Select a suitable time slot on the calendar to apply. Your supervisor must approve or reject your request first.</p>

            @if(!empty($recommendedMeetingSlots))
                <div class="mb-2">
                    <strong class="d-block mb-1">Suggested slots:</strong>
                    @foreach($recommendedMeetingSlots as $slot)
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm slot-chip js-recommended-slot"
                            data-start="{{ $slot['start'] }}"
                            data-end="{{ $slot['end'] }}"
                        >
                            {{ $slot['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif

            <small class="text-muted">After approval, you will receive a notification and a reminder before the meeting date.</small>
        </div>

        <div class="meeting-history-card">
            <h5><i class="fas fa-list-check me-1"></i> Your Meeting Applications</h5>

            @forelse($studentMeetingRequests as $meetingRequest)
                @php
                    $statusClass = match($meetingRequest->status) {
                        'approved' => 'meeting-status-approved',
                        'rejected' => 'meeting-status-rejected',
                        default => 'meeting-status-pending',
                    };
                @endphp
                <div class="meeting-request-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <strong>{{ $meetingRequest->topic }}</strong>
                            <div class="meeting-request-meta">
                                {{ $meetingRequest->requested_start->format('d M Y H:i') }} - {{ $meetingRequest->requested_end->format('H:i') }}
                                with {{ $meetingRequest->supervisor?->name ?? 'Supervisor' }}
                            </div>
                        </div>
                        <span class="meeting-status-pill {{ $statusClass }}">{{ $meetingRequest->status }}</span>
                    </div>
                    @if($meetingRequest->student_note)
                        <div class="meeting-request-meta mt-1">Your note: {{ $meetingRequest->student_note }}</div>
                    @endif
                    @if($meetingRequest->review_note)
                        <div class="meeting-request-meta mt-1">Supervisor note: {{ $meetingRequest->review_note }}</div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">No meeting applications submitted yet.</p>
            @endforelse
            </div>
        </div>
        @endif
    </div>

    @if($canCreateEvents)
        <div class="meeting-history-card mt-3">
            <h5><i class="fas fa-inbox me-1"></i> Pending Student Meeting Requests</h5>
            @forelse($pendingMeetingRequests as $meetingRequest)
                <div class="meeting-request-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <strong>{{ $meetingRequest->student?->name ?? 'Student' }}</strong> requested:
                            <strong>{{ $meetingRequest->topic }}</strong>
                            <div class="meeting-request-meta">
                                {{ $meetingRequest->requested_start->format('d M Y H:i') }} - {{ $meetingRequest->requested_end->format('H:i') }}
                            </div>
                        </div>
                        <span class="meeting-status-pill meeting-status-pending">pending</span>
                    </div>

                    @if($meetingRequest->student_note)
                        <div class="meeting-request-meta mt-1">Student note: {{ $meetingRequest->student_note }}</div>
                    @endif

                    <div class="meeting-request-actions mt-2">
                        <form method="POST" action="{{ route('meeting-requests.approve', $meetingRequest) }}">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('meeting-requests.reject', $meetingRequest) }}" onsubmit="return attachRejectNote(this);">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="review_note" value="">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No pending requests right now.</p>
            @endforelse
        </div>

        <div class="meeting-history-card">
            <h5><i class="fas fa-clock-rotate-left me-1"></i> Recently Reviewed Requests</h5>
            @forelse($reviewedMeetingRequests as $meetingRequest)
                @php
                    $statusClass = $meetingRequest->status === 'approved' ? 'meeting-status-approved' : 'meeting-status-rejected';
                @endphp
                <div class="meeting-request-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <strong>{{ $meetingRequest->student?->name ?? 'Student' }}</strong>
                            <span class="meeting-request-meta">({{ $meetingRequest->topic }})</span>
                            <div class="meeting-request-meta">
                                {{ $meetingRequest->requested_start->format('d M Y H:i') }} - {{ $meetingRequest->requested_end->format('H:i') }}
                            </div>
                        </div>
                        <span class="meeting-status-pill {{ $statusClass }}">{{ $meetingRequest->status }}</span>
                    </div>
                    @if($meetingRequest->review_note)
                        <div class="meeting-request-meta mt-1">Review note: {{ $meetingRequest->review_note }}</div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">No reviewed requests yet.</p>
            @endforelse
        </div>
    @endif

    <!-- Include FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    const canCreateEvents = @json($canCreateEvents);
    const isStudent = @json($isStudent);
    const canSubmitMeetingRequests = @json($isStudent && ! $supervisorMissing);

    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: canCreateEvents || canSubmitMeetingRequests,
        editable: false,
        events: '/get-events',
        select: function (info) {
            if (canCreateEvents) {
                let title = prompt('Enter Event Title:');
                if (title) {
                    saveEvent(title, info.startStr, info.endStr)
                        .then((data) => {
                            calendar.refetchEvents();
                            if (data.message) {
                                alert(data.message);
                            }
                        })
                        .catch((error) => {
                            alert(error.message);
                        });
                }
                return;
            }

            if (!canSubmitMeetingRequests) {
                return;
            }

            let topic = prompt('Enter meeting topic for this request:');
            if (!topic) {
                return;
            }

            let note = prompt('Optional note for your supervisor:') || '';

            submitMeetingRequest(topic, info.startStr, info.endStr, note)
                .then((data) => {
                    calendar.refetchEvents();
                    if (data.message) {
                        alert(data.message);
                    }
                    window.location.reload();
                })
                .catch((error) => {
                    alert(error.message);
                });
        }
    });

    calendar.render();

    document.querySelectorAll('.js-recommended-slot').forEach(function (button) {
        button.addEventListener('click', function () {
            let topic = prompt('Enter meeting topic for this recommended slot:');
            if (!topic) {
                return;
            }

            let note = prompt('Optional note for your supervisor:') || '';

            submitMeetingRequest(topic, button.dataset.start, button.dataset.end, note)
                .then(function (data) {
                    calendar.refetchEvents();
                    if (data.message) {
                        alert(data.message);
                    }
                    window.location.reload();
                })
                .catch(function (error) {
                    alert(error.message);
                });
        });
    });
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

function submitMeetingRequest(topic, start, end, studentNote) {
    let csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        return Promise.reject(new Error('CSRF token missing.'));
    }

    return fetch('{{ route('meeting-requests.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        },
        body: JSON.stringify({
            topic: topic,
            start: start,
            end: end,
            student_note: studentNote
        })
    })
    .then(async (response) => {
        let data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to submit meeting request.');
        }

        return data;
    });
}

function attachRejectNote(formElement) {
    let note = prompt('Optional reason to send to student:') || '';
    let input = formElement.querySelector('input[name="review_note"]');
    if (input) {
        input.value = note;
    }

    return true;
}



    </script>
@endsection
