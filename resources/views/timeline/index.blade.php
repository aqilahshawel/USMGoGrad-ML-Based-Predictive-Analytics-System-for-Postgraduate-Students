@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')
@php
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

    $liveForecastCheckpointDates = [
        'proposal_review_date' => optional($checkpoint?->recommended_proposal_review_date)->format('d M Y'),
        'research_review_date' => optional($checkpoint?->recommended_research_review_date)->format('d M Y'),
        'nos_date' => optional($checkpoint?->recommended_nos_date)->format('d M Y'),
        'final_draft_submission_date' => optional($checkpoint?->recommended_final_draft_submission_date)->format('d M Y'),
        'viva_date' => optional($checkpoint?->recommended_viva_date)->format('d M Y'),
    ];

    $baselineCheckpointDates = [
        'proposal_review_date' => optional($checkpoint?->baseline_recommended_proposal_review_date)->format('d M Y')
            ?: optional($checkpoint?->recommended_proposal_review_date)->format('d M Y'),
        'research_review_date' => optional($checkpoint?->baseline_recommended_research_review_date)->format('d M Y')
            ?: optional($checkpoint?->recommended_research_review_date)->format('d M Y'),
        'nos_date' => optional($checkpoint?->baseline_recommended_nos_date)->format('d M Y')
            ?: optional($checkpoint?->recommended_nos_date)->format('d M Y'),
        'final_draft_submission_date' => optional($checkpoint?->baseline_recommended_final_draft_submission_date)->format('d M Y')
            ?: optional($checkpoint?->recommended_final_draft_submission_date)->format('d M Y'),
        'viva_date' => optional($checkpoint?->baseline_recommended_viva_date)->format('d M Y')
            ?: optional($checkpoint?->recommended_viva_date)->format('d M Y'),
    ];

    $actualCheckpointDates = [
        'proposal_review_date' => optional($checkpoint?->proposal_review_date)->format('d M Y'),
        'research_review_date' => optional($checkpoint?->research_review_date)->format('d M Y'),
        'nos_date' => optional($checkpoint?->nos_date)->format('d M Y'),
        'final_draft_submission_date' => optional($checkpoint?->final_draft_submission_date)->format('d M Y'),
        'viva_date' => optional($checkpoint?->viva_date)->format('d M Y'),
    ];

    $predictionInput = (array) ($checkpoint?->prediction_input ?? []);
    $recommendationDiagnostics = (array) ($predictionInput['recommendation_diagnostics'] ?? []);
    $compressedByMaxSemester = (bool) ($recommendationDiagnostics['compressed_by_max_semester'] ?? false);
    $sameSemesterGroups = collect((array) ($recommendationDiagnostics['same_semester_groups'] ?? []));

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

    .checkpoint-note.success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }

    .checkpoint-note.secondary {
        background: #f5f5f5;
        color: #666666;
        border: 1px solid #e0e0e0;
    }

    .checkpoint-note.forecast {
        background: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
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
                </form>
            </div>
        </section>
    @endif

    <div class="timeline-tab-section">
                    @if($targetUser)
                        <div class="checkpoint-grid">
                            <section class="timeline-panel">
                                <div class="timeline-panel-header">
                                    <h3>Checkpoint Updates</h3>
                                </div>
                                <div class="timeline-panel-body">
                                    <div class="checkpoint-meta">
                                        <span class="checkpoint-pill">Cluster: {{ $targetUser->cluster ?? 'Missing' }}</span>
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
                                                        @if($input['field'] !== 'ekc500_admin_approved' && !empty($baselineCheckpointDates[$input['field']]))
                                                            <div class="text-muted small mb-1">Baseline target: {{ $baselineCheckpointDates[$input['field']] }}</div>
                                                        @endif
                                                        @if(
                                                            $input['field'] !== 'ekc500_admin_approved'
                                                            && !empty($liveForecastCheckpointDates[$input['field']])
                                                            && $liveForecastCheckpointDates[$input['field']] !== ($baselineCheckpointDates[$input['field']] ?? null)
                                                        )
                                                            <div class="text-muted small mb-1">Live forecast: {{ $liveForecastCheckpointDates[$input['field']] }}</div>
                                                        @endif
                                                        @if($input['field'] !== 'ekc500_admin_approved' && !empty($actualCheckpointDates[$input['field']]))
                                                            <div class="text-muted small mb-1">Completed on: {{ $actualCheckpointDates[$input['field']] }}</div>
                                                        @endif
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
                                                    @if($input['field'] !== 'ekc500_admin_approved' && !empty($baselineCheckpointDates[$input['field']]))
                                                        <div class="text-muted small mt-1">Baseline target: {{ $baselineCheckpointDates[$input['field']] }}</div>
                                                    @endif
                                                    @if(
                                                        $input['field'] !== 'ekc500_admin_approved'
                                                        && !empty($liveForecastCheckpointDates[$input['field']])
                                                        && $liveForecastCheckpointDates[$input['field']] !== ($baselineCheckpointDates[$input['field']] ?? null)
                                                    )
                                                        <div class="text-muted small mt-1">Live forecast: {{ $liveForecastCheckpointDates[$input['field']] }}</div>
                                                    @endif
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
                                        @php
                                            $panelPredictionState = $predictionState ?? 'unavailable';
                                        @endphp
                                        @if($panelPredictionState === 'ready' && !is_null($checkpoint->got_probability))
                                            @php
                                                $probabilityPercent = number_format($checkpoint->got_probability * 100, 2);
                                                $predictedPerThousand = (int) round($checkpoint->got_probability * 1000);
                                            @endphp
                                            <div class="prediction-hero">
                                                <div class="text-uppercase small fw-semibold text-muted">GoT Probability</div>
                                                <div class="prediction-score risk-{{ $checkpoint->risk_level }}">{{ $probabilityPercent }}%</div>
                                                <div class="fw-semibold text-uppercase risk-{{ $checkpoint->risk_level }}">
                                                    {{ ucfirst($checkpoint->risk_level ?? 'unknown') }} Risk
                                                </div>
                                                <p class="mb-0 mt-2 text-muted">
                                                    Prediction: {{ $checkpoint->predicted_got ? 'Graduate on Time' : 'Low Probability To Graduate on Time' }}
                                                </p>
                                            </div>

                                            <div class="checkpoint-note success mt-3">
                                                <i class="fas fa-check-circle"></i> Last predicted: {{ optional($checkpoint->last_predicted_at)->timezone(config('app.display_timezone'))->format('d M Y H:i') ?? 'Not available' }}<br>
                                                Model version: {{ $checkpoint->model_version ?? 'Unknown' }}
                                            </div>

                                            <div class="checkpoint-note secondary mt-3">
                                                <strong><i class="fas fa-rocket"></i> Baseline Target</strong><br>
                                                Proposal Review: {{ $baselineCheckpointDates['proposal_review_date'] ?? 'Not available' }}<br>
                                                Research Review: {{ $baselineCheckpointDates['research_review_date'] ?? 'Not available' }}<br>
                                                NOS: {{ $baselineCheckpointDates['nos_date'] ?? 'Not available' }}<br>
                                                Final Draft Submission: {{ $baselineCheckpointDates['final_draft_submission_date'] ?? 'Not available' }}<br>
                                                Viva: {{ $baselineCheckpointDates['viva_date'] ?? 'Not available' }}
                                            </div>

                                            <div class="checkpoint-note forecast mt-3">
                                                <strong><i class="fas fa-chart-line"></i> Live Forecast (Updates With Progress)</strong><br>
                                                Proposal Review: {{ $actualCheckpointDates['proposal_review_date'] ? 'Completed on ' . $actualCheckpointDates['proposal_review_date'] : ($liveForecastCheckpointDates['proposal_review_date'] ?? 'Not available') }}<br>
                                                Research Review: {{ $actualCheckpointDates['research_review_date'] ? 'Completed on ' . $actualCheckpointDates['research_review_date'] : ($liveForecastCheckpointDates['research_review_date'] ?? 'Not available') }}<br>
                                                NOS: {{ $actualCheckpointDates['nos_date'] ? 'Completed on ' . $actualCheckpointDates['nos_date'] : ($liveForecastCheckpointDates['nos_date'] ?? 'Not available') }}<br>
                                                Final Draft Submission: {{ $actualCheckpointDates['final_draft_submission_date'] ? 'Completed on ' . $actualCheckpointDates['final_draft_submission_date'] : ($liveForecastCheckpointDates['final_draft_submission_date'] ?? 'Not available') }}<br>
                                                Viva: {{ $actualCheckpointDates['viva_date'] ? 'Completed on ' . $actualCheckpointDates['viva_date'] : ($liveForecastCheckpointDates['viva_date'] ?? 'Not available') }}
                                            </div>

                                        @elseif($panelPredictionState === 'not_started')
                                            <div class="prediction-hero">
                                                <div class="text-uppercase small fw-semibold text-muted">Prediction Status</div>
                                                <div class="prediction-score risk-pending">Initializing</div>
                                                <div class="fw-semibold text-uppercase risk-pending">Preparing Baseline Estimates</div>
                                                <p class="mb-0 mt-2 text-muted">
                                                    The model is preparing baseline checkpoint targets and graduation probability from historical cohort data.
                                                </p>
                                            </div>

                                            <div class="checkpoint-note info mt-3">
                                                <i class="fas fa-hourglass-start"></i> Last predicted: {{ optional($checkpoint->last_predicted_at)->timezone(config('app.display_timezone'))->format('d M Y H:i') ?? 'Not available' }}<br>
                                                Model version: {{ $checkpoint->model_version ?? 'Not started' }}
                                            </div>
                                        @elseif($panelPredictionState === 'refresh_failed')
                                            <div class="prediction-hero">
                                                <div class="text-uppercase small fw-semibold text-muted">Prediction Status</div>
                                                <div class="prediction-score risk-pending">Refresh Failed</div>
                                                <div class="fw-semibold text-uppercase risk-pending">Temporarily Unavailable</div>
                                                <p class="mb-0 mt-2 text-muted">
                                                    {{ $predictionWarning ?? 'The model could not return a probability in this refresh. Please check input completeness and try again.' }}
                                                </p>
                                            </div>

                                            <div class="checkpoint-note warning mt-3">
                                                <i class="fas fa-exclamation-triangle"></i> Last checked: {{ optional($checkpoint->last_predicted_at)->timezone(config('app.display_timezone'))->format('d M Y H:i') ?? 'Not available' }}<br>
                                                Model version: {{ $checkpoint->model_version ?? 'Unknown' }}
                                            </div>
                                        @elseif($panelPredictionState === 'unavailable_prerequisites')
                                            <div class="checkpoint-note warning">
                                                <i class="fas fa-circle-exclamation"></i> Prediction is waiting for required student profile details: {{ implode(', ', $missingPrerequisites) }}.
                                            </div>
                                        @else
                                            <div class="checkpoint-note info">
                                                <i class="fas fa-circle-info"></i> Prediction is not available yet. Save checkpoint updates and refresh this page.
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
