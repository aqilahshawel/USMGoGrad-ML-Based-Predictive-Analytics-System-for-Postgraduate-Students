@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')
@php
    $checkpointStyles = [
        'ekc500_admin_approval' => ['icon' => 'fa-file-circle-check', 'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
        'proposal_review' => ['icon' => 'fa-file-signature', 'color' => '#e07d45', 'bg' => '#fff5ef'],
        'publish_paper' => ['icon' => 'fa-newspaper', 'color' => '#0f766e', 'bg' => '#e6fffa'],
        'research_review' => ['icon' => 'fa-magnifying-glass-chart', 'color' => '#3d9970', 'bg' => '#e8f5e9'],
        'nos' => ['icon' => 'fa-envelope-open-text', 'color' => '#d97706', 'bg' => '#fff5ef'],
        'final_draft_submission' => ['icon' => 'fa-file-arrow-up', 'color' => '#b7791f', 'bg' => '#fffbf0'],
        'max_duration' => ['icon' => 'fa-flag-checkered', 'color' => '#cc4b4b', 'bg' => '#fdecea'],
    ];

    $defaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

    $checkpointFieldMap = [
        'checkpoint:ekc500_admin_approval' => 'ekc500_admin_approved',
        'checkpoint:proposal_review' => 'proposal_review_date',
        'checkpoint:publish_paper' => 'publish_paper',
        'checkpoint:research_review' => 'research_review_date',
        'checkpoint:nos' => 'nos_date',
        'checkpoint:final_draft_submission' => 'final_draft_submission_date',
    ];

    $checkpointInputs = [
        ['field' => 'ekc500_admin_approved', 'title' => 'EKC500 Admin Approval', 'stage' => 1],
        ['field' => 'proposal_review_date', 'title' => 'Proposal Review Date', 'stage' => 1],
        ['field' => 'publish_paper', 'title' => 'Publish Paper', 'stage' => 1],
        ['field' => 'research_review_date', 'title' => 'Research Review Date', 'stage' => 1],
        ['field' => 'nos_date', 'title' => 'Notice of Submission Date', 'stage' => 2],
        ['field' => 'final_draft_submission_date', 'title' => 'Final Draft Submission Date', 'stage' => 2],
    ];

    $stage1Inputs = array_values(array_filter($checkpointInputs, fn($row) => (int) ($row['stage'] ?? 0) === 1));
    $stage2Inputs = array_values(array_filter($checkpointInputs, fn($row) => (int) ($row['stage'] ?? 0) === 2));

    $stage1Complete = (bool) ($checkpoint?->ekc500_admin_approved)
        && ($checkpoint?->proposal_review_date !== null)
        && (bool) ($checkpoint?->publish_paper)
        && ($checkpoint?->research_review_date !== null);
    $stage2Locked = ! $stage1Complete;

    $liveForecastCheckpointDates = [
        'proposal_review_date' => optional($checkpoint?->recommended_proposal_review_date)->format('d M Y'),
        'research_review_date' => optional($checkpoint?->recommended_research_review_date)->format('d M Y'),
        'nos_date' => optional($checkpoint?->recommended_nos_date)->format('d M Y'),
        'final_draft_submission_date' => optional($checkpoint?->recommended_final_draft_submission_date)->format('d M Y'),
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
    ];

    $actualCheckpointDates = [
        'proposal_review_date' => optional($checkpoint?->proposal_review_date)->format('d M Y'),
        'research_review_date' => optional($checkpoint?->research_review_date)->format('d M Y'),
        'nos_date' => optional($checkpoint?->nos_date)->format('d M Y'),
        'final_draft_submission_date' => optional($checkpoint?->final_draft_submission_date)->format('d M Y'),
    ];

    $predictionInput = (array) ($checkpoint?->prediction_input ?? []);
    $recommendationDiagnostics = (array) ($predictionInput['recommendation_diagnostics'] ?? []);
    $compressedByMaxSemester = (bool) ($recommendationDiagnostics['compressed_by_max_semester'] ?? false);
    $sameSemesterGroups = collect((array) ($recommendationDiagnostics['same_semester_groups'] ?? []));

    $customRows = $timelines
        ->filter(fn($timeline) => ! $timeline->is_system_generated)
        ->sortBy('event_date')
        ->values();

    // Stage 1 completion counts for progress display
    $s1CompletedCount = (int)(bool)($checkpoint?->ekc500_admin_approved)
        + (int)($checkpoint?->proposal_review_date !== null)
        + (int)(bool)($checkpoint?->publish_paper)
        + (int)($checkpoint?->research_review_date !== null);

    $s2CompletedCount = (int)($checkpoint?->nos_date !== null)
        + (int)($checkpoint?->final_draft_submission_date !== null);

    $checkpointForecastRows = [
        ['key' => 'proposal_review_date', 'label' => 'Proposal Review', 'stage' => 1],
        ['key' => 'research_review_date', 'label' => 'Research Review', 'stage' => 1],
        ['key' => 'nos_date', 'label' => 'NOS', 'stage' => 2],
        ['key' => 'final_draft_submission_date', 'label' => 'Final Draft', 'stage' => 2],
    ];

    $customRows = $timelines
        ->filter(fn($timeline) => ! $timeline->is_system_generated)
        ->sortBy('event_date')
        ->values();

    $hasCompletedStudies = (bool) ($progressSnapshot['has_completed_studies'] ?? false);
    $graduatedOnTime = (bool) ($progressSnapshot['graduated_on_time'] ?? false);
    $readOnlyTimeline = $hasCompletedStudies;
@endphp

<style>
    /* ── Page shell ─────────────────────────────────────── */
    .timeline-page { padding-bottom: 2rem; font-family: var(--font-base, sans-serif); }

    .timeline-hero {
        background: linear-gradient(135deg, rgba(243,240,255,.95) 0%, rgba(255,245,239,.92) 100%);
        color: var(--n-900);
        padding: 1.6rem 2rem;
        border-radius: 14px;
        margin-bottom: 1.5rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 4px 12px rgba(46,41,36,.03);
    }

    .timeline-hero h2 { margin: 0; color: var(--n-900); font-family: var(--font-display); }

    .timeline-panel {
        background: var(--surface, #ffffff);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        box-shadow: 0 4px 12px rgba(46,41,36,.03);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .timeline-panel-body { padding: 1.5rem; }

    .checkpoint-grid {
        display: grid;
        grid-template-columns: 1.45fr 0.95fr;
        gap: 1.5rem;
    }

    .checkpoint-select {
        width: 100%;
        border: 1px solid var(--lav-200);
        border-radius: 8px;
        padding: .75rem 1rem;
        background: #fdfcff;
        margin-top: 0.5rem;
        transition: all 0.2s;
    }
    .checkpoint-select:focus { outline: none; border-color: #7c6fb3; box-shadow: 0 0 0 3px rgba(124,111,179,.12); }

    /* ── Stage cards ────────────────────────────────────── */
    .stages-stack { display: flex; flex-direction: column; gap: 1.5rem; }

    .stage-card {
        background: var(--surface, #ffffff);
        border: 1px solid var(--lav-200);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(46,41,36,.02);
        transition: opacity .25s, box-shadow .25s;
    }

    .stage-card.stage-locked {
        opacity: .6;
        pointer-events: none;
        box-shadow: none;
        background: #faf9ff;
    }

    .stage-card.stage-1 { border-top: 4px solid #7c6fb3; }
    .stage-card.stage-2 { border-top: 4px solid #3d9970; }
    .stage-card.stage-locked { border-top-color: #d1d1d1; }

    /* ── Stage header ───────────────────────────────────── */
    .stage-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 1.25rem 1.5rem;
        background: #ffffff;
    }

    .stage-number {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stage-1 .stage-number { background: #f3f0ff; color: #7c6fb3; }
    .stage-2:not(.stage-locked) .stage-number { background: #e8f5e9; color: #3d9970; }
    .stage-locked .stage-number { background: #f0f0f0; color: #999; }

    .stage-header-text { flex: 1; }
    .stage-header-text h4 { margin: 0; font-family: var(--font-display); font-size: 1.1rem; color: var(--n-900); font-weight: 600; }
    .stage-header-text p { margin: .25rem 0 0; font-size: .85rem; color: #666; }

    .stage-status-pill {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .35rem .85rem;
        border-radius: 6px;
        font-size: .75rem; font-weight: 600;
        white-space: nowrap;
    }

    .pill-locked   { background: #f0f0f0; color: #777; }
    .pill-progress { background: #f3f0ff; color: #7c6fb3; }
    .pill-done     { background: #e8f5e9; color: #2e7d32; }

    /* ── Progress bar ───────────────────────────────────── */
    .stage-progress-bar { height: 2px; background: var(--lav-100, #f0ebf8); width: 100%; }
    .stage-progress-fill { height: 100%; transition: width .4s ease; }
    .stage-1 .stage-progress-fill { background: #7c6fb3; }
    .stage-2:not(.stage-locked) .stage-progress-fill { background: #3d9970; }
    .stage-locked .stage-progress-fill { background: #ccc; }

    /* ── Stage body ─────────────────────────────────────── */
    .stage-body { padding: 0 1.5rem 1.5rem; }

    .lock-notice {
        display: flex; align-items: center; gap: 12px;
        padding: 1rem 1.25rem;
        background: #faf9ff;
        border-radius: 8px;
        font-size: .9rem; color: #666;
        margin-top: 1.5rem;
    }
    .lock-notice i { color: #a99dd6; font-size: 1.1rem; }

    /* Clean Horizontal Form Items */
    .checkpoint-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 0;
        border-bottom: 1px solid var(--lav-100, #f0ebf8);
        gap: 2rem;
    }
    .checkpoint-item:last-child { border-bottom: none; padding-bottom: 0; }

    .checkpoint-info {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        flex: 1;
    }

    .checkpoint-info label {
        font-weight: 600; font-size: .95rem;
        color: var(--n-900);
        margin: 0;
        cursor: pointer;
    }

    .meta-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .meta-tag {
        font-size: .75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .meta-tag.baseline { background: #f0f0f0; color: #555; }
    .meta-tag.forecast { background: #e3f2fd; color: #1565c0; }
    .meta-tag.actual   { background: #e8f5e9; color: #2e7d32; }
    
    .meta-dot { width: 6px; height: 6px; border-radius: 50%; }
    .meta-dot.baseline { background: #aaa; }
    .meta-dot.forecast { background: #378add; }
    .meta-dot.actual   { background: #3d9970; }

    .checkpoint-action {
        width: 220px;
        flex-shrink: 0;
    }

    .checkpoint-action input[type="date"],
    .checkpoint-action input[type="text"] {
        width: 100%;
        border: 1px solid var(--lav-200, #d1c8eb);
        border-radius: 8px;
        padding: .65rem .85rem;
        background: #fdfcff;
        font-size: .9rem;
        transition: all .2s;
        outline: none;
        color: var(--n-900);
    }

    .checkpoint-action input[type="date"]:focus,
    .checkpoint-action input[type="text"]:focus {
        border-color: #7c6fb3;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(124,111,179,.12);
    }

    .checkpoint-action input.input-filled { border-color: #3d9970; background: #fafdfa; }

    .toggle-wrapper { display: flex; align-items: center; gap: 10px; }
    .toggle-wrapper input[type="checkbox"] { accent-color: #7c6fb3; width: 20px; height: 20px; cursor: pointer; }

    /* stage footer */
    .stage-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding-top: 1.25rem;
        margin-top: 1rem;
        border-top: 1px solid var(--lav-100, #f0ebf8);
    }

    .stage-footer-meta { font-size: .85rem; color: #666; }
    .stage-footer-meta strong { color: #7c6fb3; font-weight: 700; background: #f3f0ff; padding: 0.2rem 0.6rem; border-radius: 4px; }

    .btn-save {
        background: #7c6fb3; color: white;
        border: none; padding: 0.5rem 1.2rem;
        border-radius: 6px; font-weight: 500;
        cursor: pointer; transition: background 0.2s;
    }
    .btn-save:hover { background: #665b96; }
    .btn-save:disabled { background: #ccc; cursor: not-allowed; }

    /* ── Notes ──────────────────────────────────────────── */
    .checkpoint-note { padding: 1rem; border-radius: 8px; font-size: .9rem; line-height: 1.5; margin-bottom: 1rem; }
    .checkpoint-note.warning  { background: #fff5ef; color: #b75e29; border: 1px solid #f8c9a8; }
    .checkpoint-note.info     { background: var(--lav-100, #f0ebf8); color: #7c6fb3; border: 1px solid var(--lav-200); }

    /* ── Prediction Panel ────────────────────────────────── */
    .prediction-panel { display: flex; flex-direction: column; gap: 1.5rem; }

    /* Option A: keep prediction visible and reduce dead space */
    .prediction-aside { align-self: start; }
    @media (min-width: 901px) {
        .prediction-aside {
            position: sticky;
            top: 1rem;
        }
    }

    .prediction-hero {
        border-radius: 14px;
        padding: 1.5rem;
        background: linear-gradient(135deg, #fdfbf3 0%, #fff5ef 100%);
        border: 1px solid #f8c9a8;
    }

    .forecast-kv {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
        margin-top: 1rem;
        text-align: left;
    }
    .forecast-kv-item {
        background: rgba(255,255,255,.7);
        border: 1px solid rgba(248,201,168,.8);
        border-radius: 10px;
        padding: .65rem .75rem;
    }
    .forecast-kv-label { font-size: .75rem; color: #666; font-weight: 600; }
    .forecast-kv-value { font-size: .9rem; color: var(--n-900); font-weight: 700; margin-top: .15rem; }

    .forecast-table-wrap {
        margin-top: 1rem;
        text-align: left;
        background: rgba(255,255,255,.85);
        border: 1px solid rgba(248,201,168,.8);
        border-radius: 12px;
        overflow: hidden;
    }
    .forecast-table-title {
        padding: .75rem 1rem;
        font-weight: 700;
        font-size: .9rem;
        border-bottom: 1px solid rgba(248,201,168,.75);
        color: var(--n-900);
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .forecast-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .82rem;
    }
    .forecast-table th,
    .forecast-table td {
        padding: .55rem .75rem;
        border-bottom: 1px solid rgba(248,201,168,.55);
        vertical-align: top;
    }
    .forecast-table th { color: #666; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; }
    .forecast-table tr:last-child td { border-bottom: none; }

    .checkpoint-compact-meta { font-size: .8rem; color: #666; margin-top: .25rem; }
    .checkpoint-compact-meta strong { color: var(--n-900); }
    .forecast-muted { color: #777; font-weight: 700; }

    .prediction-score {
        font-family: var(--font-display);
        font-size: 2.5rem;
        line-height: 1;
        font-weight: 700;
        margin: .5rem 0;
        text-transform: capitalize;
    }

    .risk-low, .risk-on_track { color: #2e7d32; }
    .risk-medium, .risk-at_risk { color: #d97706; }
    .risk-high, .risk-overdue, .risk-high_risk { color: #c2410c; }
    .risk-pending { color: #7c6fb3; }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 900px) {
        .checkpoint-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .checkpoint-item { flex-direction: column; align-items: flex-start; gap: 0.8rem; }
        .checkpoint-action { width: 100%; }
        .stage-header { flex-wrap: wrap; }
        .forecast-kv { grid-template-columns: 1fr; }
    }
</style>

<div class="timeline-page">

    {{-- Hero --}}
    <section class="timeline-hero">
        <h2><i class="fas fa-route"></i> Timeline Tracking</h2>
    </section>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    @if(session('prediction_warning'))
        <div class="alert alert-warning mb-3">{{ session('prediction_warning') }}</div>
    @endif

    {{-- Lecturer student selector --}}
    @if(auth()->user()->hasRole('lecturer') && $students->isNotEmpty())
        <section class="timeline-panel">
            <div class="timeline-panel-body">
                <form method="GET" action="{{ route('timeline.index') }}">
                    <label for="student_id" class="fw-semibold" style="color: var(--n-900);">Select Student</label>
                    <select id="student_id" name="student_id" class="checkpoint-select" onchange="this.form.submit()">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                {{ (int) $selectedStudentId === (int) $student->id ? 'selected' : '' }}>
                                {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </section>
    @endif

    {{-- Main content --}}
    @if($targetUser)
        <div class="row g-4">

            {{-- ── Prerequisites warning ───────────────────────────── --}}
            @if($missingPrerequisites !== [])
                <section class="col-12">
                    <div class="checkpoint-note warning">
                        <i class="fas fa-circle-exclamation me-1"></i>
                        Prediction cannot run yet. Missing: {{ implode(', ', $missingPrerequisites) }}.
                    </div>
                </section>
            @endif

            @if(auth()->user()->hasRole('student'))
                {{-- ── Editable stages (Stage 1 left, Stage 2 right) ─── --}}
                <section class="col-12">
                    @if($readOnlyTimeline)
                        <div class="checkpoint-note info">
                            <i class="fas fa-award me-1"></i>
                            You have completed all checkpoints. Timeline tracking is now read-only.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('timeline.checkpoints.update') }}" id="checkpoint-update-form">
                        @csrf
                        @method('PUT')

                        <div class="row g-4">

                            {{-- Stage 1 --}}
                            <div class="col-12 col-lg-6">
                            <div class="stage-card stage-1" id="stage-1-card">
                                <div class="stage-header">
                                    <div class="stage-number" id="s1-number">1</div>
                                    <div class="stage-header-text">
                                        <h4>Proposal &amp; Research Review</h4>
                                        <p id="s1-subtitle">Complete all four fields to unlock Stage 2</p>
                                    </div>
                                    <span class="stage-status-pill pill-progress" id="s1-pill">
                                        <i class="fas fa-spinner fa-spin"></i> In progress
                                    </span>
                                </div>
                                <div class="stage-progress-bar">
                                    <div class="stage-progress-fill" id="s1-bar"
                                     style="width: {{ round(($s1CompletedCount / 4) * 100) }}%"></div>
                                </div>
                                <div class="stage-body">
                                    
                                    {{-- EKC500 Approval --}}
                                    <div class="checkpoint-item mt-3">
                                        <div class="checkpoint-info">
                                            <label for="ekc500_admin_approved">EKC500 Admin Approval</label>
                                            <span style="font-size: 0.8rem; color: #666;">Verify that the administration has approved your EKC500 status.</span>
                                        </div>
                                        <div class="checkpoint-action toggle-wrapper">
                                            <input type="hidden" name="ekc500_admin_approved" value="0">
                                            <input 
                                                type="checkbox" 
                                                id="ekc500_admin_approved" 
                                                name="ekc500_admin_approved" 
                                                value="1" 
                                                {{ old('ekc500_admin_approved', $checkpoint?->ekc500_admin_approved ?? false) ? 'checked' : '' }}
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}
                                            >
                                            <span style="font-size: 0.9rem; font-weight: 500;">Approved</span>
                                        </div>
                                    </div>

                                    {{-- Proposal Review Date --}}
                                    <div class="checkpoint-item">
                                        <div class="checkpoint-info">
                                            <label for="proposal_review_date">Proposal Review Date</label>
                                            <div class="checkpoint-compact-meta">
                                                <span class="forecast-muted">Stage 1</span>
                                                &middot;
                                                Baseline <strong>{{ $baselineCheckpointDates['proposal_review_date'] ?: '—' }}</strong>
                                                &middot;
                                                Forecast <strong>{{ $liveForecastCheckpointDates['proposal_review_date'] ?: '—' }}</strong>
                                                @if(!empty($actualCheckpointDates['proposal_review_date']))
                                                    &middot; Actual <strong>{{ $actualCheckpointDates['proposal_review_date'] }}</strong>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="checkpoint-action">
                                            <input
                                                type="date"
                                                id="proposal_review_date"
                                                name="proposal_review_date"
                                                value="{{ old('proposal_review_date', optional($checkpoint?->proposal_review_date)->format('Y-m-d')) }}"
                                                class="{{ !empty($actualCheckpointDates['proposal_review_date']) ? 'input-filled' : '' }}"
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}
                                            >
                                            @error('proposal_review_date')
                                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Publish Paper --}}
                                    <div class="checkpoint-item">
                                        <div class="checkpoint-info">
                                            <label for="publish_paper">Publish Paper</label>
                                            <span style="font-size: 0.8rem; color: #666;">Confirm your paper has been published.</span>
                                        </div>
                                        <div class="checkpoint-action toggle-wrapper">
                                            <input type="hidden" name="publish_paper" value="0">
                                            <input
                                                type="checkbox"
                                                id="publish_paper"
                                                name="publish_paper"
                                                value="1"
                                                {{ old('publish_paper', $checkpoint?->publish_paper ?? false) ? 'checked' : '' }}
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}
                                            >
                                            <span style="font-size: 0.9rem; font-weight: 500;">Published</span>
                                        </div>
                                    </div>

                                    {{-- Research Review Date --}}
                                    <div class="checkpoint-item">
                                        <div class="checkpoint-info">
                                            <label for="research_review_date">Research Review Date</label>
                                            <div class="checkpoint-compact-meta">
                                                <span class="forecast-muted">Stage 1</span>
                                                &middot;
                                                Baseline <strong>{{ $baselineCheckpointDates['research_review_date'] ?: '—' }}</strong>
                                                &middot;
                                                Forecast <strong>{{ $liveForecastCheckpointDates['research_review_date'] ?: '—' }}</strong>
                                                @if(!empty($actualCheckpointDates['research_review_date']))
                                                    &middot; Actual <strong>{{ $actualCheckpointDates['research_review_date'] }}</strong>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="checkpoint-action">
                                            <input
                                                type="date"
                                                id="research_review_date"
                                                name="research_review_date"
                                                value="{{ old('research_review_date', optional($checkpoint?->research_review_date)->format('Y-m-d')) }}"
                                                class="{{ !empty($actualCheckpointDates['research_review_date']) ? 'input-filled' : '' }}"
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}
                                            >
                                            @error('research_review_date')
                                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="stage-footer">
                                        <div class="stage-footer-meta">
                                            Fields completed: <strong id="s1-count">{{ $s1CompletedCount }} / 4</strong>
                                        </div>
                                        <button type="submit" class="btn-save" {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            <i class="fas fa-save me-1"></i> Save Progress
                                        </button>
                                    </div>
                                </div>
                            </div>
                            </div>

                            {{-- Stage 2 --}}
                            <div class="col-12 col-lg-6">
                            <div class="stage-card stage-2 {{ $stage2Locked ? 'stage-locked' : '' }}" id="stage-2-card">
                                <div class="stage-header">
                                    <div class="stage-number" id="s2-number">2</div>
                                    <div class="stage-header-text">
                                        <h4>Final Submission</h4>
                                        <p id="s2-subtitle">
                                            {{ $stage2Locked ? 'Locked until Stage 1 is completed' : 'Enter your submission checkpoint dates' }}
                                        </p>
                                    </div>
                                    <span class="stage-status-pill {{ $stage2Locked ? 'pill-locked' : 'pill-progress' }}" id="s2-pill">
                                        @if($stage2Locked)
                                            <i class="fas fa-lock"></i> Locked
                                        @else
                                            <i class="fas fa-lock-open"></i> Unlocked
                                        @endif
                                    </span>
                                </div>
                                <div class="stage-progress-bar">
                                    <div class="stage-progress-fill" id="s2-bar"
                                         style="width: {{ $stage2Locked ? 0 : round(($s2CompletedCount / 2) * 100) }}%"></div>
                                </div>
                                <div class="stage-body">

                                    {{-- Lock overlay --}}
                                    <div class="lock-notice {{ $stage1Complete ? 'd-none' : '' }}" id="s2-lock-notice">
                                        <i class="fas fa-lock"></i>
                                        <span>Complete Stage 1 (EKC500 approval, proposal review, publish paper, and research review) to unlock submission fields.</span>
                                    </div>

                                    {{-- Fields --}}
                                    <div id="s2-fields" class="mt-3 {{ $stage2Locked ? 'd-none' : '' }}">
                                        
                                        {{-- NOS --}}
                                        <div class="checkpoint-item">
                                            <div class="checkpoint-info">
                                                <label for="nos_date">Notice of Submission (NOS) Date</label>
                                                <div class="checkpoint-compact-meta">
                                                    <span class="forecast-muted">Stage 2{{ $stage2Locked ? ' (locked)' : '' }}</span>
                                                    &middot;
                                                    Baseline <strong>{{ $baselineCheckpointDates['nos_date'] ?: '—' }}</strong>
                                                    &middot;
                                                    Forecast <strong>{{ $liveForecastCheckpointDates['nos_date'] ?: '—' }}</strong>
                                                    @if(!empty($actualCheckpointDates['nos_date']))
                                                        &middot; Actual <strong>{{ $actualCheckpointDates['nos_date'] }}</strong>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="checkpoint-action">
                                                <input
                                                    type="date"
                                                    id="nos_date"
                                                    name="nos_date"
                                                    value="{{ old('nos_date', optional($checkpoint?->nos_date)->format('Y-m-d')) }}"
                                                    class="{{ !empty($actualCheckpointDates['nos_date']) ? 'input-filled' : '' }}"
                                                    {{ $stage2Locked || $readOnlyTimeline ? 'disabled' : '' }}
                                                >
                                                @error('nos_date')
                                                    <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- Final Draft Submission --}}
                                        <div class="checkpoint-item">
                                            <div class="checkpoint-info">
                                                <label for="final_draft_submission_date">Final Draft Submission Date</label>
                                                <div class="checkpoint-compact-meta">
                                                    <span class="forecast-muted">Stage 2{{ $stage2Locked ? ' (locked)' : '' }}</span>
                                                    &middot;
                                                    Baseline <strong>{{ $baselineCheckpointDates['final_draft_submission_date'] ?: '—' }}</strong>
                                                    &middot;
                                                    Forecast <strong>{{ $liveForecastCheckpointDates['final_draft_submission_date'] ?: '—' }}</strong>
                                                    @if(!empty($actualCheckpointDates['final_draft_submission_date']))
                                                        &middot; Actual <strong>{{ $actualCheckpointDates['final_draft_submission_date'] }}</strong>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="checkpoint-action">
                                                <input
                                                    type="date"
                                                    id="final_draft_submission_date"
                                                    name="final_draft_submission_date"
                                                    value="{{ old('final_draft_submission_date', optional($checkpoint?->final_draft_submission_date)->format('Y-m-d')) }}"
                                                    class="{{ !empty($actualCheckpointDates['final_draft_submission_date']) ? 'input-filled' : '' }}"
                                                    {{ $stage2Locked || $readOnlyTimeline ? 'disabled' : '' }}
                                                >
                                                @error('final_draft_submission_date')
                                                    <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="stage-footer">
                                            <div class="stage-footer-meta">
                                                Fields completed: <strong id="s2-count">{{ $s2CompletedCount }} / 2</strong>
                                            </div>
                                            <button type="submit" class="btn-save" {{ $stage2Locked || $readOnlyTimeline ? 'disabled' : '' }}>
                                                <i class="fas fa-save me-1"></i> Save Progress
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </form>
                </section>
            @endif

            {{-- ── Forecast card (below stages) ─────────────────────── --}}
            <section class="col-12 prediction-panel">
                
                {{-- Prediction Status Hero --}}
                <div class="prediction-hero">
                    <div class="d-flex align-items-start justify-content-between" style="gap: .75rem;">
                        <div>
                            <h4 style="margin:0; color: var(--n-900); font-size: 1.05rem; font-weight: 600;">
                                Forecast Summary
                            </h4>
                        </div>
                    </div>
                    @php
                        $forecastHeadline = match ($predictionState ?? null) {
                            'ready' => (bool) ($checkpoint?->predicted_got) ? 'On track' : 'Potential delay',
                            'unavailable_prerequisites' => 'Unavailable',
                            'refresh_failed' => 'Needs refresh',
                            default => 'Pending',
                        };

                        if ($hasCompletedStudies) {
                            $forecastHeadline = $graduatedOnTime ? 'Complete Studies';
                        }

                        $forecastRiskClass = $hasCompletedStudies
                            ? 'risk-on_track'
                            : 'risk-' . strtolower((string) ($checkpoint?->risk_level ?? 'pending'));
                    @endphp
                    <div class="prediction-score {{ $forecastRiskClass }}">
                        {{ $forecastHeadline }}
                    </div>

                    <p style="margin: .35rem 0 0; font-size: .85rem; color: #666;">
                        @if($hasCompletedStudies)
                            All required checkpoints are completed.
                        @elseif(($predictionState ?? null) === 'ready')
                            Forecast generated from your current checkpoint and profile data.
                        @elseif(($predictionState ?? null) === 'unavailable_prerequisites')
                            Missing profile details required to run the model.
                        @elseif(($predictionState ?? null) === 'refresh_failed')
                            Latest ML output could not be refreshed right now.
                        @else
                            ML forecast is initializing.
                        @endif
                    </p>

                    @unless($hasCompletedStudies)
                        <div class="forecast-kv">
                            <div class="forecast-kv-item">
                                <div class="forecast-kv-label">Risk level</div>
                                <div class="forecast-kv-value">{{ $checkpoint?->risk_level ? ucfirst(str_replace('_', ' ', $checkpoint->risk_level)) : '—' }}</div>
                            </div>
                            <div class="forecast-kv-item">
                                <div class="forecast-kv-label">Last updated</div>
                                <div class="forecast-kv-value">
                                    {{ $checkpoint?->last_predicted_at
                                        ? $checkpoint->last_predicted_at->timezone(config('app.display_timezone'))->format('d M Y H:i')
                                        : '—' }}
                                </div>
                            </div>
                        </div>
                    @endunless

                    @if(!$hasCompletedStudies && ($predictionState ?? null) === 'unavailable_prerequisites' && !empty($missingPrerequisites))
                        <div class="forecast-table-wrap" style="margin-top: .9rem;">
                            <div class="forecast-table-title"><i class="fas fa-circle-exclamation"></i> Missing prerequisites</div>
                            <div style="padding: .75rem 1rem; font-size: .85rem; color: #666;">
                                {{ implode(', ', $missingPrerequisites) }}
                            </div>
                        </div>
                    @elseif(!$hasCompletedStudies && ($predictionState ?? null) === 'refresh_failed' && !empty($predictionWarning))
                        <div class="forecast-table-wrap" style="margin-top: .9rem;">
                            <div class="forecast-table-title"><i class="fas fa-triangle-exclamation"></i> Forecast warning</div>
                            <div style="padding: .75rem 1rem; font-size: .85rem; color: #666;">
                                {{ $predictionWarning }}
                            </div>
                        </div>
                    @endif

                    <div class="forecast-table-wrap">
                        <div class="forecast-table-title"><i class="fas fa-calendar-check"></i> Checkpoint targets</div>
                        <table class="forecast-table">
                            <thead>
                                <tr>
                                    <th style="width: 34%;">Checkpoint</th>
                                    <th style="width: 22%;">Baseline</th>
                                    <th style="width: 22%;">Forecast</th>
                                    <th style="width: 22%;">Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($checkpointForecastRows as $row)
                                    @php
                                        $k = $row['key'];
                                        $baseline = $baselineCheckpointDates[$k] ?? null;
                                        $forecast = $liveForecastCheckpointDates[$k] ?? null;
                                        $actual = $actualCheckpointDates[$k] ?? null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: var(--n-900);">{{ $row['label'] }}</div>
                                            <div style="font-size: .75rem; color: #666;">Stage {{ (int) $row['stage'] }}</div>
                                        </td>
                                        <td>{{ $baseline ?: '—' }}</td>
                                        <td>{{ $forecast ?: '—' }}</td>
                                        <td>{{ $actual ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            @if($customRows->isNotEmpty())
                <section class="col-12">
                    <section class="timeline-panel">
                        <div class="timeline-panel-header">
                            <h5>Additional Timeline Events</h5>
                            <p>Student-created checkpoint requests and their approval status.</p>
                        </div>
                        <div class="timeline-panel-body">
                            @foreach($customRows as $row)
                                <div class="checkpoint-item">
                                    <div class="checkpoint-info">
                                        <label>{{ $row->title }}</label>
                                        <div class="checkpoint-compact-meta">
                                            <strong>{{ optional($row->event_date)->format('d M Y') }}</strong>
                                            &middot; {{ ucfirst($row->status ?? 'approved') }}
                                            @if($row->status === 'approved')
                                                &middot; {{ $row->completed ? 'Completed' : 'Not yet completed' }}
                                            @endif
                                        </div>

                                        @if($row->status === 'rejected' && $row->review_note)
                                            <div class="checkpoint-note warning mb-0 mt-2">
                                                <strong>Supervisor reason:</strong> {{ $row->review_note }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="checkpoint-action" style="width: auto; min-width: 240px;">
                                        @if(auth()->user()->hasRole('lecturer') && $row->status === 'pending')
                                            <div class="d-flex flex-column gap-2">
                                                <form action="{{ route('timeline.approve', $row->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-check-circle"></i> Approve</button>
                                                </form>
                                                <form action="{{ route('timeline.reject', $row->id) }}" method="POST" class="d-flex flex-column gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <textarea name="review_note" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Reason for rejection" required></textarea>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="fas fa-ban"></i> Reject</button>
                                                </form>
                                            </div>
                                        @elseif(auth()->user()->hasRole('student') && (int) $row->proposed_by_user_id === (int) auth()->id() && $row->status === 'approved')
                                            @if($readOnlyTimeline)
                                                <span class="status-pill pill-done">Read-only</span>
                                            @else
                                            <div class="d-flex flex-column gap-2">
                                                <form action="{{ route('timeline.completion.update', $row->id) }}" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="completed" value="0">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="timeline-completed-{{ $row->id }}" name="completed" value="1" {{ $row->completed ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="timeline-completed-{{ $row->id }}">Mark as completed</label>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Save</button>
                                                </form>
                                                <form action="{{ route('timeline.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Delete this approved checkpoint?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="fas fa-trash"></i> Delete</button>
                                                </form>
                                            </div>
                                            @endif
                                        @else
                                            <span class="status-pill {{ $row->status === 'approved' ? 'pill-done' : ($row->status === 'rejected' ? 'pill-locked' : 'pill-progress') }}">
                                                {{ $row->status === 'approved' ? 'Approved' : ($row->status === 'rejected' ? 'Rejected' : 'Pending') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </section>
            @endif
        </div>
    @endif
</div>
@endsection