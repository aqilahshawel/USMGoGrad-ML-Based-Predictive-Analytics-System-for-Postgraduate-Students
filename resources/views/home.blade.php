@extends('layouts.app')

@section('title', 'Dashboard - USMGradView 360')

@section('content')

<style>
    .hero-section {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(124, 111, 179, 0.15);
    }

    .hero-section h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        font-family: var(--font-display);
    }

    .hero-section .lead {
        opacity: 0.92;
    }

    .user-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid var(--lav-200);
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(124, 111, 179, 0.10);
    }

    .stat-card .icon {
        font-size: 2.25rem;
        color: var(--lav-400);
        margin-bottom: 0.5rem;
    }

    .stat-card .number {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--lav-500);
        font-family: var(--font-display);
    }

    .stat-card .label {
        color: var(--muted);
        font-size: 0.85rem;
        margin-top: 0.4rem;
    }

    .roadmap-tabs {
        margin-bottom: 2rem;
    }

    .tab-content-section {
        background: var(--surface);
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        border: 1px solid var(--lav-200);
    }

    .student-selector {
        margin-bottom: 2rem;
    }

    .timeline-card {
        background: var(--surface);
        border-left: 4px solid var(--lav-400);
        border-radius: 10px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 4px rgba(46, 41, 36, 0.04);
        transition: all 0.25s ease;
    }

    .timeline-card:hover {
        box-shadow: 0 4px 12px rgba(124, 111, 179, 0.10);
        transform: translateX(3px);
    }

    .timeline-card.completed {
        border-left-color: #3d9970;
        background: linear-gradient(to right, rgba(61, 153, 112, 0.04), transparent);
    }

    .timeline-card h5 {
        color: var(--lav-500);
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-family: var(--font-display);
        font-size: 1.1rem;
    }

    .timeline-card .date {
        color: var(--muted);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .timeline-card .date i {
        margin-right: 0.5rem;
        color: var(--lav-400);
    }

    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-badge.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.pending {
        background: var(--gold-100);
        color: #8a6d0b;
    }

    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 1.75rem;
        }

        .user-stats {
            grid-template-columns: 1fr;
        }

        .tab-content-section {
            padding: 1rem;
        }
    }
</style>

<!-- Hero Section -->
@auth
<div class="hero-section">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1><i class="fas fa-user-circle"></i> Welcome, {{ Auth::user()->name }}!</h1>
            <p class="lead mb-0">Manage your academic journey with USMGradView 360</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div style="font-size: 3rem; opacity: 0.3;">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
    </div>
</div>

<!-- User Info Section -->
<div class="card mb-4" style="border-left: 4px solid var(--lav-400); border-top: none; border-right: none; border-bottom: none;">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-2"><i class="fas fa-id-card"></i> Account Information</h6>
                <p><strong>Role:</strong> <span class="badge bg-primary">{{ Auth::user()->getRoleNames()->first() }}</span></p>
            </div>
            @if(Auth::user()->hasRole('student') && Auth::user()->supervisor)
            <div class="col-md-6">
                <h6 class="text-muted mb-2"><i class="fas fa-user-tie"></i> Supervisor</h6>
                <p><strong>{{ Auth::user()->supervisor->name }}</strong></p>
            </div>
            @elseif(Auth::user()->hasRole('lecturer'))
            <div class="col-md-6">
                <h6 class="text-muted mb-2"><i class="fas fa-chalkboard-user"></i> Role Status</h6>
                <p><strong>You are a Supervisor</strong></p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Roadmap Tabs -->
