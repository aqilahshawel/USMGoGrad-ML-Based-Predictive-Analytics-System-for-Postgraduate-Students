@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')
@php
    $activeTab = request('tab', 'roadmap');

    $checkpointStyles = [
        'ekc500_admin_approval' => ['icon' => 'fa-file-circle-check', 'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
        'proposal_review' => ['icon' => 'fa-file-signature', 'color' => '#e07d45', 'bg' => '#fff5ef'],
        'research_review' => ['icon' => 'fa-magnifying-glass-chart', 'color' => '#3d9970', 'bg' => '#e8f5e9'],
        'nos' => ['icon' => 'fa-envelope-open-text', 'color' => '#d97706', 'bg' => '#fff5ef'],
        'final_draft_submission' => ['icon' => 'fa-file-arrow-up', 'color' => '#b7791f', 'bg' => '#fffbf0'],
        'viva' => ['icon' => 'fa-user-graduate', 'color' => '#c2410c', 'bg' => '#fdecea'],
        'max_duration' => ['icon' => 'fa-flag-checkered', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
    ];

    $defaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

    $checkpointFieldMap = [
        'checkpoint:ekc500_admin_approval' => 'ekc500_admin_approved',
        'checkpoint:proposal_review' => 'proposal_review_date',
        'checkpoint:research_review' => 'research_review_date',
        'checkpoint:nos' => 'nos_date',
        'checkpoint:final_draft_submission' => 'final_draft_submission_date',
        'checkpoint:viva' => 'viva_date',
    ];

    $checkpointInputs = [
        ['field' => 'ekc500_admin_approved', 'title' => 'EKC500 Admin Approval Checked'],
        ['field' => 'proposal_review_date', 'title' => 'Proposal Review Date'],
        ['field' => 'research_review_date', 'title' => 'Research Review Date'],
        ['field' => 'nos_date', 'title' => 'Notice of Submission Date'],
        ['field' => 'final_draft_submission_date', 'title' => 'Final Draft Submission Date'],
        ['field' => 'viva_date', 'title' => 'Viva Date'],
    ];

    $systemStops = $timelines
        ->filter(fn($timeline) => $timeline->system_key && str_starts_with($timeline->system_key, 'checkpoint:'))
        ->sortBy('event_date')
        ->values();

    $customRows = $timelines
        ->filter(fn($timeline) => ! $timeline->is_system_generated)
        ->sortBy('event_date')
        ->values();

    $maxDurationRow = $timelines->firstWhere('system_key', 'max_duration');
    $totalStops = $systemStops->count();
    $completedStops = $systemStops->where('completed', true)->count();
    $currentStopId = optional($systemStops->firstWhere('completed', false))->id;

    $overdueStops = $systemStops->filter(function ($stop) use ($progressSnapshot) {
        return $progressSnapshot
            && ! $stop->completed
            && $stop->semester_number
            && $progressSnapshot['current_semester'] > $stop->semester_number;
    });
@endphp

<style>
    .timeline-page {
        padding-bottom: 2rem;
    }

    .timeline-hero {
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
        color: var(--n-900);
        padding: 1.6rem 2rem;
        border-radius: 14px;
        margin-bottom: 1rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .timeline-hero h2,
    .timeline-hero h3,
    .timeline-hero h4 {
        margin: 0;
        color: var(--n-900);
        font-family: var(--font-display);
    }

    .timeline-hero p {
        margin: 0.35rem 0 0;
        color: var(--n-700);
    }

    .timeline-toolbar {
        margin-bottom: 1rem;
    }

    .timeline-panel {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .timeline-panel-header {
        padding: 1.15rem 1.35rem 0.75rem;
    }

    .timeline-panel-header h3,
    .timeline-panel-header h4,
    .timeline-panel-header h5 {
        margin: 0;
        color: var(--n-900);
        font-family: var(--font-display);
    }

    .timeline-panel-header p {
        margin: 0.35rem 0 0;
        color: var(--n-700);
    }

    .timeline-panel-body {
        padding: 0 1.35rem 1.35rem;
    }

    .timeline-tabs {
        display: flex;
        gap: 0.65rem;
        margin: 0 0 1rem;
        border-bottom: none;
    }

    .timeline-tabs .nav-link {
        border: 1px solid var(--lav-200);
        border-radius: 999px;
        color: var(--lav-500);
        background: var(--surface);
        font-weight: 600;
        padding: 0.7rem 1rem;
    }

    .timeline-tabs .nav-link.active {
        background: var(--lav-500);
        border-color: var(--lav-500);
        color: #fff;
    }

    .progress-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .progress-card {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        text-align: center;
    }

    .progress-card .pc-value {
        font-family: var(--font-display);
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1;
    }

    .progress-card .pc-label {
        font-size: 0.8rem;
        color: var(--muted);
        margin-top: 0.35rem;
    }

    .progress-card .pc-meta {
        margin-top: 0.45rem;
        font-size: 0.76rem;
        color: var(--n-700);
    }

    .risk-on_track .pc-value {
        color: #2e7d32;
    }

    .risk-at_risk .pc-value {
        color: #d97706;
    }

    .risk-overdue .pc-value {
        color: #c2410c;
    }

    .risk-pending .pc-value {
        color: var(--lav-500);
    }

    .risk-high_risk .pc-value {
        color: #b42318;
    }

    .road-path {
        position: relative;
        padding: 1rem 0 2rem;
        width: 100%;
    }

    .road-path::before {
        content: '';
        position: absolute;
        left: 62px;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--lav-300), var(--lav-200) 70%, #e0e0e0);
        border-radius: 2px;
    }

    .road-marker {
        padding-left: 42px;
        position: relative;
        z-index: 2;
        margin-bottom: 0.6rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .road-marker-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: #fff;
        flex-shrink: 0;
    }

    .road-marker-label {
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--muted);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .road-stop {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 0.7rem;
        padding-left: 100px;
    }

    .road-node {
        position: absolute;
        left: 62px;
        transform: translateX(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        z-index: 2;
        border: 3px solid var(--surface);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .road-node.node-completed {
        background: #3d9970;
        color: #fff;
    }

    .road-node.node-current {
        background: #fff;
        border-color: var(--lav-400);
        box-shadow: 0 0 0 4px rgba(124, 111, 179, 0.18), 0 2px 8px rgba(0, 0, 0, 0.10);
    }

    .road-card {
        position: relative;
        flex: 1;
        background: var(--surface);
        border-radius: 14px;
        padding: 0.95rem 1.1rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 10px rgba(46, 41, 36, 0.04);
    }

    .road-card::after {
        content: '';
        position: absolute;
        top: 18px;
        left: -7px;
        width: 12px;
        height: 12px;
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-right: none;
        border-top: none;
        transform: rotate(45deg);
    }

    .road-card.completed {
        border-left: 4px solid #3d9970;
    }

    .road-card.warning {
        border-left: 4px solid #d97706;
    }

    .road-card.current {
        border-left: 4px solid var(--lav-500);
    }

    .road-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        flex-wrap: wrap;
        margin-bottom: 0.25rem;
    }

    .road-card-title {
        font-family: var(--font-display);
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--n-900);
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }

    .road-card-meta {
        font-size: 0.8rem;
        color: var(--muted);
        margin-bottom: 0.4rem;
    }

    .road-card-description {
        font-size: 0.86rem;
        color: var(--n-700);
        margin-bottom: 0.5rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .pill-completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .pill-current {
        background: var(--lav-100);
        color: var(--lav-500);
    }

    .pill-upcoming {
        background: var(--n-100);
        color: var(--n-700);
    }

    .pill-warning {
        background: #fff5ef;
        color: #d97706;
    }

    .checkpoint-grid {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 1.5rem;
    }

    .timeline-tab-section {
        display: grid;
        gap: 1.25rem;
    }

    .checkpoint-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .checkpoint-field label {
        display: block;
        margin-bottom: 0.45rem;
        font-weight: 600;
        color: var(--n-900);
    }

    .checkpoint-field small {
        display: block;
        color: var(--muted);
        margin-top: 0.35rem;
        font-size: 0.78rem;
    }

    .checkpoint-field input,
    .checkpoint-select,
    .timeline-form input {
        width: 100%;
        border: 1.5px solid var(--lav-200);
        border-radius: 10px;
        padding: 0.8rem 0.95rem;
        background: #fff;
    }

    .checkpoint-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-bottom: 1rem;
    }

    .checkpoint-pill {
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.84rem;
        font-weight: 600;
        background: var(--lav-100);
        color: var(--lav-500);
    }

    .prediction-panel {
        display: grid;
        gap: 1rem;
    }

    .prediction-hero {
        border-radius: 16px;
        padding: 1.2rem;
        background: linear-gradient(135deg, #fdfbf3 0%, #fff5ef 100%);
        border: 1px solid var(--gold-200);
    }

    .prediction-score {
        font-family: var(--font-display);
        font-size: 3rem;
        line-height: 1;
        color: var(--n-900);
        margin: 0.35rem 0;
    }

    .risk-low {
        color: #2e7d32;
    }

    .risk-medium {
        color: #d97706;
    }

    .risk-high {
        color: #c2410c;
    }

    .risk-pending {
        color: var(--lav-500);
    }

    .checkpoint-note {
        padding: 0.95rem 1rem;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    .checkpoint-note.warning {
        background: #fff5ef;
        color: #9a4d20;
        border: 1px solid #f8c9a8;
    }

    .checkpoint-note.info {
        background: var(--lav-100);
        color: var(--lav-500);
        border: 1px solid var(--lav-200);
    }

    .timeline-form-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr auto;
        gap: 0.75rem;
        align-items: end;
    }

    .additional-events {
        margin-top: 1.5rem;
    }

    .additional-event-item {
        border: 1px solid var(--lav-200);
        border-radius: 12px;
        padding: 0.95rem 1rem;
        margin-bottom: 0.75rem;
        background: #fff;
    }

    .additional-event-header {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .additional-event-title {
        font-weight: 700;
        color: var(--n-900);
    }

    .additional-event-meta {
        color: var(--muted);
        font-size: 0.82rem;
        margin-top: 0.2rem;
    }

    .additional-event-actions {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-top: 0.75rem;
    }

    @media (max-width: 900px) {
        .checkpoint-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .timeline-hero {
            padding: 1.35rem 1.15rem;
        }

        .road-path::before {
            left: 20px;
        }

        .road-stop {
            padding-left: 50px;
        }

        .road-node {
            left: 20px;
            width: 36px;
            height: 36px;
            font-size: 0.85rem;
        }

        .road-card::after {
            display: none;
        }

        .road-marker {
            padding-left: 4px;
        }

        .timeline-form-grid,
        .checkpoint-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="timeline-page">
    <section class="timeline-hero">
        <h2><i class="fas fa-route"></i> Timeline Tracking</h2>
    </section>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    @if(session('prediction_warning'))
        <div class="alert alert-warning mb-3">{{ session('prediction_warning') }}</div>
    @endif

    @if(auth()->user()->hasRole('lecturer') && $students->isNotEmpty())
        <section class="timeline-panel timeline-toolbar">
            <div class="timeline-panel-body pt-3">
                <form method="GET" action="{{ route('timeline.index') }}">
                    <label for="student_id" class="form-label fw-semibold">Select Student</label>
                    <select id="student_id" name="student_id" class="checkpoint-select" onchange="this.form.submit()">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ (int) $selectedStudentId === (int) $student->id ? 'selected' : '' }}>
                                {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                            </option>
                        @endforeach
                    </select>
                    @if($activeTab === 'checkpoint')
                        <input type="hidden" name="tab" value="checkpoint">
                    @endif
                </form>
            </div>
        </section>
    @endif

    <ul class="nav timeline-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'roadmap' ? 'active' : '' }}" id="roadmap-tab" data-bs-toggle="tab" data-bs-target="#roadmap-panel" type="button" role="tab" aria-selected="{{ $activeTab === 'roadmap' ? 'true' : 'false' }}">
                        <i class="fas fa-map"></i> Timeline Roadmap
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'checkpoint' ? 'active' : '' }}" id="checkpoint-tab" data-bs-toggle="tab" data-bs-target="#checkpoint-panel" type="button" role="tab" aria-selected="{{ $activeTab === 'checkpoint' ? 'true' : 'false' }}">
                        <i class="fas fa-brain"></i> Timeline Update
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'roadmap' ? 'show active' : '' }}" id="roadmap-panel" role="tabpanel" aria-labelledby="roadmap-tab">
                    <div class="timeline-tab-section">
                    @if($progressSnapshot)
                        <div class="progress-summary">
                            <div class="progress-card risk-{{ $progressSnapshot['risk_status'] }}">
                                <div class="pc-value">{{ $progressSnapshot['current_semester'] ?: '—' }}</div>
                                <div class="pc-label">Current Semester</div>
                            </div>
                            <div class="progress-card">
                                <div class="pc-value" style="color: var(--lav-500);">{{ $completedStops }}/{{ $totalStops }}</div>
                                <div class="pc-label">Core Checkpoints Completed</div>
                            </div>
                            <div class="progress-card risk-{{ $progressSnapshot['risk_status'] }}">
                                <div class="pc-value">{{ $progressSnapshot['display_risk_label'] ?? str_replace('_', ' ', ucwords($progressSnapshot['risk_status'], '_')) }}</div>
                                <div class="pc-label">Current Status</div>
                                @if(!empty($progressSnapshot['show_ml_pending_badge']))
                                    <div class="pc-meta">ML prediction pending</div>
                                @elseif(!empty($progressSnapshot['has_checkpoint_delay']))
                                    <div class="pc-meta">Checkpoint delay detected</div>
                                @endif
                            </div>
                            <div class="progress-card">
                                <div class="pc-value" style="font-size: 1rem; color: var(--gold-500); padding-top: 0.25rem;">{{ $progressSnapshot['stage_title'] ?? '—' }}</div>
                                <div class="pc-label">Current Stage</div>
                            </div>
                        </div>
                    @endif

                    @if($overdueStops->isNotEmpty())
                        <div class="checkpoint-note warning mb-4">
                            <strong><i class="fas fa-bell"></i> Reminder</strong><br>
                            The following checkpoints are already past their target semester: {{ $overdueStops->pluck('title')->implode(', ') }}.
                        </div>
                    @endif

                    @if(auth()->user()->hasRole('student'))
                        <section class="timeline-panel">
                            <div class="timeline-panel-header">
                                <h4>Add New Event</h4>
                            </div>
                            <div class="timeline-panel-body timeline-form">
                                <form action="{{ route('timeline.store') }}" method="POST">
                                    @csrf
                                    <div class="timeline-form-grid">
                                        <div>
                                            <label for="title" class="form-label fw-semibold">Title</label>
                                            <input type="text" id="title" name="title" value="{{ old('title') }}" class="@error('title') is-invalid @enderror" placeholder="e.g. Internal mock presentation" required>
                                            @error('title') <span class="invalid-feedback d-block mt-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="event_date" class="form-label fw-semibold">Target Date</label>
                                            <input type="date" id="event_date" name="event_date" value="{{ old('event_date') }}" class="@error('event_date') is-invalid @enderror" required>
                                            @error('event_date') <span class="invalid-feedback d-block mt-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </section>
                    @endif

                    @if($systemStops->isNotEmpty())
                        <div class="road-path">
                            <div class="road-marker">
                                <div class="road-marker-icon" style="background: var(--lav-500);"><i class="fas fa-play"></i></div>
                                <div class="road-marker-label">Registration Completed</div>
                            </div>

                            @foreach($systemStops as $stop)
                                @php
                                    $key = str_replace('checkpoint:', '', $stop->system_key);
                                    $style = $checkpointStyles[$key] ?? $defaultStyle;
                                    $actualField = $checkpointFieldMap[$stop->system_key] ?? null;
                                    $actualValue = $actualField && $checkpoint ? $checkpoint->{$actualField} : null;
                                    $isCompleted = (bool) $stop->completed;
                                    $isCurrent = ! $isCompleted && $currentStopId === $stop->id;
                                    $isOverdue = ! $isCompleted && $progressSnapshot && $stop->semester_number && $progressSnapshot['current_semester'] > $stop->semester_number;
                                    $cardClass = $isCompleted ? 'completed' : ($isOverdue ? 'warning' : ($isCurrent ? 'current' : ''));
                                @endphp
                                <div class="road-stop">
                                    <div class="road-node {{ $isCompleted ? 'node-completed' : ($isCurrent ? 'node-current' : '') }}"
                                         @if(! $isCompleted && ! $isCurrent) style="background: {{ $style['bg'] }}; color: {{ $style['color'] }}; border-color: {{ $style['bg'] }};" @endif>
                                        @if($isCompleted)
                                            <i class="fas fa-check"></i>
                                        @elseif($isCurrent)
                                            <i class="fas fa-location-dot" style="color: {{ $style['color'] }};"></i>
                                        @else
                                            <i class="fas {{ $style['icon'] }}"></i>
                                        @endif
                                    </div>

                                    <div class="road-card {{ $cardClass }}">
                                        <div class="road-card-header">
                                            <div class="road-card-title">
                                                <i class="fas {{ $style['icon'] }}" style="color: {{ $style['color'] }};"></i>
                                                {{ $stop->title }}
                                            </div>
                                            @if($isCompleted)
                                                <span class="status-pill pill-completed"><i class="fas fa-check-circle"></i> Completed</span>
                                            @elseif($isOverdue)
                                                <span class="status-pill pill-warning"><i class="fas fa-bell"></i> Reminder Needed</span>
                                            @elseif($isCurrent)
                                                <span class="status-pill pill-current"><i class="fas fa-spinner fa-pulse"></i> Current Focus</span>
                                            @else
                                                <span class="status-pill pill-upcoming"><i class="fas fa-hourglass-start"></i> Upcoming</span>
                                            @endif
                                        </div>

                                        <div class="road-card-meta">
                                            Target by {{ optional($stop->event_date)->format('d M Y') }}
                                            @if($stop->semester_number)
                                                &middot; Semester {{ $stop->semester_number }}
                                            @endif
                                        </div>

                                        @if($actualField === 'ekc500_admin_approved' && $actualValue)
                                            <div class="checkpoint-pill">Passed</div>
                                        @elseif($actualValue)
                                            <div class="checkpoint-pill">Completed on {{ $actualValue->format('d M Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <div class="road-marker" style="margin-top: 0.65rem;">
                                <div class="road-marker-icon" style="background: {{ $completedStops >= $totalStops && $totalStops > 0 ? '#3d9970' : 'var(--n-300)' }};">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="road-marker-label">
                                    @if($maxDurationRow)
                                        Maximum candidature duration: {{ optional($maxDurationRow->event_date)->format('d M Y') }}
                                    @else
                                        Finish Line
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="checkpoint-note info">
                            No generated checkpoint roadmap is available yet. Complete the student registration details first.
                        </div>
                    @endif

                    @if($customRows->isNotEmpty())
                        <div class="additional-events">
                            <section class="timeline-panel">
                                <div class="timeline-panel-header">
                                    <h4>Additional Timeline Events</h4>
                                    <p>Extra events outside the core candidature checkpoint sequence.</p>
                                </div>
                                <div class="timeline-panel-body">
                                    @foreach($customRows as $row)
                                        <div class="additional-event-item">
                                            <div class="additional-event-header">
                                                <div>
                                                    <div class="additional-event-title">{{ $row->title }}</div>
                                                    <div class="additional-event-meta">
                                                        {{ optional($row->event_date)->format('d M Y') }}
                                                        &middot; {{ ucfirst($row->status ?? 'approved') }}
                                                    </div>
                                                </div>
                                                @if($row->status === 'approved')
                                                    <span class="status-pill pill-completed"><i class="fas fa-check-circle"></i> Approved</span>
                                                @elseif($row->status === 'pending')
                                                    <span class="status-pill pill-warning"><i class="fas fa-clock"></i> Pending Review</span>
                                                @else
                                                    <span class="status-pill pill-warning"><i class="fas fa-ban"></i> Rejected</span>
                                                @endif
                                            </div>

                                            @if(auth()->user()->hasRole('lecturer') && $row->status === 'pending')
                                                <div class="additional-event-actions">
                                                    <form action="{{ route('timeline.approve', $row->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check-circle"></i> Approve</button>
                                                    </form>
                                                    <form action="{{ route('timeline.reject', $row->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-ban"></i> Reject</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    @endif
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'checkpoint' ? 'show active' : '' }}" id="checkpoint-panel" role="tabpanel" aria-labelledby="checkpoint-tab">
                    @if($targetUser)
                        <div class="checkpoint-grid">
                            <section class="timeline-panel">
                                <div class="timeline-panel-header">
                                    <h3>Checkpoint Updates</h3>
                                </div>
                                <div class="timeline-panel-body">
                                    <div class="checkpoint-meta">
                                        <span class="checkpoint-pill">Cluster: {{ $targetUser->cluster ?? 'Missing' }}</span>
                                        <span class="checkpoint-pill">Study Type: {{ $targetUser->study_mode ? str_replace('_', ' ', ucwords($targetUser->study_mode, '_')) : 'Missing' }}</span>
                                        <span class="checkpoint-pill">Registration: {{ optional($targetUser->start_date)->format('d M Y') ?? 'Missing' }}</span>
                                    </div>

                                    @if($missingPrerequisites !== [])
                                        <div class="checkpoint-note warning mb-3">
                                            Prediction cannot run yet. Missing: {{ implode(', ', $missingPrerequisites) }}.
                                        </div>
                                    @endif

                                    @if(auth()->user()->hasRole('student'))
                                        <form method="POST" action="{{ route('timeline.checkpoints.update') }}" id="checkpoint-update-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="checkpoint-form-grid">
                                                @foreach($checkpointInputs as $index => $input)
                                                    <div class="checkpoint-field">
                                                        <label for="{{ $input['field'] }}">{{ $input['title'] }}</label>
                                                        @if($input['field'] === 'ekc500_admin_approved')
                                                            <input type="hidden" name="ekc500_admin_approved" value="0">
                                                            <div class="form-check mt-2">
                                                                <input
                                                                    id="{{ $input['field'] }}"
                                                                    type="checkbox"
                                                                    name="{{ $input['field'] }}"
                                                                    value="1"
                                                                    data-sequence-index="{{ $index }}"
                                                                    class="form-check-input @error($input['field']) is-invalid @enderror"
                                                                    {{ old($input['field'], $checkpoint->{$input['field']} ?? false) ? 'checked' : '' }}
                                                                >
                                                                <label class="form-check-label" for="{{ $input['field'] }}">Approved</label>
                                                            </div>
                                                        @else
                                                            <input
                                                                id="{{ $input['field'] }}"
                                                                type="date"
                                                                name="{{ $input['field'] }}"
                                                                data-sequence-index="{{ $index }}"
                                                                value="{{ old($input['field'], optional($checkpoint->{$input['field']})->format('Y-m-d')) }}"
                                                                class="@error($input['field']) is-invalid @enderror"
                                                            >
                                                        @endif
                                                        @error($input['field'])
                                                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="mt-3">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="checkpoint-form-grid">
                                            @foreach($checkpointInputs as $input)
                                                <div class="checkpoint-field">
                                                    <label>{{ $input['title'] }}</label>
                                                    <input type="text" value="{{ $input['field'] === 'ekc500_admin_approved' ? (($checkpoint->{$input['field']} ?? false) ? 'Passed' : 'Not passed yet') : (optional($checkpoint->{$input['field']})->format('d M Y') ?? 'Not submitted') }}" disabled>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <aside class="prediction-panel">
                                <section class="timeline-panel">
                                    <div class="timeline-panel-header">
                                        <h3>Prediction Output</h3>
                                    </div>
                                    <div class="timeline-panel-body">
                                        @if(!is_null($checkpoint->got_probability))
                                            <div class="prediction-hero">
                                                <div class="text-uppercase small fw-semibold text-muted">GoT Probability</div>
                                                <div class="prediction-score risk-{{ $checkpoint->risk_level }}">{{ number_format($checkpoint->got_probability * 100, 1) }}%</div>
                                                <div class="fw-semibold text-uppercase risk-{{ $checkpoint->risk_level }}">
                                                    {{ ucfirst($checkpoint->risk_level ?? 'unknown') }} Risk
                                                </div>
                                                <p class="mb-0 mt-2 text-muted">
                                                    Prediction: {{ $checkpoint->predicted_got ? 'Graduate on Time' : 'Potential Delay' }}
                                                </p>
                                            </div>

                                            <div class="checkpoint-note info mt-3">
                                                Last predicted: {{ optional($checkpoint->last_predicted_at)->format('d M Y H:i') ?? 'Not available' }}<br>
                                                Model version: {{ $checkpoint->model_version ?? 'Unknown' }}
                                            </div>
                                        @elseif($checkpoint->risk_level === 'pending')
                                            <div class="prediction-hero">
                                                <div class="text-uppercase small fw-semibold text-muted">Prediction Status</div>
                                                <div class="prediction-score risk-pending">Pending</div>
                                                <div class="fw-semibold text-uppercase risk-pending">Cold Start</div>
                                                <p class="mb-0 mt-2 text-muted">
                                                    The model is waiting for enough actual checkpoint timing before generating a probability.
                                                </p>
                                            </div>

                                            <div class="checkpoint-note info mt-3">
                                                Last checked: {{ optional($checkpoint->last_predicted_at)->format('d M Y H:i') ?? 'Not available' }}<br>
                                                Model version: {{ $checkpoint->model_version ?? 'Unknown' }}
                                            </div>
                                        @else
                                            <div class="checkpoint-note info">
                                                No prediction has been generated yet. Save checkpoint statuses after the student registration details are complete.
                                            </div>
                                        @endif
                                    </div>
                                </section>
                            </aside>
                        </div>
                    @else
                        <div class="checkpoint-note info mt-3">
                            @if(auth()->user()->hasRole('lecturer'))
                                Select a student to view checkpoint updates and prediction status.
                            @else
                                Your checkpoint panel will appear once your candidature details are available.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
</div>

@if(auth()->user()->hasRole('student'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('checkpoint-update-form');
        if (!form) {
            return;
        }

        const sequenceFields = [
            'ekc500_admin_approved',
            'proposal_review_date',
            'research_review_date',
            'nos_date',
            'final_draft_submission_date',
            'viva_date'
        ].map(function (id) {
            return document.getElementById(id);
        }).filter(Boolean);

        function syncSequence() {
            sequenceFields.forEach(function (field, index) {
                if (index === 0) {
                    field.removeAttribute('disabled');
                    return;
                }

                const previousField = sequenceFields[index - 1];
                const previousComplete = previousField.type === 'checkbox'
                    ? previousField.checked
                    : previousField.value !== '';

                if (!previousComplete && field.value === '') {
                    field.setAttribute('disabled', 'disabled');
                } else {
                    field.removeAttribute('disabled');
                }
            });
        }

        sequenceFields.forEach(function (field) {
            field.addEventListener('input', syncSequence);
            field.addEventListener('change', syncSequence);
        });

        syncSequence();
    });
</script>
@endif

@endsection
