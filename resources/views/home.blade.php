@extends('layouts.app')

@section('title', 'Dashboard - USMGoGrad')

@section('content')

<style>
    .hero-section {
        background: var(--hue-gradient);
        color: var(--text);
        padding: 1.1rem 1.25rem;
        margin-bottom: 1.5rem;
        border: 1px solid var(--lav-200);
        border-radius: var(--radius);
        box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
    }

    .hero-section h1 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.2rem;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    .hero-section .lead {
        color: var(--n-700);
        font-size: 0.8rem;
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
        border-radius: var(--radius);
        padding: 1.2rem 1.3rem;
        box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid var(--border);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(109, 91, 208, 0.10);
    }

    .stat-card .icon {
        font-size: 2rem;
        color: var(--lav-500);
        margin-bottom: 0.35rem;
    }

    .stat-card .number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        font-family: var(--font-display);
    }

    .stat-card .label {
        color: var(--muted);
        font-size: 0.8rem;
        margin-top: 0.35rem;
    }

    .admin-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .admin-panel {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.1rem 1.25rem;
        box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
        border: 1px solid var(--border);
    }

    .admin-panel h5 {
        margin: 0;
        color: var(--text);
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

    .roadmap-tabs {
        margin-bottom: 0;
    }

    .tab-content-section {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.25rem 1.25rem 0.75rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        border: 1px solid var(--lav-200);
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

    .assignments-dropdown {
        border-radius: 14px;
        border: 1px solid var(--lav-200);
        background: var(--surface);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        overflow: hidden;
    }

    .assignments-dropdown summary {
        list-style: none;
        cursor: pointer;
        user-select: none;
        padding: 1.05rem 1.25rem;
        background: linear-gradient(135deg, rgba(243,240,255,0.95) 0%, rgba(255,245,239,0.92) 100%);
    }

    .assignments-dropdown summary:hover {
        background: linear-gradient(135deg, rgba(226,218,255,0.92) 0%, rgba(253,229,210,0.92) 100%);
    }

    .assignments-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .assignments-dropdown summary h3 {
        margin-bottom: 0;
        width: 100%;
    }

    .assignments-dropdown[open] summary {
        border-bottom: 1px solid var(--lav-200);
    }

    .assignments-dropdown-body {
        padding: 1rem 1.25rem 0.25rem;
        background: var(--surface);
    }

    .assignments-caret {
        margin-left: auto;
        font-size: 0.95rem;
        color: var(--lav-400);
        transition: transform 0.2s ease;
    }

    .assignments-dropdown[open] .assignments-caret {
        transform: rotate(180deg);
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

    .timeline-panel {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .timeline-panel-header {
        padding: 1.15rem 1.35rem 0.75rem;
    }

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

    .checkpoint-pill {
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.84rem;
        font-weight: 600;
        background: var(--lav-100);
        color: var(--lav-500);
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

    .timeline-form-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr auto;
        gap: 0.75rem;
        align-items: end;
    }

    .timeline-form input {
        width: 100%;
        border: 1.5px solid var(--lav-200);
        border-radius: 10px;
        padding: 0.8rem 0.95rem;
        background: #fff;
    }

    .checkpoint-note {
        padding: 0.95rem 1rem;
        border-radius: 12px;
        font-size: 0.9rem;
    }

    .checkpoint-note.info {
        background: var(--lav-100);
        color: var(--lav-500);
        border: 1px solid var(--lav-200);
    }

    @media (max-width: 768px) {
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

        .timeline-form-grid {
            grid-template-columns: 1fr;
        }
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
        <details class="assignments-dropdown">
            <summary>
                <h3>
                    <i class="fas fa-list"></i> Current Assignments
                    <i class="fas fa-chevron-down assignments-caret"></i>
                </h3>
            </summary>

            <div class="assignments-dropdown-body">

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
                                        @if($student->start_date)
                                            -
                                            @if($student->start_date)
                                                {{ $student->start_date->format('d M Y') }}
                                            @else
                                                Missing registration date
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
        </details>
    </div>

    <!-- Analytics Dashboard Section -->
    @if($analyticsData)
    <div class="analytics-section" style="margin-top: 2.5rem;">
        <h3 style="font-family: var(--font-display); color: var(--lav-500); margin-bottom: 1.5rem; font-weight: 700; font-size: 1.5rem;">
            <i class="fas fa-chart-line"></i> Historical Analytics (Past 3 Years)
        </h3>

        <div class="analytics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Cluster Success Rates -->
            @if(!empty($analyticsData['cluster_statistics']))
            <div class="analytics-card" style="background: var(--surface); border: 1px solid var(--lav-200); border-radius: 14px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);">
                <h5 style="font-family: var(--font-display); margin-bottom: 1rem; color: var(--n-900); font-weight: 700;">
                    <i class="fas fa-layer-group" style="color: var(--lav-400); margin-right: 0.5rem;"></i>
                    Graduation Success Rates by Cluster
                </h5>
                <canvas id="clusterChart" style="max-height: 300px;"></canvas>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--n-100);">
                    <small style="color: var(--muted); display: block; margin-bottom: 0.75rem;">
                        <strong>Overview:</strong> Students enrolled 3+ years ago and their graduation outcomes by research cluster.
                    </small>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        @foreach($analyticsData['cluster_statistics'] as $cluster)
                            <div style="padding: 0.5rem; background: var(--n-50); border-radius: 8px; border-left: 3px solid var(--lav-400);">
                                <div style="font-weight: 600; color: var(--n-900); font-size: 0.9rem;">{{ $cluster['cluster'] }}</div>
                                <div style="color: var(--muted); font-size: 0.8rem;">{{ $cluster['success_rate'] }}% ({{ $cluster['graduated_students'] }}/{{ $cluster['total_students'] }})</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Time to Graduation Trends -->
            @if(!empty($analyticsData['time_to_graduation_trends']))
            <div class="analytics-card" style="background: var(--surface); border: 1px solid var(--lav-200); border-radius: 14px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);">
                <h5 style="font-family: var(--font-display); margin-bottom: 1rem; color: var(--n-900); font-weight: 700;">
                    <i class="fas fa-hourglass-end" style="color: var(--peach-400); margin-right: 0.5rem;"></i>
                    Time to Graduation Trends
                </h5>
                <canvas id="trendChart" style="max-height: 300px;"></canvas>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--n-100);">
                    <small style="color: var(--muted); display: block; margin-bottom: 0.75rem;">
                        <strong>Insight:</strong> Average months to complete candidature by enrollment year, based on graduated students.
                    </small>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        @foreach($analyticsData['time_to_graduation_trends'] as $trend)
                            <div style="padding: 0.5rem; background: var(--n-50); border-radius: 8px; border-left: 3px solid var(--peach-300);">
                                <div style="font-weight: 600; color: var(--n-900); font-size: 0.9rem;">{{ $trend['cohort_label'] }}</div>
                                <div style="color: var(--muted); font-size: 0.8rem;">{{ $trend['avg_years'] }} years avg ({{ $trend['graduated_count'] }} graduates)</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Milestone Completion Rates (Full Width) -->
        @if(!empty($analyticsData['milestone_completion']))
        <div class="analytics-card" style="background: var(--surface); border: 1px solid var(--lav-200); border-radius: 14px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04); margin-bottom: 1.5rem;">
            <h5 style="font-family: var(--font-display); margin-bottom: 1.5rem; color: var(--n-900); font-weight: 700;">
                <i class="fas fa-tasks" style="color: var(--gold-400); margin-right: 0.5rem;"></i>
                Milestone Completion Rates
            </h5>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                @foreach($analyticsData['milestone_completion'] as $key => $milestone)
                    @if(!empty($milestone['label']))
                    <div style="padding: 1rem; background: linear-gradient(135deg, var(--n-50) 0%, white 100%); border-radius: 12px; border: 1px solid var(--lav-200); text-align: center;">
                        <div style="font-size: 1.75rem; font-weight: 700; color: var(--lav-500); font-family: var(--font-display);">
                            {{ $milestone['percentage_label'] }}
                        </div>
                        <div style="color: var(--muted); font-size: 0.85rem; margin-top: 0.5rem; font-weight: 500;">
                            {{ $milestone['label'] }}
                        </div>
                        <div style="color: var(--n-700); font-size: 0.8rem; margin-top: 0.25rem;">
                            {{ $milestone['completed'] }}/{{ $milestone['total'] }} students
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Risk Distribution Pie Chart -->
        @if(!empty($analyticsData['risk_distribution']))
        <div class="analytics-card" style="background: var(--surface); border: 1px solid var(--lav-200); border-radius: 14px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04); max-width: 500px;">
            <h5 style="font-family: var(--font-display); margin-bottom: 1rem; color: var(--n-900); font-weight: 700;">
                <i class="fas fa-exclamation-circle" style="color: #d97706; margin-right: 0.5rem;"></i>
                Current Risk Distribution (All Students)
            </h5>
            <canvas id="riskChart" style="max-height: 250px;"></canvas>
            <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div style="padding: 0.75rem; background: rgba(46, 125, 50, 0.1); border-left: 3px solid #2e7d32; border-radius: 8px;">
                    <div style="font-weight: 600; color: #2e7d32;">Low Risk</div>
                    <div style="font-size: 0.9rem; color: var(--muted);">{{ $analyticsData['risk_distribution']['low']['count'] }} students ({{ $analyticsData['risk_distribution']['low']['percentage'] }}%)</div>
                </div>
                <div style="padding: 0.75rem; background: rgba(217, 119, 6, 0.1); border-left: 3px solid #d97706; border-radius: 8px;">
                    <div style="font-weight: 600; color: #d97706;">Medium Risk</div>
                    <div style="font-size: 0.9rem; color: var(--muted);">{{ $analyticsData['risk_distribution']['medium']['count'] }} students ({{ $analyticsData['risk_distribution']['medium']['percentage'] }}%)</div>
                </div>
                <div style="padding: 0.75rem; background: rgba(194, 65, 12, 0.1); border-left: 3px solid #c2410c; border-radius: 8px;">
                    <div style="font-weight: 600; color: #c2410c;">High Risk</div>
                    <div style="font-size: 0.9rem; color: var(--muted);">{{ $analyticsData['risk_distribution']['high']['count'] }} students ({{ $analyticsData['risk_distribution']['high']['percentage'] }}%)</div>
                </div>
                <div style="padding: 0.75rem; background: rgba(124, 111, 179, 0.1); border-left: 3px solid var(--lav-400); border-radius: 8px;">
                    <div style="font-weight: 600; color: var(--lav-500);">Unassessed</div>
                    <div style="font-size: 0.9rem; color: var(--muted);">{{ $analyticsData['risk_distribution']['unknown']['count'] }} ({{ $analyticsData['risk_distribution']['unknown']['percentage'] }}%)</div>
                </div>
            </div>
        </div>
        @endif

        <p style="color: var(--muted); font-size: 0.8rem; margin-top: 2rem; text-align: center;">
            Analytics generated at {{ \Carbon\Carbon::parse($analyticsData['generated_at'])->format('d M Y H:i A') }}
        </p>
    </div>
    @endif

@elseif(Auth::user()->hasRole('student') || Auth::user()->hasRole('lecturer'))
    @php
        $predictionState = $dashboardPredictionState ?? 'unavailable';
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

            if ($predictionState === 'unavailable_prerequisites' || $dashboardMissingPrerequisites !== []) {
                $predictionState = 'unavailable';
                $predictionTitle = $displayName . ' GoT prediction is waiting for registration details';
                $predictionCopy = 'Missing required fields: ' . implode(', ', $dashboardMissingPrerequisites) . '.';
                $predictionBadges[] = ['class' => 'unavailable', 'text' => 'Registration details incomplete'];
            } elseif ($predictionState === 'refresh_failed' || $predictionWarning) {
                $predictionState = 'unavailable';
                $predictionTitle = $displayName . ' GoT prediction could not be refreshed';
                $predictionCopy = $predictionWarning ?: 'The model failed to refresh this result. Please try again later.';
                $predictionBadges[] = ['class' => 'unavailable', 'text' => 'Refresh failed'];
            } elseif ($predictionState === 'ready' && $dashboardCheckpoint && !is_null($dashboardCheckpoint->got_probability)) {
                $predictionPercent = number_format($dashboardCheckpoint->got_probability * 100, 2);
                $predictedPerThousand = (int) round($dashboardCheckpoint->got_probability * 1000);
                $predictionState = $dashboardCheckpoint->risk_level ?? 'unavailable';
                $predictionTitle = $displayName . ' GoT prediction';
                $predictionCopy = $dashboardCheckpoint->predicted_got
                    ? 'The model currently expects this student to graduate on time if the current pace is maintained.'
                    : 'The model currently detects a risk of delay based on the recorded checkpoint timing.';
                if ($dashboardCheckpoint->recommended_proposal_review_date) {
                    $predictionCopy .= ' ML checkpoint target dates are generated from historical cohort patterns and shown in the checkpoint panel.';
                }
                $predictionMetric = $predictionPercent . '% GoT probability';
                $predictionBadges[] = ['class' => $predictionState, 'text' => ucfirst($predictionState) . ' risk'];
                $predictionBadges[] = [
                    'class' => $predictionState,
                    'text' => $dashboardCheckpoint->predicted_got ? 'Graduate on time' : 'Potential delay'
                ];
                $predictionBadges[] = ['class' => 'pending', 'text' => 'ML target dates generated'];

                if ($dashboardCheckpoint->last_predicted_at) {
                    $predictionBadges[] = [
                        'class' => 'pending',
                        'text' => 'Updated ' . $dashboardCheckpoint->last_predicted_at->timezone(config('app.display_timezone'))->format('d M Y H:i')
                    ];
                }
            } else {
                $predictionState = 'pending';
                $predictionTitle = $displayName . ' GoT prediction is initializing';
                $predictionCopy = 'The model is preparing baseline checkpoint target dates and graduation probability for this student.';
                $predictionBadges[] = ['class' => 'pending', 'text' => 'Initializing model output'];
            }
        }

        $checkpointStyles = [
            'ekc500_admin_approval' => ['icon' => 'fa-file-circle-check', 'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
            'proposal_review' => ['icon' => 'fa-file-signature', 'color' => '#e07d45', 'bg' => '#fff5ef'],
            'publish_paper' => ['icon' => 'fa-newspaper', 'color' => '#0f766e', 'bg' => '#e6fffa'],
            'research_review' => ['icon' => 'fa-magnifying-glass-chart', 'color' => '#3d9970', 'bg' => '#e8f5e9'],
            'nos'             => ['icon' => 'fa-envelope-open-text', 'color' => '#d97706', 'bg' => '#fff5ef'],
            'final_draft_submission' => ['icon' => 'fa-file-arrow-up', 'color' => '#b7791f', 'bg' => '#fffbf0'],
            'viva'            => ['icon' => 'fa-user-graduate', 'color' => '#c2410c', 'bg' => '#fdecea'],
            'extension'       => ['icon' => 'fa-hourglass-half', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
            'overdue'         => ['icon' => 'fa-exclamation-triangle','color' => '#cc4b4b', 'bg' => '#fdecea'],
            'max_duration'    => ['icon' => 'fa-flag-checkered', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
        ];
        $defaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

        $checkpointFieldMap = [
            'checkpoint:ekc500_admin_approval' => 'ekc500_admin_approved',
            'checkpoint:proposal_review' => 'proposal_review_date',
            'checkpoint:publish_paper' => 'publish_paper',
            'checkpoint:research_review' => 'research_review_date',
            'checkpoint:nos' => 'nos_date',
            'checkpoint:final_draft_submission' => 'final_draft_submission_date',
            'checkpoint:viva' => 'viva_date',
        ];

        $checkpoint = $dashboardCheckpoint;
        $systemStops = collect();
        $customRows = collect();
        if (!Auth::user()->hasRole('lecturer') || request('student_id')) {
            $systemStops = $timelines
                ->filter(fn($t) => $t->system_key
                    && (str_starts_with($t->system_key, 'checkpoint:') || $t->system_key === 'max_duration')
                    && $t->system_key !== 'checkpoint:viva'
                )
                ->sortBy('event_date')
                ->values();

            $customRows = $timelines
                ->filter(fn($t) => ! $t->is_system_generated)
                ->sortBy('event_date')
                ->values();
        }

        $maxDurationRow = $timelines->firstWhere('system_key', 'max_duration');
        $totalStops = $systemStops->count();
        $completedStops = $systemStops->where('completed', true)->count();
        $currentStopId = optional($systemStops->firstWhere('completed', false))->id;
    @endphp

    <section class="tab-content-section mt-3">
        @if(Auth::user()->hasRole('lecturer'))
            <div class="mb-3">
                <h5 style="font-family:var(--font-display); margin-bottom: 0.2rem;"><i class="fas fa-map"></i> Timeline Roadmap</h5>
                <p class="text-muted mb-2" style="font-size: 0.83rem;">Select a student to preview their candidature progress</p>
            </div>
            <div class="student-selector">
                <form method="GET" action="{{ route('home') }}">
                    <div class="input-group">
                        <label class="input-group-text" for="selectedStudent"><i class="fas fa-search"></i></label>
                        <select name="student_id" id="selectedStudent" class="form-select" onchange="this.form.submit()">
                            <option value="">- Choose a student -</option>
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
                <h5 style="font-family:var(--font-display); margin-bottom: 0.1rem;"><i class="fas fa-map"></i> Timeline Roadmap</h5>
                <p class="text-muted mb-0" style="font-size:0.82rem;">Your candidature progress at a glance</p>
            </div>
        @endif

        @if($progressSnapshot)
            <div class="progress-summary">
                <div class="progress-card risk-{{ $progressSnapshot['risk_status'] }}">
                    <div class="pc-value">{{ $progressSnapshot['current_semester'] ?: '-' }}</div>
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
                    <div class="pc-value" style="font-size: 1rem; color: var(--gold-500); padding-top: 0.25rem;">{{ $progressSnapshot['stage_title'] ?? '-' }}</div>
                    <div class="pc-label">Current Stage</div>
                </div>
            </div>
        @endif

        @php
            $graduatedOnTime = (bool) ($progressSnapshot['graduated_on_time'] ?? false);
            $hasCompletedStudies = (bool) ($progressSnapshot['has_completed_studies'] ?? false);
        @endphp

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
                            @elseif(is_bool($actualValue) && $actualValue)
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

            @if($customRows->isNotEmpty())
                <div class="additional-events">
                    <section class="timeline-panel">
                        <div class="timeline-panel-header">
                            <h5>Additional Timeline Events</h5>
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

                                    @if($row->status === 'rejected' && $row->review_note)
                                        <div class="checkpoint-note warning mb-0 mt-3">
                                            <strong>Supervisor reason:</strong> {{ $row->review_note }}
                                        </div>
                                    @endif

                                    @if(auth()->user()->hasRole('student') && (int) $row->proposed_by_user_id === (int) auth()->id() && $row->status === 'approved' && ! $graduatedOnTime)
                                        <div class="checkpoint-note info mb-0 mt-3">
                                            <strong>Completion:</strong> {{ $row->completed ? 'Completed' : 'Not yet completed' }}
                                        </div>

                                        <div class="additional-event-actions">
                                            <form action="{{ route('timeline.completion.update', $row->id) }}" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="completed" value="0">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" id="completed-{{ $row->id }}" name="completed" value="1" {{ $row->completed ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="completed-{{ $row->id }}">Mark as completed</label>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Save</button>
                                            </form>

                                            <form action="{{ route('timeline.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Delete this approved checkpoint?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    @endif

                                    @if(auth()->user()->hasRole('lecturer') && $row->status === 'pending')
                                        <div class="additional-event-actions">
                                            <form action="{{ route('timeline.approve', $row->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check-circle"></i> Approve</button>
                                            </form>
                                            <form action="{{ route('timeline.reject', $row->id) }}" method="POST" class="d-flex flex-column gap-2" style="min-width: 260px;">
                                                @csrf
                                                @method('PUT')
                                                <textarea name="review_note" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Reason for rejection" required></textarea>
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
        @else
            <div class="checkpoint-note info">
                @if(Auth::user()->hasRole('lecturer'))
                    Select a student above to see the roadmap.
                @else
                    No generated checkpoint roadmap is available yet. Complete your registration details first.
                @endif
            </div>
        @endif
    </section>
@endif

@else
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i> Please log in to access your dashboard.
</div>
@endauth

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cluster Statistics Chart
    const clusterCtx = document.getElementById('clusterChart');
    if (clusterCtx) {
        const clusterData = @json($analyticsData['cluster_statistics'] ?? []);
        
        if (clusterData.length > 0) {
            new Chart(clusterCtx, {
                type: 'bar',
                data: {
                    labels: clusterData.map(c => c.cluster),
                    datasets: [{
                        label: 'Success Rate (%)',
                        data: clusterData.map(c => c.success_rate),
                        backgroundColor: [
                            'rgba(124, 111, 179, 0.7)',
                            'rgba(224, 125, 69, 0.7)',
                            'rgba(61, 153, 112, 0.7)',
                            'rgba(217, 119, 6, 0.7)',
                            'rgba(194, 65, 12, 0.7)',
                        ],
                        borderColor: [
                            'var(--lav-500)',
                            'var(--peach-500)',
                            '#3d9970',
                            '#d97706',
                            '#c2410c',
                        ],
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.x.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            min: 0,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Time to Graduation Trends Chart
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        const trendData = @json($analyticsData['time_to_graduation_trends'] ?? []);
        
        if (trendData.length > 0) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.map(t => t.cohort_label),
                    datasets: [{
                        label: 'Average Years to Graduation',
                        data: trendData.map(t => t.avg_years),
                        borderColor: 'var(--peach-500)',
                        backgroundColor: 'rgba(224, 125, 69, 0.1)',
                        borderWidth: 3,
                        pointRadius: 6,
                        pointBackgroundColor: 'var(--peach-500)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: { size: 12 },
                                color: 'var(--muted)'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toFixed(2) + ' years';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(1) + 'y';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Risk Distribution Pie Chart
    const riskCtx = document.getElementById('riskChart');
    if (riskCtx) {
        const riskData = @json($analyticsData['risk_distribution'] ?? null);
        
        if (riskData) {
            new Chart(riskCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Low Risk', 'Medium Risk', 'High Risk', 'Unassessed'],
                    datasets: [{
                        data: [
                            riskData.low.percentage,
                            riskData.medium.percentage,
                            riskData.high.percentage,
                            riskData.unknown.percentage
                        ],
                        backgroundColor: [
                            'rgba(46, 125, 50, 0.7)',
                            'rgba(217, 119, 6, 0.7)',
                            'rgba(194, 65, 12, 0.7)',
                            'rgba(124, 111, 179, 0.4)',
                        ],
                        borderColor: [
                            '#2e7d32',
                            '#d97706',
                            '#c2410c',
                            'var(--lav-500)',
                        ],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: { size: 11 },
                                color: 'var(--muted)',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    return data.labels.map((label, index) => ({
                                        text: label + ' (' + data.datasets[0].data[index].toFixed(1) + '%)',
                                        fillStyle: data.datasets[0].backgroundColor[index],
                                        hidden: false,
                                        index: index
                                    }));
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed.toFixed(1) + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
@endsection