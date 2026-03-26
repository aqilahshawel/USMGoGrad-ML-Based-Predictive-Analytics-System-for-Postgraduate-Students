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
    .calendar-container.has-sidebar #calendar {
        flex: 0 1 auto;
        width: calc(100% - 380px);
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
        width: 380px;
        flex-shrink: 0;
    }
    @media (max-width: 1200px) {
        .calendar-container {
            flex-direction: column;
        }
        .calendar-container.has-sidebar #calendar {
            width: 100%;
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
    .inline-modal-backdrop {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.35);
        z-index: 1200;
        padding: 1rem;
    }
    .inline-modal-backdrop.is-open {
        display: flex;
    }
    .inline-modal {
        width: min(520px, 100%);
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    .inline-modal-header {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--lav-200);
    }
    .inline-modal-header h5 {
        margin: 0;
        color: var(--n-900);
    }
    .inline-modal-body {
        padding: 1rem;
    }
    .inline-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding: 0.9rem 1rem;
        border-top: 1px solid var(--lav-200);
    }
    .inline-modal .form-label {
        font-weight: 600;
        margin-bottom: 0.3rem;
    }
    .meeting-slot-preview {
        font-size: 0.88rem;
        color: var(--n-700);
        margin-bottom: 0.75rem;
    }
    .inline-feedback {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        display: none;
    }
    .inline-feedback.is-visible {
        display: block;
    }
    .inline-feedback.feedback-success {
        background: #e8f7ef;
        border-color: #bde5cc;
        color: #11663b;
    }
    .inline-feedback.feedback-error {
        background: #fff1f3;
        border-color: #f5c2c7;
        color: #8b1f2d;
    }
    .meeting-time-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    @media (max-width: 576px) {
        .meeting-time-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<head><meta name="csrf-token" content="{{ csrf_token() }}"></head>
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

    <div id="schedulingInlineFeedback" class="inline-feedback" role="status" aria-live="polite"></div>

    <div class="calendar-container {{ ($canCreateEvents || ($isStudent && ! $supervisorMissing)) ? 'has-sidebar' : '' }}">
        <div id="calendar"></div>

        @if($canCreateEvents)
        <div class="calendar-sidebar">
            <div class="meeting-history-card">
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

            <div class="meeting-history-card mb-0">
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
        </div>
        @elseif($isStudent && ! $supervisorMissing)
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
    <div id="eventTitleModal" class="inline-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="eventTitleModalHeading">
        <div class="inline-modal">
            <div class="inline-modal-header">
                <h5 id="eventTitleModalHeading"><i class="fas fa-calendar-plus me-1"></i> Add Event</h5>
            </div>
            <form id="eventTitleForm">
                <div class="inline-modal-body">
                    <label for="eventTitleInput" class="form-label">Event title</label>
                    <input id="eventTitleInput" type="text" class="form-control" maxlength="120" required>
                </div>
                <div class="inline-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-close-modal="event">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <div id="rejectNoteModal" class="inline-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="rejectNoteModalHeading">
        <div class="inline-modal">
            <div class="inline-modal-header">
                <h5 id="rejectNoteModalHeading"><i class="fas fa-comment-dots me-1"></i> Reject Request</h5>
            </div>
            <form id="rejectNoteForm">
                <div class="inline-modal-body">
                    <label for="rejectNoteInput" class="form-label">Optional note for student</label>
                    <textarea id="rejectNoteInput" class="form-control" rows="4" maxlength="500" placeholder="Add context for the rejection (optional)"></textarea>
                </div>
                <div class="inline-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-close-modal="reject">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($isStudent && ! $supervisorMissing)
    <div id="meetingRequestModal" class="inline-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="meetingRequestModalHeading">
        <div class="inline-modal">
            <div class="inline-modal-header">
                <h5 id="meetingRequestModalHeading"><i class="fas fa-hand-paper me-1"></i> Apply for Meeting</h5>
            </div>
            <form id="meetingRequestForm">
                <div class="inline-modal-body">
                    <p class="meeting-slot-preview mb-2" id="meetingSlotPreview"></p>

                    <div class="meeting-time-grid">
                        <div>
                            <label for="meetingDateInput" class="form-label">Meeting date</label>
                            <input id="meetingDateInput" type="date" class="form-control" required>
                        </div>
                        <div>
                            <label for="meetingStartTimeInput" class="form-label">Start time</label>
                            <input id="meetingStartTimeInput" type="time" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="meetingEndTimeInput" class="form-label">End time</label>
                        <input id="meetingEndTimeInput" type="time" class="form-control" required>
                    </div>

                    <label for="meetingTopicInput" class="form-label">Meeting topic</label>
                    <input id="meetingTopicInput" type="text" class="form-control mb-3" maxlength="120" required>

                    <label for="meetingNoteInput" class="form-label">Optional note for your supervisor</label>
                    <textarea id="meetingNoteInput" class="form-control" rows="3" maxlength="500"></textarea>
                </div>
                <div class="inline-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-close-modal="meeting">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
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
    const meetingSlotLocale = {
        dateStyle: 'medium',
        timeStyle: 'short'
    };

    const eventTitleModal = document.getElementById('eventTitleModal');
    const eventTitleForm = document.getElementById('eventTitleForm');
    const eventTitleInput = document.getElementById('eventTitleInput');

    const meetingRequestModal = document.getElementById('meetingRequestModal');
    const meetingRequestForm = document.getElementById('meetingRequestForm');
    const meetingTopicInput = document.getElementById('meetingTopicInput');
    const meetingNoteInput = document.getElementById('meetingNoteInput');
    const meetingSlotPreview = document.getElementById('meetingSlotPreview');
    const meetingDateInput = document.getElementById('meetingDateInput');
    const meetingStartTimeInput = document.getElementById('meetingStartTimeInput');
    const meetingEndTimeInput = document.getElementById('meetingEndTimeInput');

    const rejectNoteModal = document.getElementById('rejectNoteModal');
    const rejectNoteForm = document.getElementById('rejectNoteForm');
    const rejectNoteInput = document.getElementById('rejectNoteInput');
    const inlineFeedback = document.getElementById('schedulingInlineFeedback');

    let pendingEventSelection = null;
    let pendingMeetingSelection = null;
    let pendingRejectForm = null;

    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: canCreateEvents || canSubmitMeetingRequests,
        editable: false,
        events: '/get-events',
        select: function (info) {
            if (canCreateEvents) {
                pendingEventSelection = {
                    start: info.startStr,
                    end: info.endStr
                };
                openModal(eventTitleModal, eventTitleInput);
                return;
            }

            if (!canSubmitMeetingRequests) {
                return;
            }

            openMeetingRequestModal(info.startStr, info.endStr);
        }
    });

    calendar.render();

    document.querySelectorAll('.js-recommended-slot').forEach(function (button) {
        button.addEventListener('click', function () {
            openMeetingRequestModal(button.dataset.start, button.dataset.end);
        });
    });

    if (eventTitleForm) {
        eventTitleForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!pendingEventSelection) {
                closeModal(eventTitleModal);
                return;
            }

            const title = (eventTitleInput.value || '').trim();
            if (!title) {
                eventTitleInput.focus();
                return;
            }

            saveEvent(title, pendingEventSelection.start, pendingEventSelection.end)
                .then((data) => {
                    calendar.refetchEvents();
                    if (data.message) {
                        showInlineFeedback(data.message, 'success');
                    }
                    closeModal(eventTitleModal);
                    pendingEventSelection = null;
                    eventTitleForm.reset();
                    calendar.unselect();
                })
                .catch((error) => {
                    showInlineFeedback(error.message, 'error');
                });
        });
    }

    if (meetingRequestForm) {
        meetingRequestForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!pendingMeetingSelection) {
                closeModal(meetingRequestModal);
                return;
            }

            const topic = (meetingTopicInput.value || '').trim();
            if (!topic) {
                meetingTopicInput.focus();
                return;
            }

            const dateValue = meetingDateInput ? meetingDateInput.value : '';
            const startTimeValue = meetingStartTimeInput ? meetingStartTimeInput.value : '';
            const endTimeValue = meetingEndTimeInput ? meetingEndTimeInput.value : '';

            if (!dateValue || !startTimeValue || !endTimeValue) {
                showInlineFeedback('Please select meeting date and time.', 'error');
                return;
            }

            const startIso = combineDateAndTime(dateValue, startTimeValue);
            const endIso = combineDateAndTime(dateValue, endTimeValue);

            if (new Date(endIso) <= new Date(startIso)) {
                showInlineFeedback('End time must be later than start time.', 'error');
                return;
            }

            const note = (meetingNoteInput.value || '').trim();

            submitMeetingRequest(topic, startIso, endIso, note)
                .then((data) => {
                    calendar.refetchEvents();
                    if (data.message) {
                        showInlineFeedback(data.message, 'success');
                    }
                    closeModal(meetingRequestModal);
                    pendingMeetingSelection = null;
                    meetingRequestForm.reset();
                    calendar.unselect();
                })
                .catch((error) => {
                    showInlineFeedback(error.message, 'error');
                });
        });
    }

    [meetingDateInput, meetingStartTimeInput, meetingEndTimeInput].forEach(function (inputElement) {
        if (!inputElement) {
            return;
        }

        inputElement.addEventListener('change', updateMeetingSlotPreview);
    });

    if (rejectNoteForm) {
        rejectNoteForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!pendingRejectForm) {
                closeModal(rejectNoteModal);
                return;
            }

            const hiddenInput = pendingRejectForm.querySelector('input[name="review_note"]');
            if (hiddenInput) {
                hiddenInput.value = (rejectNoteInput.value || '').trim();
            }

            const formToSubmit = pendingRejectForm;
            pendingRejectForm = null;
            closeModal(rejectNoteModal);
            rejectNoteForm.reset();
            formToSubmit.submit();
        });
    }

    document.querySelectorAll('[data-close-modal="event"]').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingEventSelection = null;
            if (eventTitleForm) {
                eventTitleForm.reset();
            }
            closeModal(eventTitleModal);
            calendar.unselect();
        });
    });

    document.querySelectorAll('[data-close-modal="meeting"]').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingMeetingSelection = null;
            if (meetingRequestForm) {
                meetingRequestForm.reset();
            }
            closeModal(meetingRequestModal);
            calendar.unselect();
        });
    });

    document.querySelectorAll('[data-close-modal="reject"]').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingRejectForm = null;
            if (rejectNoteForm) {
                rejectNoteForm.reset();
            }
            closeModal(rejectNoteModal);
        });
    });

    if (eventTitleModal) {
        eventTitleModal.addEventListener('click', function (event) {
            if (event.target === eventTitleModal) {
                pendingEventSelection = null;
                if (eventTitleForm) {
                    eventTitleForm.reset();
                }
                closeModal(eventTitleModal);
                calendar.unselect();
            }
        });
    }

    if (meetingRequestModal) {
        meetingRequestModal.addEventListener('click', function (event) {
            if (event.target === meetingRequestModal) {
                pendingMeetingSelection = null;
                if (meetingRequestForm) {
                    meetingRequestForm.reset();
                }
                closeModal(meetingRequestModal);
                calendar.unselect();
            }
        });
    }

    if (rejectNoteModal) {
        rejectNoteModal.addEventListener('click', function (event) {
            if (event.target === rejectNoteModal) {
                pendingRejectForm = null;
                if (rejectNoteForm) {
                    rejectNoteForm.reset();
                }
                closeModal(rejectNoteModal);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (eventTitleModal && eventTitleModal.classList.contains('is-open')) {
            pendingEventSelection = null;
            if (eventTitleForm) {
                eventTitleForm.reset();
            }
            closeModal(eventTitleModal);
            calendar.unselect();
            return;
        }

        if (meetingRequestModal && meetingRequestModal.classList.contains('is-open')) {
            pendingMeetingSelection = null;
            if (meetingRequestForm) {
                meetingRequestForm.reset();
            }
            closeModal(meetingRequestModal);
            calendar.unselect();
            return;
        }

        if (rejectNoteModal && rejectNoteModal.classList.contains('is-open')) {
            pendingRejectForm = null;
            if (rejectNoteForm) {
                rejectNoteForm.reset();
            }
            closeModal(rejectNoteModal);
        }
    });

    window.openRejectNoteModal = function (formElement) {
        if (!rejectNoteModal) {
            return;
        }

        pendingRejectForm = formElement;
        if (rejectNoteForm) {
            rejectNoteForm.reset();
        }
        openModal(rejectNoteModal, rejectNoteInput);
    };

    function openMeetingRequestModal(start, end) {
        if (!meetingRequestModal) {
            return;
        }

        pendingMeetingSelection = { start, end };

        const parsedStart = parseSelectionDate(start);
        const parsedEnd = parseSelectionDate(end);
        const includesTime = typeof start === 'string' && start.includes('T');

        if (meetingDateInput) {
            meetingDateInput.value = toDateInputValue(parsedStart);
        }

        if (meetingStartTimeInput) {
            meetingStartTimeInput.value = includesTime
                ? toTimeInputValue(parsedStart)
                : '09:00';
        }

        if (meetingEndTimeInput) {
            meetingEndTimeInput.value = includesTime
                ? toTimeInputValue(parsedEnd)
                : '09:30';
        }

        updateMeetingSlotPreview();

        openModal(meetingRequestModal, meetingTopicInput);
    }

    function parseSelectionDate(value) {
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return new Date();
        }

        return parsed;
    }

    function toDateInputValue(dateValue) {
        const year = dateValue.getFullYear();
        const month = String(dateValue.getMonth() + 1).padStart(2, '0');
        const day = String(dateValue.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function toTimeInputValue(dateValue) {
        const hours = String(dateValue.getHours()).padStart(2, '0');
        const minutes = String(dateValue.getMinutes()).padStart(2, '0');
        return hours + ':' + minutes;
    }

    function combineDateAndTime(dateValue, timeValue) {
        return dateValue + 'T' + timeValue;
    }

    function updateMeetingSlotPreview() {
        if (!meetingSlotPreview || !meetingDateInput || !meetingStartTimeInput || !meetingEndTimeInput) {
            return;
        }

        const dateValue = meetingDateInput.value;
        const startTimeValue = meetingStartTimeInput.value;
        const endTimeValue = meetingEndTimeInput.value;

        if (!dateValue || !startTimeValue || !endTimeValue) {
            meetingSlotPreview.textContent = 'Choose meeting date and time.';
            return;
        }

        const startValue = new Date(combineDateAndTime(dateValue, startTimeValue));
        const endValue = new Date(combineDateAndTime(dateValue, endTimeValue));

        meetingSlotPreview.textContent = 'Selected slot: '
            + startValue.toLocaleDateString(undefined, { dateStyle: meetingSlotLocale.dateStyle })
            + ', '
            + startValue.toLocaleTimeString(undefined, { timeStyle: 'short' })
            + ' - '
            + endValue.toLocaleTimeString(undefined, { timeStyle: 'short' });
    }

    function showInlineFeedback(message, type) {
        if (!inlineFeedback) {
            return;
        }

        inlineFeedback.textContent = message;
        inlineFeedback.classList.add('is-visible');
        inlineFeedback.classList.remove('feedback-success', 'feedback-error');

        if (type === 'error') {
            inlineFeedback.classList.add('feedback-error');
        } else {
            inlineFeedback.classList.add('feedback-success');
        }

        window.clearTimeout(showInlineFeedback.hideTimer);
        showInlineFeedback.hideTimer = window.setTimeout(function () {
            inlineFeedback.classList.remove('is-visible');
        }, 5000);
    }

    function openModal(modalElement, focusElement) {
        if (!modalElement) {
            return;
        }

        modalElement.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (focusElement) {
            setTimeout(function () {
                focusElement.focus();
            }, 20);
        }
    }

    function closeModal(modalElement) {
        if (!modalElement) {
            return;
        }

        modalElement.classList.remove('is-open');

        if (!document.querySelector('.inline-modal-backdrop.is-open')) {
            document.body.style.overflow = '';
        }
    }
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
    if (typeof window.openRejectNoteModal === 'function') {
        window.openRejectNoteModal(formElement);
        return false;
    }

    return true;
}



    </script>
@endsection
