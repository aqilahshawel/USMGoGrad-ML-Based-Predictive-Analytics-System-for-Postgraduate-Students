@extends('layouts.app')

@section('title', 'Dashboard - USMGoGrad')

@section('content')

<style>
    .hero-section {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 14px;
        margin-bottom: 1rem;
        box-shadow: 0 4px 20px rgba(124, 111, 179, 0.15);
    }

    .hero-section h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
        font-family: var(--font-display);
    }

    .hero-section .lead {
        opacity: 0.92;
        font-size: 0.95rem;
        margin: 0;
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
        margin-bottom: 0;
    }

    .tab-content-section {
        background: var(--surface);
        border-radius: 0 0 14px 14px;
        padding: 1.25rem 1.25rem 0.75rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        border: 1px solid var(--lav-200);
        border-top: none;
        overflow: hidden;
    }

    .student-selector {
        margin-bottom: 1.25rem;
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

    /* Account info card */
    .account-info-card {
        background: var(--surface);
        border-left: 4px solid var(--lav-400);
        border-radius: 10px;
        padding: 0.85rem 1.25rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 4px rgba(46, 41, 36, 0.04);
    }
    .account-info-card h6 {
        font-size: 0.78rem;
        color: var(--muted);
        margin-bottom: 0.3rem;
    }
    .account-info-card p {
        margin: 0;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 1.4rem;
        }

        .user-stats {
            grid-template-columns: 1fr;
        }

        .tab-content-section {
            padding: 1rem;
        }
    }

    /* ═══════════════════════════════════════════
       HORIZONTAL JOURNEY PATH (Home page)
       ═══════════════════════════════════════════ */
    .hjourney-wrap {
        overflow-x: auto;
        overflow-y: visible;
        padding: 0.25rem 0 0.25rem;
        -webkit-overflow-scrolling: touch;
        /* shrink to content so no phantom space appears to the right */
        display: inline-block;
        min-width: 100%;
        box-sizing: border-box;
    }
    .hjourney-wrap::-webkit-scrollbar {
        height: 6px;
    }
    .hjourney-wrap::-webkit-scrollbar-thumb {
        background: var(--lav-200);
        border-radius: 3px;
    }

    .hjourney {
        display: inline-flex;
        align-items: flex-start;
        gap: 0;
        padding: 0.25rem 0 0.25rem;
    }

    /* Connector line — sits between nodes, no overflow */
    .hjourney-connector {
        width: 48px;
        height: 4px;
        border-radius: 2px;
        align-self: flex-start;
        /* center vertically with 46px node: (46-4)/2 + 4px top padding = 25px */
        margin-top: 25px;
        flex-shrink: 0;
        background: var(--lav-200);
    }
    .hjourney-connector.connector-done   { background: #3d9970; }
    .hjourney-connector.connector-active { background: linear-gradient(90deg, #3d9970, var(--lav-300)); }
    .hjourney-connector.connector-future { background: var(--n-200); }

    .hjourney-stop {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 100px;
        max-width: 130px;
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }
    .hjourney-marker {
        min-width: 60px;
        max-width: 60px;
    }

    .hjourney-node {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        border: 3px solid var(--surface);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        background: var(--n-100);
        color: var(--n-700);
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }
    .hjourney-stop:hover .hjourney-node {
        transform: scale(1.12);
    }

    .hnode-done {
        background: #3d9970 !important;
        color: white !important;
        border-color: #e8f5e9 !important;
    }
    .hnode-current {
        background: white !important;
        border-color: var(--lav-400) !important;
        box-shadow: 0 0 0 4px rgba(124,111,179,0.18), 0 2px 8px rgba(0,0,0,0.1) !important;
        animation: h-pulse 2s ease-out infinite;
    }
    @keyframes h-pulse {
        0%   { box-shadow: 0 0 0 4px rgba(124,111,179,0.18), 0 0 0 0 rgba(124,111,179,0.3); }
        70%  { box-shadow: 0 0 0 4px rgba(124,111,179,0.18), 0 0 0 12px rgba(124,111,179,0); }
        100% { box-shadow: 0 0 0 4px rgba(124,111,179,0.18), 0 0 0 0 rgba(124,111,179,0); }
    }

    .hjourney-title {
        font-size: 0.74rem;
        font-weight: 600;
        text-align: center;
        margin-top: 0.5rem;
        line-height: 1.2;
        color: var(--text);
        word-break: break-word;
    }
    .hjourney-date {
        font-size: 0.68rem;
        color: var(--muted);
        margin-top: 0.15rem;
    }
    .hjourney-label {
        font-size: 0.68rem;
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.5rem;
    }
</style>

<!-- Hero Section -->
@auth
<div class="hero-section">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h1><i class="fas fa-user-circle"></i> Welcome, {{ Auth::user()->name }}!</h1>
            <p class="lead">Manage your academic journey with USMGoGrad</p>
        </div>
        <div class="col-md-3 text-md-end">
            <div style="font-size: 2.5rem; opacity: 0.25;">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
    </div>
</div>

<!-- User Info Section — compact -->
<div class="account-info-card mb-3">
    <div class="row g-0">
        <div class="col-md-6">
            <h6><i class="fas fa-id-card"></i> Account Information</h6>
            <p><strong>Role:</strong> <span class="badge bg-primary">{{ Auth::user()->getRoleNames()->first() }}</span></p>
        </div>
        @if(Auth::user()->hasRole('student') && Auth::user()->supervisor)
        <div class="col-md-6">
            <h6><i class="fas fa-user-tie"></i> Supervisor</h6>
            <p><strong>{{ Auth::user()->supervisor->name }}</strong></p>
        </div>
        @elseif(Auth::user()->hasRole('lecturer'))
        <div class="col-md-6">
            <h6><i class="fas fa-chalkboard-user"></i> Role Status</h6>
            <p><strong>You are a Supervisor</strong></p>
        </div>
        @endif
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
            @php
                $hStageStyles = [
                    'prep'            => ['icon' => 'fa-book-open',      'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
                    'proposal'        => ['icon' => 'fa-file-signature', 'color' => '#e07d45', 'bg' => '#fff5ef'],
                    'data_collection' => ['icon' => 'fa-chart-bar',      'color' => '#3d9970', 'bg' => '#e8f5e9'],
                    'thesis'          => ['icon' => 'fa-graduation-cap', 'color' => '#c9a227', 'bg' => '#fffbf0'],
                    'extension'       => ['icon' => 'fa-hourglass-half', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
                    'overdue'         => ['icon' => 'fa-exclamation-triangle','color' => '#cc4b4b', 'bg' => '#fdecea'],
                    'max_duration'    => ['icon' => 'fa-flag-checkered', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
                ];
                $hDefaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

                $hStops = $timelines
                    ->filter(fn($t) => $t->system_key && (str_starts_with($t->system_key, 'stage:') || $t->system_key === 'max_duration'))
                    ->sortBy('event_date')
                    ->values();

                if ($hStops->isEmpty()) {
                    $hStops = $timelines->sortBy('event_date')->take(8)->values();
                }

                $hTotal = $hStops->count();
                $hDone  = $hStops->where('completed', true)->count();
            @endphp

            @if(Auth::user()->hasRole('lecturer'))
                <div class="mb-3">
                    <h5 style="font-family:var(--font-display); margin-bottom: 0.2rem;"><i class="fas fa-route"></i> Student Journey</h5>
                    <p class="text-muted mb-2" style="font-size: 0.83rem;">Select a student to preview their candidature progress</p>
                </div>
                <div class="student-selector">
                    <form method="GET" action="{{ route('home') }}">
                        <div class="input-group">
                            <label class="input-group-text" for="selectedStudent"><i class="fas fa-search"></i></label>
                            <select name="student_id" id="selectedStudent" class="form-select" onchange="this.form.submit()">
                                <option value="">— Choose a student —</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            @else
                <div class="mb-1">
                    <h5 style="font-family:var(--font-display); margin-bottom: 0.1rem;"><i class="fas fa-route"></i> My Journey</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">Your candidature progress at a glance</p>
                </div>
            @endif

            @if($hStops->isNotEmpty())
                {{-- Progress mini-bar --}}
                @if($progressSnapshot)
                <div class="d-flex gap-2 mb-1 flex-wrap" style="font-size: 0.82rem;">
                    <span class="badge rounded-pill" style="background:var(--lav-100); color:var(--lav-500); font-size:0.82rem; padding:0.4rem 0.8rem;">
                        Semester {{ $progressSnapshot['current_semester'] }}
                    </span>
                    <span class="badge rounded-pill" style="background:#e8f5e9; color:#2e7d32; font-size:0.82rem; padding:0.4rem 0.8rem;">
                        {{ $hDone }}/{{ $hTotal }} Done
                    </span>
                    @if($progressSnapshot['risk_status'] === 'on_track')
                        <span class="badge rounded-pill" style="background:#e8f5e9; color:#2e7d32; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-check-circle"></i> On Track
                        </span>
                    @elseif($progressSnapshot['risk_status'] === 'at_risk')
                        <span class="badge rounded-pill" style="background:#fff5ef; color:#e07d45; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-exclamation-circle"></i> At Risk
                        </span>
                    @else
                        <span class="badge rounded-pill" style="background:#fdecea; color:#cc4b4b; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-times-circle"></i> Overdue
                        </span>
                    @endif
                </div>
                @endif

                {{-- Horizontal journey path --}}
                <div class="hjourney-wrap">
                    <div class="hjourney">
                        {{-- Start pip --}}
                        <div class="hjourney-stop hjourney-marker">
                            <div class="hjourney-node" style="background:var(--lav-500); width:32px; height:32px; font-size:0.7rem;">
                                <i class="fas fa-play" style="color:white;"></i>
                            </div>
                            <div class="hjourney-label">Start</div>
                        </div>

                        @foreach($hStops as $idx => $stop)
                            @php
                                $sk = $stop->stage_key ?? ($stop->system_key ? str_replace(['stage:','max_duration'], ['','max_duration'], $stop->system_key) : null);
                                $st = $hStageStyles[$sk] ?? $hDefaultStyle;
                                $done = (bool) $stop->completed;
                                $isCurr = !$done && ($stop->status ?? 'approved') === 'approved'
                                          && $hStops->where('completed', false)->where('status', 'approved')->first()?->id === $stop->id;
                                // Connector colour: done→done = green, done→current = gradient, else grey
                                $prevDone = $idx === 0 ? true : (bool) $hStops[$idx - 1]->completed;
                                $connClass = ($prevDone && $done) ? 'connector-done'
                                           : ($prevDone && $isCurr ? 'connector-active' : 'connector-future');
                            @endphp

                            {{-- Connector before this stop --}}
                            <div class="hjourney-connector {{ $connClass }}"></div>

                            <div class="hjourney-stop {{ $done ? 'hstop-done' : ($isCurr ? 'hstop-current' : 'hstop-future') }}">
                                <div class="hjourney-node {{ $done ? 'hnode-done' : ($isCurr ? 'hnode-current' : '') }}"
                                     @if(!$done && !$isCurr) style="background: {{ $st['bg'] }}; color: {{ $st['color'] }}; border-color: {{ $st['bg'] }};" @endif>
                                    @if($done)
                                        <i class="fas fa-check"></i>
                                    @elseif($isCurr)
                                        <i class="fas fa-location-dot" style="color:{{ $st['color'] }};"></i>
                                    @else
                                        <i class="fas {{ $st['icon'] }}"></i>
                                    @endif
                                </div>
                                <div class="hjourney-title" @if($done) style="color:#3d9970;" @endif>
                                    {{ Str::limit($stop->title, 28) }}
                                </div>
                                <div class="hjourney-date">
                                    {{ \Carbon\Carbon::parse($stop->event_date)->format('M Y') }}
                                </div>
                            </div>
                        @endforeach

                        {{-- Connector before finish --}}
                        <div class="hjourney-connector {{ $hDone >= $hTotal ? 'connector-done' : 'connector-future' }}"></div>

                        {{-- Finish pip --}}
                        <div class="hjourney-stop hjourney-marker">
                            <div class="hjourney-node" style="background:{{ $hDone >= $hTotal ? '#3d9970' : 'var(--n-200)' }}; width:32px; height:32px; font-size:0.7rem;">
                                <i class="fas fa-flag-checkered" style="color:{{ $hDone >= $hTotal ? 'white' : 'var(--n-700)' }};"></i>
                            </div>
                            <div class="hjourney-label">Finish</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-road" style="font-size:2rem; color:var(--lav-300);"></i>
                    <p class="mt-2 mb-0">
                        @if(Auth::user()->hasRole('lecturer'))
                            Select a student above to see their journey.
                        @else
                            Your journey path will appear once your start date is configured.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Milestone Section -->
    <div class="tab-pane fade" id="milestone-section" role="tabpanel" aria-labelledby="milestone-tab">
        <div class="tab-content-section">
            @if(Auth::user()->hasRole('student'))
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 style="margin-bottom:0.1rem;"><i class="fas fa-flag"></i> Your Milestones</h5>
                        <p class="text-muted mb-0" style="font-size:0.82rem;">Propose and track your project milestones</p>
                    </div>
                    <a href="{{ route('milestones.create') }}" class="btn btn-primary btn-sm">
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
                <div class="mb-3">
                    <h5 style="margin-bottom:0.1rem;"><i class="fas fa-flag"></i> Students' Milestones</h5>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">Review and manage your students' milestone proposals</p>
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
                                        {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
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
                        <h6 class="mb-2">{{ $selectedStudent->name }}'s Milestones</h6>
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