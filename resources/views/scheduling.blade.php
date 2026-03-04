@extends('layouts.app')

@section('title', 'Scheduling')

@section('styles')
<style>
    .scheduling-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
    }
    .scheduling-header h1 {
        font-family: var(--font-display);
        font-weight: 700;
        margin: 0 0 0.5rem 0;
    }
    .scheduling-header p {
        margin: 0;
        opacity: 0.92;
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
    <div class="scheduling-header">
        <h1><i class="fas fa-calendar-alt"></i> Scheduling</h1>
        <p>Schedule an event or check available time slots.</p>
    </div>

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
            let title = prompt('Enter Event Title:');
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
            alert('Event saved successfully!');
        } else {
            alert('Failed to save event.');
        }
    })
    .catch(error => {
        console.error('Error saving event:', error);
        alert('Error saving event. Check console for details.');
    });
}



    </script>
@endsection