<ul class="nav nav-tabs roadmap-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-section" type="button" role="tab" aria-controls="timeline-section" aria-selected="true">
            <i class="fas fa-timeline"></i> Timeline Roadmap
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="milestone-tab" data-bs-toggle="tab" data-bs-target="#milestone-section" type="button" role="tab" aria-controls="milestone-section" aria-selected="false">
            <i class="fas fa-flag"></i> Milestone Roadmap
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Timeline Section -->
    <div class="tab-pane fade show active" id="timeline-section" role="tabpanel" aria-labelledby="timeline-tab">
        <div class="tab-content-section">
            @if(Auth::user()->hasRole('student'))
                <div class="mb-4">
                    <h4><i class="fas fa-tasks"></i> Your Timeline Roadmap</h4>
                    <p class="text-muted">Track your project milestones and important events</p>
                </div>
                
                @forelse($timelines as $timeline)
                    <div class="timeline-card {{ $timeline->completed ? 'completed' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5><i class="fas fa-check-circle"></i> {{ $timeline->title }}</h5>
                                <div class="date">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($timeline->event_date)->format('M d, Y') }}
                                </div>
                                <p class="text-muted mb-2">
                                    <span class="status-badge {{ $timeline->completed ? 'completed' : 'pending' }}">
                                        {{ $timeline->completed ? '✓ Completed' : '⏳ Pending' }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                @if(!$timeline->completed)
                                    <form action="{{ route('timeline.update', $timeline->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="title" value="{{ $timeline->title }}">
                                        <input type="hidden" name="event_date" value="{{ $timeline->event_date }}">
                                        <input type="hidden" name="completed" value="1">
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark as completed">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                                @if(auth()->user()->id == $timeline->user_id)
                                    <form action="{{ route('timeline.destroy', $timeline->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> No timeline events yet. Go to Timeline section to create one.
                    </div>
                @endforelse
            @elseif(Auth::user()->hasRole('lecturer'))
                <div class="mb-4">
                    <h4><i class="fas fa-users-students"></i> Supervised Students' Timelines</h4>
                    <p class="text-muted">Monitor your students' project progress</p>
                </div>

                <div class="student-selector">
                    <form method="GET" action="{{ route('home') }}">
                        <div class="input-group">
                            <label class="input-group-text" for="selectedStudent">
                                <i class="fas fa-search"></i>
                            </label>
                            <select name="student_id" id="selectedStudent" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Select a Student --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @if(request('student_id'))
                    @php
                        $selectedStudent = $students->where('id', request('student_id'))->first();
                    @endphp

                    @if($selectedStudent)
                        <h5 class="mb-3">{{ $selectedStudent->name }}'s Timeline</h5>
                        @forelse($selectedStudent->timelines as $timeline)
                            <div class="timeline-card {{ $timeline->completed ? 'completed' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5><i class="fas fa-check-circle"></i> {{ $timeline->title }}</h5>
                                        <div class="date">
                                            <i class="fas fa-calendar-alt"></i>
                                            {{ \Carbon\Carbon::parse($timeline->event_date)->format('M d, Y') }}
                                        </div>
                                        <p class="text-muted">
                                            <span class="status-badge {{ $timeline->completed ? 'completed' : 'pending' }}">
                                                {{ $timeline->completed ? '✓ Completed' : '⏳ Pending' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> No timeline events for this student yet.
                            </div>
                        @endforelse
                    @else
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Student not found.
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> Select a student from the dropdown to view their timeline.
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Milestone Section -->
    <div class="tab-pane fade" id="milestone-section" role="tabpanel" aria-labelledby="milestone-tab">
        <div class="tab-content-section">
            @if(Auth::user()->hasRole('student'))
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4><i class="fas fa-flag"></i> Your Milestones</h4>
                        <p class="text-muted">Propose and track your project milestones</p>
                    </div>
                    <a href="{{ route('milestones.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Propose Milestone
                    </a>
                </div>

                @forelse($milestones as $milestone)
                    <div class="timeline-card {{ $milestone->status == 'completed' ? 'completed' : '' }}">
                        <h5><i class="fas fa-flag"></i> {{ $milestone->title }}</h5>
                        <div class="date">
                            <i class="fas fa-calendar-alt"></i>
                            Due: {{ \Carbon\Carbon::parse($milestone->due_date)->format('M d, Y') }}
                        </div>
                        <p class="text-muted mb-2">{{ $milestone->description }}</p>
                        <p class="mb-0">
                            <span class="status-badge {{ $milestone->status == 'completed' ? 'completed' : 'pending' }}">
                                {{ ucfirst($milestone->status) }}
                            </span>
                        </p>
                    </div>
                @empty
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> No milestones yet. <a href="{{ route('milestones.create') }}" class="alert-link">Propose one now</a>
                    </div>
                @endforelse
            @elseif(Auth::user()->hasRole('lecturer'))
                <div class="mb-4">
                    <h4><i class="fas fa-flag"></i> Students' Milestones</h4>
                    <p class="text-muted">Review and manage your students' milestone proposals</p>
                </div>

                <div class="student-selector">
                    <form method="GET" action="{{ route('home') }}">
                        <div class="input-group">
                            <label class="input-group-text" for="milestoneStudentSelect">
                                <i class="fas fa-search"></i>
                            </label>
                            <select name="student_id" id="milestoneStudentSelect" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Select a Student --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @if(request('student_id'))
                    @php
                        $selectedStudent = $students->where('id', request('student_id'))->first();
                    @endphp

                    @if($selectedStudent)
                        <h5 class="mb-3">{{ $selectedStudent->name }}'s Milestones</h5>
                        @forelse($selectedStudent->milestones as $milestone)
                            <div class="timeline-card {{ $milestone->status == 'completed' ? 'completed' : '' }}">
                                <h5><i class="fas fa-flag"></i> {{ $milestone->title }}</h5>
                                <div class="date">
                                    <i class="fas fa-calendar-alt"></i>
                                    Due: {{ \Carbon\Carbon::parse($milestone->due_date)->format('M d, Y') }}
                                </div>
                                <p class="text-muted mb-2">{{ $milestone->description }}</p>
                                <p class="mb-0">
                                    <span class="status-badge {{ $milestone->status == 'completed' ? 'completed' : 'pending' }}">
                                        {{ ucfirst($milestone->status) }}
                                    </span>
                                </p>
                            </div>
                        @empty
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> No milestones proposed yet.
                            </div>
                        @endforelse
                    @else
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Student not found.
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> Select a student from the dropdown to view their milestones.
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@else
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Please log in to access your dashboard.
</div>
@endauth

@endsection
