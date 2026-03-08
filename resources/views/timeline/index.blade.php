@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')

<style>
    .timeline-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
    }

    .timeline-header h1 {
        font-family: var(--font-display);
        font-weight: 700;
    }

    .form-section {
        background: var(--surface);
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        margin-bottom: 2rem;
        border: 1px solid var(--lav-200);
    }

    .form-section h5 {
        font-family: var(--font-display);
        color: var(--lav-500);
    }

    .timeline-container {
        position: relative;
        padding: 1rem 0;
    }

    .timeline-container::before {
        content: '';
        position: absolute;
        left: 30px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, var(--lav-400), var(--lav-200));
    }

    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
        margin-left: 100px;
    }

    .timeline-dot {
        position: absolute;
        left: -70px;
        top: 0;
        width: 60px;
        height: 60px;
        background: var(--surface);
        border: 3px solid var(--lav-400);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--lav-400);
        box-shadow: 0 2px 8px rgba(46,41,36,0.06);
        transition: all 0.25s ease;
    }

    .timeline-item.completed .timeline-dot {
        background: #3d9970;
        border-color: #3d9970;
        color: white;
    }

    .timeline-item:hover .timeline-dot {
        transform: scale(1.08);
    }

    .timeline-content {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        border-left: 4px solid var(--lav-400);
        border: 1px solid var(--lav-200);
        border-left: 4px solid var(--lav-400);
        transition: all 0.25s ease;
    }

    .timeline-item.completed .timeline-content {
        background: linear-gradient(to right, rgba(61, 153, 112, 0.04), transparent);
        border-left-color: #3d9970;
    }

    .timeline-item:hover .timeline-content {
        box-shadow: 0 4px 16px rgba(124,111,179,0.12);
        transform: translateX(3px);
    }

    .timeline-content h5 {
        color: var(--lav-500);
        font-weight: 700;
        margin-bottom: 0.75rem;
        font-family: var(--font-display);
    }

    .timeline-item.completed .timeline-content h5 {
        color: #3d9970;
    }

    .timeline-date {
        color: var(--muted);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .timeline-date i {
        margin-right: 0.5rem;
        color: var(--lav-400);
    }

    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .status-badge.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.pending {
        background: var(--gold-100);
        color: #8a6d3b;
    }

    .timeline-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .timeline-actions .btn {
        flex: 1;
        min-width: 100px;
    }

    .student-selector {
        margin-bottom: 2rem;
    }

    .add-event-btn {
        margin-top: 1rem;
    }

    @media (max-width: 768px) {
        .timeline-container::before {
            left: 20px;
        }

        .timeline-item {
            margin-left: 70px;
        }

        .timeline-dot {
            left: -45px;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }

        .timeline-actions .btn {
            padding: 0.5rem;
            font-size: 0.85rem;
        }
    }
</style>

<div class="timeline-header">
    <h1><i class="fas fa-timeline"></i> Timeline Roadmap</h1>
    <p class="lead mb-0">Track your project milestones and important events</p>
</div>

<!-- Lecturer's Student Selection -->
@if(auth()->user()->hasRole('lecturer') && $students->isNotEmpty())
    <div class="form-section student-selector">
        <h5 class="mb-3"><i class="fas fa-search"></i> Select a Student</h5>
        <form method="GET" action="{{ route('timeline.index') }}">
            <div class="input-group">
                <select name="student_id" id="student_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Students --</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ $selectedStudentId == $student->id ? 'selected' : '' }}>
                            {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
@endif

<!-- Add Event Form (Only for Students) -->
@if(auth()->user()->hasRole('student'))
    <div class="form-section">
        <h5 class="mb-3"><i class="fas fa-plus"></i> Add New Event</h5>
        <form action="{{ route('timeline.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" placeholder="Enter event title" required>
                    @error('title')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('event_date') is-invalid @enderror" id="event_date" name="event_date" required>
                    @error('event_date')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary add-event-btn">
                <i class="fas fa-plus-circle"></i> Add Event
            </button>
        </form>
    </div>
@endif

<!-- Timeline Display -->
<div class="timeline-container">
    @forelse($timelines as $timeline)
        <div class="timeline-item {{ $timeline->completed ? 'completed' : '' }}">
            <div class="timeline-dot">
                @if($timeline->completed)
                    <i class="fas fa-check"></i>
                @else
                    <i class="fas fa-circle"></i>
                @endif
            </div>
            <div class="timeline-content">
                <h5><i class="fas fa-bookmark"></i> {{ $timeline->title }}</h5>
                <div class="timeline-date">
                    <i class="fas fa-calendar-alt"></i>
                    {{ \Carbon\Carbon::parse($timeline->event_date)->format('F d, Y') }}
                </div>
                
                <div class="mb-3">
                    <span class="status-badge {{ $timeline->completed ? 'completed' : 'pending' }}">
                        {{ $timeline->completed ? '✓ Completed' : '⏳ Pending' }}
                    </span>
                </div>

                <div class="timeline-actions">
                    <!-- Mark as Completed (Only for Lecturer or Student) -->
                    @if((auth()->user()->hasRole('student') || auth()->user()->hasRole('lecturer')) && !$timeline->completed)
                        <form action="{{ route('timeline.update', $timeline->id) }}" method="POST" class="flex-grow-1">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="title" value="{{ $timeline->title }}">
                            <input type="hidden" name="event_date" value="{{ $timeline->event_date }}">
                            <input type="hidden" name="completed" value="1">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-check"></i> Mark Completed
                            </button>
                        </form>
                    @endif

                    <!-- Delete Event (Only for the student who created it) -->
                    @if(auth()->user()->id == $timeline->user_id)
                        <form action="{{ route('timeline.destroy', $timeline->id) }}" method="POST" class="flex-grow-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>No events yet!</strong><br>
                    @if(auth()->user()->hasRole('student'))
                        Start by adding your first timeline event above.
                    @else
                        This timeline is empty.
                    @endif
                </div>
            </div>
        </div>
    @endforelse
</div>

@endsection
