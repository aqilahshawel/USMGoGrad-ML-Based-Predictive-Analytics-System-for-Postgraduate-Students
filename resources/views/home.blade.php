@extends('layouts.app')

@section('title', 'Dashboard - USMGoGrad')

@section('content')

<style>
    .hero-section {
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
        color: var(--n-900);
        padding: 1.5rem 2rem;
        border-radius: 14px;
        margin-bottom: 1rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .hero-section h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    .hero-section .lead {
        color: var(--n-700);
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

    .admin-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .admin-panel {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.25rem 1.4rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        border: 1px solid var(--lav-200);
    }

    .admin-panel h5 {
        margin: 0;
        color: var(--n-900);
        font-family: var(--font-display);
    }

    .admin-panel-copy {
        margin: 0.55rem 0 0;
        color: var(--muted);
        max-width: 56rem;
    }

    .admin-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .admin-alert-group + .admin-alert-group {
        margin-top: 1rem;
    }

    .admin-alert-heading {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--n-900);
    }

    .admin-alert-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 0.7rem;
    }

    .admin-alert-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 0.95rem;
        border-radius: 12px;
        background: var(--n-50);
        border: 1px solid var(--n-100);
    }

    .admin-alert-item strong {
        display: block;
        color: var(--n-900);
    }

    .admin-alert-item span {
        color: var(--muted);
        font-size: 0.84rem;
    }

    .admin-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .admin-pill.warning {
        background: #fff5ef;
        color: #d97706;
    }

    .admin-pill.info {
        background: var(--lav-100);
        color: var(--lav-500);
    }

    .admin-empty {
        margin: 0;
        padding: 1rem;
        border-radius: 12px;
        background: #f5fbf7;
        color: #2e7d32;
        border: 1px solid #d8efe0;
    }

    .overdue-notice-panel {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .overdue-notice-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.6rem;
    }

    .overdue-notice-header h5 {
        margin: 0;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    .overdue-notice-count {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: #fff5ef;
        color: #d97706;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .overdue-notice-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 0.6rem;
    }

    .overdue-notice-item {
        border: 1px solid var(--n-100);
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
        background: var(--n-50);
    }

    .overdue-notice-item p {
        margin: 0;
        color: var(--n-700);
        font-size: 0.88rem;
    }

    .overdue-notice-item .notice-meta {
        margin-top: 0.35rem;
        color: var(--muted);
        font-size: 0.78rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
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

    .prediction-summary {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        padding: 1.2rem 1.4rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .prediction-summary.low {
        border-color: #cfe8d3;
        background: linear-gradient(135deg, #f5fbf7 0%, #ffffff 100%);
    }

    .prediction-summary.medium {
        border-color: #f8c9a8;
        background: linear-gradient(135deg, #fff9f3 0%, #ffffff 100%);
    }

    .prediction-summary.high {
        border-color: #f2c5c5;
        background: linear-gradient(135deg, #fff6f6 0%, #ffffff 100%);
    }

    .prediction-summary.pending,
    .prediction-summary.unavailable {
        background: linear-gradient(135deg, #f8f6ff 0%, #ffffff 100%);
    }

    .prediction-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .prediction-kicker {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 0.35rem;
    }

    .prediction-title {
        margin: 0;
        font-size: 1.1rem;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    .prediction-copy {
        margin: 0.45rem 0 0;
        color: var(--muted);
        max-width: 700px;
    }

    .prediction-metric {
        font-size: 2.3rem;
        font-weight: 700;
        line-height: 1;
        font-family: var(--font-display);
        color: var(--lav-500);
        text-align: right;
    }

    .prediction-metric.low {
        color: #2e7d32;
    }

    .prediction-metric.medium {
        color: #d97706;
    }

    .prediction-metric.high {
        color: #c2410c;
    }

    .prediction-metric.pending,
    .prediction-metric.unavailable {
        color: var(--lav-500);
    }

    .prediction-badges {
        display: flex;
        gap: 0.55rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .prediction-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.42rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .prediction-badge.low {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .prediction-badge.medium {
        background: #fff5ef;
        color: #d97706;
    }

    .prediction-badge.high {
        background: #fdecea;
        color: #c2410c;
    }

    .prediction-badge.pending,
    .prediction-badge.unavailable {
        background: var(--lav-100);
        color: var(--lav-500);
    }

    .assignments-section {
        margin-top: 1.5rem;
    }

    .assignments-section h3 {
        color: var(--lav-500);
        font-weight: 700;
        margin-bottom: 1.25rem;
        font-size: 1.45rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-family: var(--font-display);
    }

    .supervisor-card {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        margin-bottom: 1rem;
        border: 1px solid var(--lav-200);
        border-top: 4px solid var(--lav-400);
        transition: all 0.25s ease;
    }

    .supervisor-card:hover {
        box-shadow: 0 4px 16px rgba(124, 111, 179, 0.12);
        transform: translateY(-1px);
    }

    .supervisor-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--lav-500);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-family: var(--font-display);
    }

    .students-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .students-list li {
        padding: 0.75rem;
        background: var(--n-50);
        border-radius: 8px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        border-left: 3px solid var(--lav-400);
    }

    .students-list li i {
        margin-right: 0.75rem;
        color: var(--lav-400);
    }

    .no-students {
        color: var(--muted);
        font-style: italic;
        padding: 1rem;
        background: var(--n-50);
        border-radius: 8px;
        text-align: center;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        background: var(--n-50);
        border-radius: 14px;
        border: 1px solid var(--border);
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--n-200);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .hero-section h1 {
            font-size: 1.4rem;
        }

        .user-stats {
            grid-template-columns: 1fr;
        }

        .admin-dashboard-grid {
            grid-template-columns: 1fr;
        }

        .tab-content-section {
            padding: 1rem;
        }

        .prediction-metric {
            text-align: left;
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
            <p class="lead">
                @if(Auth::user()->hasRole('admin'))
                    Manage academic journey with USMGoGrad
                @else
                    Manage your academic journey with USMGoGrad
                @endif
            </p>
        </div>
        <div class="col-md-3 text-md-end">
            <div style="font-size: 2.5rem; opacity: 0.25;">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>
    </div>
</div>

@if($recentOverdueNotifications->isNotEmpty())
<div class="overdue-notice-panel">
    <div class="overdue-notice-header">
        <h5><i class="fas fa-bell"></i> Overdue Checkpoint Notifications</h5>
        @if($unreadOverdueCount > 0)
            <span class="overdue-notice-count">{{ $unreadOverdueCount }} unread</span>
        @endif
    </div>

    <ul class="overdue-notice-list">
        @foreach($recentOverdueNotifications as $notice)
            @php
                $data = $notice->data ?? [];
                $timelineUrl = $data['timeline_url'] ?? route('timeline.index', ['tab' => 'checkpoint']);
            @endphp
            <li class="overdue-notice-item">
                <p>{{ $data['message'] ?? 'An overdue checkpoint reminder is available.' }}</p>
                <div class="notice-meta">
                    <span><i class="fas fa-clock"></i> {{ optional($notice->created_at)->diffForHumans() }}</span>
                    <a href="{{ $timelineUrl }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-arrow-right"></i> Open Timeline
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif

@if(Auth::user()->hasRole('admin'))
    <div class="user-stats">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-tie"></i></div>
            <div class="number">{{ $adminStats['total_supervisors'] }}</div>
            <div class="label">Active Supervisors</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-user-graduate"></i></div>
            <div class="number">{{ $adminStats['total_students'] }}</div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-link"></i></div>
            <div class="number">{{ $adminStats['assigned_students'] }}</div>
            <div class="label">Students Assigned</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="number">{{ $adminStats['unassigned_students'] + $adminStats['incomplete_candidature'] }}</div>
            <div class="label">Items Needing Attention</div>
        </div>
    </div>

    <div class="assignments-section">
        <h3><i class="fas fa-list"></i> Current Assignments</h3>

        @if($supervisors->count() > 0)
            @foreach($supervisors as $supervisor)
                <div class="supervisor-card">
                    <div class="supervisor-name">
                        <i class="fas fa-user-tie"></i> {{ $supervisor->name }}
                    </div>

                    @if($supervisor->students->count() > 0)
                        <ul class="students-list">
                            @foreach($supervisor->students as $student)
                                <li>
                                    <i class="fas fa-user-graduate"></i>
                                    {{ $student->name }}
                                    @if($student->cluster)
                                        ({{ $student->cluster }})
                                    @endif
                                    @if($student->start_date || $student->study_mode)
                                        -
                                        @if($student->start_date)
                                            {{ $student->start_date->format('d M Y') }}
                                        @else
                                            Missing registration date
                                        @endif
                                        /
                                        @if($student->study_mode)
                                            {{ str_replace('_', ' ', ucwords($student->study_mode, '_')) }}
                                        @else
                                            Missing study type
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="no-students">
                            <i class="fas fa-inbox"></i> No students assigned yet
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>No Supervisors Available</h5>
                <p class="text-muted">There are no supervisors in the system yet.</p>
            </div>
        @endif
    </div>
@elseif(Auth::user()->hasRole('student') || Auth::user()->hasRole('lecturer'))
    @php
        $predictionState = 'unavailable';
        $predictionTitle = Auth::user()->hasRole('lecturer') ? 'Select a student to view the latest GoT prediction' : 'Prediction unavailable';
        $predictionCopy = Auth::user()->hasRole('lecturer')
            ? 'Choose one of your supervisees to show their current machine-learning prediction on the dashboard.'
            : 'Complete the registration details and record checkpoint progress to unlock the prediction.';
        $predictionMetric = '—';
        $predictionBadges = [];
        $predictionHref = Auth::user()->hasRole('lecturer') && $dashboardTargetUser
            ? route('timeline.index', ['student_id' => $dashboardTargetUser->id, 'tab' => 'checkpoint'])
            : route('timeline.index', ['tab' => 'checkpoint']);

        if ($dashboardTargetUser) {
            $displayName = Auth::user()->hasRole('lecturer') ? $dashboardTargetUser->name . "'s" : 'Your';

            if ($dashboardMissingPrerequisites !== []) {
                $predictionTitle = $displayName . ' GoT prediction is waiting for registration details';
                $predictionCopy = 'Missing required fields: ' . implode(', ', $dashboardMissingPrerequisites) . '.';
                $predictionBadges[] = ['class' => 'unavailable', 'text' => 'Registration details incomplete'];
            } elseif ($predictionWarning) {
                $predictionTitle = $displayName . ' GoT prediction could not be refreshed';
                $predictionCopy = $predictionWarning;
                $predictionBadges[] = ['class' => 'unavailable', 'text' => 'Refresh failed'];
            } elseif ($dashboardCheckpoint && !is_null($dashboardCheckpoint->got_probability)) {
                $predictionState = $dashboardCheckpoint->risk_level ?? 'unavailable';
                $predictionTitle = $displayName . ' GoT prediction';
                $predictionCopy = $dashboardCheckpoint->predicted_got
                    ? 'The model currently expects this student to graduate on time if the current pace is maintained.'
                    : 'The model currently detects a risk of delay based on the recorded checkpoint timing.';
                $predictionMetric = number_format($dashboardCheckpoint->got_probability * 100, 1) . '%';
                $predictionBadges[] = ['class' => $predictionState, 'text' => ucfirst($predictionState) . ' risk'];
                $predictionBadges[] = [
                    'class' => $predictionState,
                    'text' => $dashboardCheckpoint->predicted_got ? 'Graduate on time' : 'Potential delay'
                ];

                if ($dashboardCheckpoint->last_predicted_at) {
                    $predictionBadges[] = [
                        'class' => 'pending',
                        'text' => 'Updated ' . $dashboardCheckpoint->last_predicted_at->format('d M Y H:i')
                    ];
                }
            } elseif ($dashboardCheckpoint && $dashboardCheckpoint->risk_level === 'pending') {
                $predictionState = 'pending';
                $predictionTitle = $displayName . ' GoT prediction is in cold start';
                $predictionCopy = 'The student profile is complete, but the model waits for the first actual checkpoint date before producing a probability.';
                $predictionMetric = 'Pending';
                $predictionBadges[] = ['class' => 'pending', 'text' => 'Cold start'];
            } else {
                $predictionTitle = $displayName . ' GoT prediction has not been generated yet';
                $predictionCopy = 'Open the timeline checkpoint tab to submit milestone dates and trigger the first prediction.';
                $predictionBadges[] = ['class' => 'unavailable', 'text' => 'No prediction yet'];
            }
        }
    @endphp
@endif

@if(Auth::user()->hasRole('student') || (Auth::user()->hasRole('lecturer')))
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
                    'ekc500_admin_approval' => ['icon' => 'fa-file-circle-check', 'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
                    'proposal_review' => ['icon' => 'fa-file-signature', 'color' => '#e07d45', 'bg' => '#fff5ef'],
                    'research_review' => ['icon' => 'fa-magnifying-glass-chart', 'color' => '#3d9970', 'bg' => '#e8f5e9'],
                    'nos'             => ['icon' => 'fa-envelope-open-text', 'color' => '#d97706', 'bg' => '#fff5ef'],
                    'final_draft_submission' => ['icon' => 'fa-file-arrow-up', 'color' => '#b7791f', 'bg' => '#fffbf0'],
                    'viva'            => ['icon' => 'fa-user-graduate', 'color' => '#c2410c', 'bg' => '#fdecea'],
                    'extension'       => ['icon' => 'fa-hourglass-half', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
                    'overdue'         => ['icon' => 'fa-exclamation-triangle','color' => '#cc4b4b', 'bg' => '#fdecea'],
                    'max_duration'    => ['icon' => 'fa-flag-checkered', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
                ];
                $hDefaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

                $hStops = $timelines
                    ->filter(fn($t) => $t->system_key && (str_starts_with($t->system_key, 'checkpoint:') || $t->system_key === 'max_duration'))
                    ->sortBy('event_date')
                    ->values();

                if ($hStops->isEmpty()) {
                    $hStops = $timelines->sortBy('event_date')->take(8)->values();
                }

                $hTotal = $hStops->count();
                $hDone  = $hStops->where('completed', true)->count();
                $overdueHomeStops = $progressSnapshot
                    ? $hStops->filter(fn($stop) => ! $stop->completed && $stop->semester_number && $progressSnapshot['current_semester'] > $stop->semester_number)
                    : collect();
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
                @if($overdueHomeStops->isNotEmpty())
                <div class="alert alert-warning mt-3 mb-3" role="alert">
                    <i class="fas fa-bell"></i>
                    Reminder: {{ $overdueHomeStops->pluck('title')->implode(', ') }} {{ $overdueHomeStops->count() === 1 ? 'is' : 'are' }} already past the target semester.
                </div>
                @endif

                {{-- Progress mini-bar --}}
                @if($progressSnapshot)
                <div class="d-flex gap-2 mb-1 flex-wrap" style="font-size: 0.82rem;">
                    <span class="badge rounded-pill" style="background:var(--lav-100); color:var(--lav-500); font-size:0.82rem; padding:0.4rem 0.8rem;">
                        Semester {{ $progressSnapshot['current_semester'] }}
                    </span>
                    <span class="badge rounded-pill" style="background:#e8f5e9; color:#2e7d32; font-size:0.82rem; padding:0.4rem 0.8rem;">
                        {{ $hDone }}/{{ $hTotal }} Done
                    </span>
                    @if($progressSnapshot['risk_status'] === 'pending')
                        <span class="badge rounded-pill" style="background:var(--lav-100); color:var(--lav-500); font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-hourglass-half"></i> Pending
                        </span>
                    @elseif($progressSnapshot['risk_status'] === 'on_track')
                        <span class="badge rounded-pill" style="background:#e8f5e9; color:#2e7d32; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-check-circle"></i> On Track
                        </span>
                    @elseif($progressSnapshot['risk_status'] === 'high_risk')
                        <span class="badge rounded-pill" style="background:#fdecea; color:#b42318; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-triangle-exclamation"></i> High Risk
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
                    @if(!empty($progressSnapshot['show_ml_pending_badge']))
                        <span class="badge rounded-pill" style="background:var(--lav-100); color:var(--lav-500); font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-brain"></i> ML Pending
                        </span>
                    @endif
                    @if(!empty($progressSnapshot['has_checkpoint_delay']))
                        <span class="badge rounded-pill" style="background:#fff5ef; color:#d97706; font-size:0.82rem; padding:0.4rem 0.8rem;">
                            <i class="fas fa-clock"></i> Checkpoint Delay
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
                                $sk = $stop->stage_key ?? ($stop->system_key ? str_replace(['checkpoint:','max_duration'], ['','max_duration'], $stop->system_key) : null);
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
@endif

@if(Auth::user()->hasRole('student') || Auth::user()->hasRole('lecturer'))
    <div class="prediction-summary {{ $predictionState }}">
        <div class="prediction-header">
            <div>
                <div class="prediction-kicker">Machine Learning Prediction</div>
                <h3 class="prediction-title">{{ $predictionTitle }}</h3>
                <p class="prediction-copy">{{ $predictionCopy }}</p>
            </div>
            <div class="prediction-metric {{ $predictionState }}">{{ $predictionMetric }}</div>
        </div>

        @if($predictionBadges !== [])
            <div class="prediction-badges">
                @foreach($predictionBadges as $badge)
                    <span class="prediction-badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                @endforeach
                <a href="{{ $predictionHref }}" class="prediction-badge pending text-decoration-none">
                    <i class="fas fa-arrow-right"></i> Open checkpoint tab
                </a>
            </div>
        @endif
    </div>
@endif

@else
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Please log in to access your dashboard.
</div>
@endauth

@endsection