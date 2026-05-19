@extends('layouts.app')

@section('title', 'Scheduling')

@section('styles')
<style>
    .scheduling-header {
        background: var(--hue-gradient);
        color: var(--n-900);
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--lav-200);
        border-radius: var(--radius);
        box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
        margin-bottom: 1.5rem;
    }
    .scheduling-header h1 {
        font-family: var(--font-display);
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        color: var(--n-900);
    }
    .scheduling-header p {
        margin: 0;
        color: var(--n-700);
    }
    #calendar {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        border: 1px solid var(--border);
        box-shadow: 0 2px 6px rgba(17,17,17,0.04);
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
    .event-title-inline {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 0.9rem 1rem;
        margin-bottom: 1rem;
    }
    .inline-feedback {
        border-radius: var(--radius-sm);
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
</style>
@endsection

@section('content')
<head><meta name="csrf-token" content="{{ csrf_token() }}"></head>
    <div class="scheduling-header">
        <h1><i class="fas fa-calendar-alt"></i> Scheduling</h1>
        <p>Schedule an event or check available time slots.</p>
    </div>

    <div class="event-title-inline">
        <label for="event-title-input" class="form-label mb-1">Event title for new slots</label>
        <input id="event-title-input" class="form-control" type="text" maxlength="120" value="New Event" placeholder="Type event title">
    </div>

    <div id="legacySchedulingFeedback" class="inline-feedback" role="status" aria-live="polite"></div>

    <div id="calendar"></div>

    <!-- Include FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    let calendarEl = document.getElementById('calendar');

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        selectable: true,
        editable: true,
        events: '/get-events',  // Fetch events from the backend
        select: function (info) {
            let titleInput = document.getElementById('event-title-input');
            let title = titleInput ? titleInput.value.trim() : '';
            if (!title) {
                title = 'New Event';
            }
            if (title) {
                saveEvent(title, info.startStr, info.endStr);
                calendar.addEvent({
                    title: title,
                    start: info.startStr,
                    end: info.endStr
                });
            }
        }
    });

    calendar.render();
});


function saveEvent(title, start, end) {
    console.log("Saving Event:", { title, start, end });
    const feedbackElement = document.getElementById('legacySchedulingFeedback');

    // Ensure CSRF token exists before calling getAttribute
    let csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token missing! Make sure <meta name='csrf-token'> exists in your HTML.");
        return;
    }

    fetch('/save-event', {
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
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data);
        if (data.message) {
            showFeedback(data.message, 'success');
        } else {
            showFeedback('Failed to save event.', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving event:', error);
        showFeedback('Error saving event. Check console for details.', 'error');
    });

    function showFeedback(message, type) {
        if (!feedbackElement) {
            return;
        }

        feedbackElement.textContent = message;
        feedbackElement.classList.add('is-visible');
        feedbackElement.classList.remove('feedback-success', 'feedback-error');
        feedbackElement.classList.add(type === 'error' ? 'feedback-error' : 'feedback-success');

        window.clearTimeout(showFeedback.hideTimer);
        showFeedback.hideTimer = window.setTimeout(() => {
            feedbackElement.classList.remove('is-visible');
        }, 4000);
    }
}



    </script>
@endsection
