@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')
@php
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

    $hasCompletedStudies = (bool) ($progressSnapshot['has_completed_studies'] ?? false);
    $graduatedOnTime = (bool) ($progressSnapshot['graduated_on_time'] ?? false);
    $readOnlyTimeline = $hasCompletedStudies;

    $activeTab = (string) request()->get('tab', 'checkpoint');
    $tabParams = [];
    if (!empty($selectedStudentId)) {
        $tabParams['student_id'] = $selectedStudentId;
    }

    $forecastHeadline = match ($predictionState ?? null) {
        'ready' => (bool) ($checkpoint?->predicted_got) ? 'On Track' : 'Potential Delay',
        'unavailable_prerequisites' => 'Unavailable',
        'refresh_failed' => 'Needs Refresh',
        default => 'Pending',
    };
    if ($hasCompletedStudies) {
        $forecastHeadline = $graduatedOnTime ? 'Graduated On Time' : 'Studies Complete';
    }

    $riskLevel = $checkpoint?->risk_level ?? 'pending';
    $riskColors = [
        'on_track'  => ['dot' => '#16a34a', 'bg' => '#f0fdf4', 'text' => '#15803d'],
        'at_risk'   => ['dot' => '#d97706', 'bg' => '#fffbeb', 'text' => '#b45309'],
        'high_risk' => ['dot' => '#dc2626', 'bg' => '#fef2f2', 'text' => '#b91c1c'],
        'overdue'   => ['dot' => '#dc2626', 'bg' => '#fef2f2', 'text' => '#b91c1c'],
        'pending'   => ['dot' => '#6366f1', 'bg' => '#eef2ff', 'text' => '#4f46e5'],
    ];
    $risk = $riskColors[$riskLevel] ?? $riskColors['pending'];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ─── Reset & Base ─────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

.tl-page {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: #1a1a1a;
    padding-bottom: 3rem;
    --c-purple: #6d5bd0;
    --c-purple-light: #ede9fc;
    --c-green: #16a34a;
    --c-green-light: #f0fdf4;
    --c-border: #e4e4e7;
    --c-muted: #71717a;
    --c-surface: #ffffff;
    --c-bg: #fafafa;
    --radius: 10px;
    --radius-sm: 6px;
}

/* ─── Page Header ──────────────────────────────────── */
.tl-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--c-border);
}

.tl-header-left { display: flex; align-items: center; gap: 10px; }
.tl-header-icon {
    width: 36px; height: 36px;
    background: var(--c-purple-light);
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    color: var(--c-purple);
    font-size: 16px;
}
.tl-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #111;
}

/* ─── Status Banner ────────────────────────────────── */
.tl-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 1.25rem;
}
.tl-banner.info { background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; }
.tl-banner.success { background: var(--c-green-light); color: var(--c-green); border: 1px solid #bbf7d0; }
.tl-banner.warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

/* ─── Tabs ─────────────────────────────────────────── */
.tl-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 1.25rem;
    background: var(--c-bg);
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 4px;
    width: fit-content;
}
.tl-tab {
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    color: var(--c-muted);
    padding: 6px 16px;
    border-radius: 4px;
    transition: all .15s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.tl-tab:hover { color: #111; }
.tl-tab.active {
    background: #fff;
    color: #111;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}

/* ─── Student Selector ─────────────────────────────── */
.tl-selector {
    margin-bottom: 1.25rem;
}
.tl-selector label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--c-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
}
.tl-selector select {
    width: 100%;
    max-width: 380px;
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 8px 12px;
    font-size: 14px;
    font-family: inherit;
    background: #fff;
    color: #111;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
}
.tl-selector select:focus { border-color: var(--c-purple); box-shadow: 0 0 0 3px rgba(109,91,208,.12); }

/* ─── Stage Grid ───────────────────────────────────── */
.tl-stages-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}
@media (max-width: 768px) { .tl-stages-grid { grid-template-columns: 1fr; } }

/* ─── Stage Card ───────────────────────────────────── */
.tl-stage {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: opacity .2s;
}
.tl-stage.locked { opacity: .55; pointer-events: none; }

.tl-stage-bar { height: 3px; background: var(--c-border); }
.tl-stage-bar-fill { height: 100%; transition: width .4s ease; }
.stage-1 .tl-stage-bar-fill { background: var(--c-purple); }
.stage-2:not(.locked) .tl-stage-bar-fill { background: var(--c-green); }

.tl-stage-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
}
.tl-stage-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    font-size: 13px;
    font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.stage-1 .tl-stage-num { background: var(--c-purple-light); color: var(--c-purple); }
.stage-2:not(.locked) .tl-stage-num { background: var(--c-green-light); color: var(--c-green); }
.locked .tl-stage-num { background: #f4f4f5; color: #a1a1aa; }

.tl-stage-title { flex: 1; }
.tl-stage-title strong { display: block; font-size: 13px; font-weight: 600; color: #111; line-height: 1.3; }
.tl-stage-title span { font-size: 11px; color: var(--c-muted); }

.tl-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
}
.badge-done { background: var(--c-green-light); color: var(--c-green); }
.badge-progress { background: var(--c-purple-light); color: var(--c-purple); }
.badge-locked { background: #f4f4f5; color: #a1a1aa; }
.badge-warning { background: #fffbeb; color: #b45309; }

/* ─── Checkpoint Items ─────────────────────────────── */
.tl-stage-body { padding: 0 16px 16px; }

.tl-cp-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f4f4f5;
}
.tl-cp-item:last-of-type { border-bottom: none; }

.tl-cp-info { flex: 1; min-width: 0; }
.tl-cp-info strong {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #111;
    margin-bottom: 2px;
}
.tl-cp-info p {
    margin: 0;
    font-size: 11px;
    color: var(--c-muted);
    line-height: 1.5;
}

.tl-cp-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}
.tl-cp-meta-tag {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 7px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}
.meta-baseline { background: #f4f4f5; color: #52525b; }
.meta-forecast { background: #eff6ff; color: #2563eb; }
.meta-actual   { background: var(--c-green-light); color: var(--c-green); }

/* ─── Form Controls ────────────────────────────────── */
.tl-cp-action { flex-shrink: 0; }

.tl-input {
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 6px 10px;
    font-size: 13px;
    font-family: 'DM Mono', monospace;
    background: #fff;
    color: #111;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    width: 148px;
}
.tl-input:focus { border-color: var(--c-purple); box-shadow: 0 0 0 3px rgba(109,91,208,.1); }
.tl-input.filled { border-color: #86efac; background: #f0fdf4; }

.tl-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.tl-toggle input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--c-purple);
    cursor: pointer;
    margin: 0;
}
.tl-toggle span { font-size: 13px; font-weight: 500; color: #111; }

/* ─── Stage Footer ─────────────────────────────────── */
.tl-stage-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px 14px;
    border-top: 1px solid #f4f4f5;
    margin-top: 4px;
}
.tl-foot-meta { font-size: 12px; color: var(--c-muted); }
.tl-foot-meta strong { color: #111; font-weight: 600; }

.tl-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    border: none;
    transition: all .15s;
}
.tl-btn-primary { background: var(--c-purple); color: #fff; }
.tl-btn-primary:hover { background: #5a4ab8; }
.tl-btn-primary:disabled { background: #d4d4d8; cursor: not-allowed; }
.tl-btn-outline {
    background: transparent;
    color: #111;
    border: 1px solid var(--c-border);
}
.tl-btn-outline:hover { background: var(--c-bg); }
.tl-btn-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.tl-btn-danger:hover { background: #fee2e2; }

/* ─── Lock Notice ──────────────────────────────────── */
.tl-lock-notice {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: #fafafa;
    border: 1px dashed var(--c-border);
    border-radius: var(--radius-sm);
    font-size: 12px;
    color: var(--c-muted);
    margin-top: 8px;
    line-height: 1.5;
}
.tl-lock-notice i { color: #a1a1aa; margin-top: 1px; flex-shrink: 0; }

/* ─── Forecast Card ────────────────────────────────── */
.tl-forecast {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.tl-forecast-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--c-border);
}
.tl-forecast-head h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #111;
}
.tl-forecast-body { padding: 14px 16px; }

.tl-status-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}
.tl-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tl-status-label { font-size: 15px; font-weight: 600; color: #111; }
.tl-status-sub { font-size: 12px; color: var(--c-muted); margin-top: 1px; }

.tl-kv-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 14px;
}
.tl-kv {
    background: var(--c-bg);
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
}
.tl-kv-label { font-size: 10px; font-weight: 600; color: var(--c-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px; }
.tl-kv-value { font-size: 13px; font-weight: 600; color: #111; }

/* ─── Forecast Table ───────────────────────────────── */
.tl-table-wrap {
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.tl-table-head {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: var(--c-bg);
    border-bottom: 1px solid var(--c-border);
    font-size: 12px;
    font-weight: 600;
    color: #111;
}
.tl-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.tl-table th {
    padding: 6px 12px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    color: var(--c-muted);
    text-transform: uppercase;
    letter-spacing: .04em;
    background: var(--c-bg);
    border-bottom: 1px solid var(--c-border);
}
.tl-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #f4f4f5;
    vertical-align: middle;
    color: #333;
}
.tl-table tr:last-child td { border-bottom: none; }
.tl-table .cp-name { font-weight: 500; color: #111; }
.tl-table .cp-stage { font-size: 10px; color: var(--c-muted); margin-top: 1px; }

/* ─── Add Event Panel ──────────────────────────────── */
.tl-add-event {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 12px;
}
.tl-add-event-head {
    padding: 12px 16px;
    border-bottom: 1px solid var(--c-border);
    background: var(--c-bg);
}
.tl-add-event-head h4 { margin: 0; font-size: 13px; font-weight: 600; color: #111; }
.tl-add-event-head p { margin: 2px 0 0; font-size: 12px; color: var(--c-muted); }
.tl-add-event-body { padding: 14px 16px; }

.tl-form-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.tl-form-group { display: flex; flex-direction: column; gap: 4px; }
.tl-form-group label { font-size: 11px; font-weight: 600; color: var(--c-muted); text-transform: uppercase; letter-spacing: .04em; }
.tl-form-input {
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 7px 10px;
    font-size: 13px;
    font-family: inherit;
    color: #111;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.tl-form-input:focus { border-color: var(--c-purple); box-shadow: 0 0 0 3px rgba(109,91,208,.1); }
.tl-form-input.date { font-family: 'DM Mono', monospace; width: 160px; }
.tl-form-input.title { width: 260px; }

/* ─── Events List ──────────────────────────────────── */
.tl-events-list {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.tl-events-head {
    padding: 12px 16px;
    border-bottom: 1px solid var(--c-border);
    background: var(--c-bg);
}
.tl-events-head h4 { margin: 0; font-size: 13px; font-weight: 600; color: #111; }
.tl-events-head p { margin: 2px 0 0; font-size: 12px; color: var(--c-muted); }

.tl-event-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f4f4f5;
}
.tl-event-item:last-child { border-bottom: none; }
.tl-event-info { flex: 1; }
.tl-event-info strong { display: block; font-size: 13px; font-weight: 500; color: #111; margin-bottom: 3px; }
.tl-event-info .tl-event-meta { font-size: 11px; color: var(--c-muted); }

.tl-event-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; min-width: 180px; }
.tl-event-action-row { display: flex; gap: 6px; align-items: center; }

.tl-textarea {
    width: 100%;
    border: 1px solid var(--c-border);
    border-radius: var(--radius-sm);
    padding: 7px 10px;
    font-size: 12px;
    font-family: inherit;
    color: #111;
    outline: none;
    resize: vertical;
    min-height: 56px;
    transition: border-color .15s;
}
.tl-textarea:focus { border-color: #dc2626; }

.tl-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 28px 16px;
    color: var(--c-muted);
    font-size: 13px;
}
.tl-empty i { font-size: 22px; color: #d4d4d8; }

/* ─── Alert ────────────────────────────────────────── */
.tl-alert {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    margin-bottom: 12px;
    border: 1px solid;
}
.tl-alert.success { background: var(--c-green-light); color: #15803d; border-color: #bbf7d0; }
.tl-alert.warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.tl-alert i { margin-top: 1px; flex-shrink: 0; }
</style>

<div class="tl-page">

    {{-- ── Page Header ──────────────────────────────────── --}}
    <div class="tl-header">
        <div class="tl-header-left">
            <div class="tl-header-icon"><i class="fas fa-route"></i></div>
            <h2>Timeline Tracking</h2>
        </div>
        @if($readOnlyTimeline)
            <span class="tl-badge badge-done">
                <i class="fas fa-check-circle"></i> Read-only
            </span>
        @endif
    </div>

    {{-- ── Flash Messages ───────────────────────────────── --}}
    @if(session('success'))
        <div class="tl-alert success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('prediction_warning'))
        <div class="tl-alert warning">
            <i class="fas fa-triangle-exclamation"></i>
            {{ session('prediction_warning') }}
        </div>
    @endif

    @if($readOnlyTimeline)
        <div class="tl-banner success">
            <i class="fas fa-award"></i>
            All checkpoints completed. Timeline is now read-only.
        </div>
    @endif

    @if(isset($missingPrerequisites) && $missingPrerequisites !== [])
        <div class="tl-banner warning">
            <i class="fas fa-circle-exclamation"></i>
            Prediction unavailable — missing: {{ implode(', ', $missingPrerequisites) }}
        </div>
    @endif

    @if($targetUser)
        {{-- ── Student Selector (Lecturer) ─────────────────── --}}
        @if(auth()->user()->hasRole('lecturer') && $students->isNotEmpty())
            <div class="tl-selector">
                <form method="GET" action="{{ route('timeline.index') }}">
                    <label for="student_id">Select Student</label>
                    <select id="student_id" name="student_id" onchange="this.form.submit()">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}"
                                {{ (int) $selectedStudentId === (int) $student->id ? 'selected' : '' }}>
                                {{ $student->name }}@if($student->cluster) · {{ $student->cluster }}@endif
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif

        {{-- ── Tabs ─────────────────────────────────────────── --}}
        <nav class="tl-tabs">
            <a class="tl-tab {{ $activeTab === 'checkpoint' ? 'active' : '' }}"
               href="{{ route('timeline.index', array_merge($tabParams, ['tab' => 'checkpoint'])) }}">
                <i class="fas fa-list-check"></i> Progress
            </a>
            <a class="tl-tab {{ $activeTab === 'crud' ? 'active' : '' }}"
               href="{{ route('timeline.index', array_merge($tabParams, ['tab' => 'crud'])) }}">
                <i class="fas fa-calendar-plus"></i> Add Event
            </a>
        </nav>

        {{-- ════════════════════════════════════════════════════
             TAB: CHECKPOINT PROGRESS
        ════════════════════════════════════════════════════ --}}
        <div class="{{ $activeTab === 'checkpoint' ? '' : 'd-none' }}">

            @if(auth()->user()->hasRole('student'))
                <form method="POST" action="{{ route('timeline.checkpoints.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- ── Stage Cards ───────────────────────────────── --}}
                    <div class="tl-stages-grid">

                        {{-- Stage 1 --}}
                        <div class="tl-stage stage-1">
                            <div class="tl-stage-bar">
                                <div class="tl-stage-bar-fill" style="width: {{ round(($s1CompletedCount / 4) * 100) }}%"></div>
                            </div>
                            <div class="tl-stage-head">
                                <div class="tl-stage-num">1</div>
                                <div class="tl-stage-title">
                                    <strong>Proposal &amp; Research Review</strong>
                                    <span>Complete all four fields to unlock Stage 2</span>
                                </div>
                                @if($readOnlyTimeline)
                                    <span class="tl-badge badge-done"><i class="fas fa-check"></i> Done</span>
                                @else
                                    <span class="tl-badge badge-progress">{{ $s1CompletedCount }}/4</span>
                                @endif
                            </div>

                            <div class="tl-stage-body">

                                {{-- EKC500 Approval --}}
                                <div class="tl-cp-item">
                                    <div class="tl-cp-info">
                                        <strong>EKC500 Admin Approval</strong>
                                        <p>Verify the administration has approved your EKC500 status.</p>
                                    </div>
                                    <div class="tl-cp-action">
                                        <label class="tl-toggle">
                                            <input type="hidden" name="ekc500_admin_approved" value="0">
                                            <input type="checkbox" name="ekc500_admin_approved" value="1"
                                                {{ old('ekc500_admin_approved', $checkpoint?->ekc500_admin_approved ?? false) ? 'checked' : '' }}
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            <span>Approved</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Proposal Review Date --}}
                                <div class="tl-cp-item">
                                    <div class="tl-cp-info">
                                        <strong>Proposal Review Date</strong>
                                        <div class="tl-cp-meta">
                                            @if($baselineCheckpointDates['proposal_review_date'])
                                                <span class="tl-cp-meta-tag meta-baseline">Baseline {{ $baselineCheckpointDates['proposal_review_date'] }}</span>
                                            @endif
                                            @if($liveForecastCheckpointDates['proposal_review_date'])
                                                <span class="tl-cp-meta-tag meta-forecast">Forecast {{ $liveForecastCheckpointDates['proposal_review_date'] }}</span>
                                            @endif
                                            @if($actualCheckpointDates['proposal_review_date'])
                                                <span class="tl-cp-meta-tag meta-actual">Actual {{ $actualCheckpointDates['proposal_review_date'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tl-cp-action">
                                        <input type="date" name="proposal_review_date"
                                            class="tl-input {{ !empty($actualCheckpointDates['proposal_review_date']) ? 'filled' : '' }}"
                                            value="{{ old('proposal_review_date', optional($checkpoint?->proposal_review_date)->format('Y-m-d')) }}"
                                            {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                        @error('proposal_review_date')
                                            <span style="font-size:11px; color:#dc2626; display:block; margin-top:3px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Publish Paper --}}
                                <div class="tl-cp-item">
                                    <div class="tl-cp-info">
                                        <strong>Publish Paper</strong>
                                        <p>Confirm your paper has been published.</p>
                                    </div>
                                    <div class="tl-cp-action">
                                        <label class="tl-toggle">
                                            <input type="hidden" name="publish_paper" value="0">
                                            <input type="checkbox" name="publish_paper" value="1"
                                                {{ old('publish_paper', $checkpoint?->publish_paper ?? false) ? 'checked' : '' }}
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            <span>Published</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Research Review Date --}}
                                <div class="tl-cp-item">
                                    <div class="tl-cp-info">
                                        <strong>Research Review Date</strong>
                                        <div class="tl-cp-meta">
                                            @if($baselineCheckpointDates['research_review_date'])
                                                <span class="tl-cp-meta-tag meta-baseline">Baseline {{ $baselineCheckpointDates['research_review_date'] }}</span>
                                            @endif
                                            @if($liveForecastCheckpointDates['research_review_date'])
                                                <span class="tl-cp-meta-tag meta-forecast">Forecast {{ $liveForecastCheckpointDates['research_review_date'] }}</span>
                                            @endif
                                            @if($actualCheckpointDates['research_review_date'])
                                                <span class="tl-cp-meta-tag meta-actual">Actual {{ $actualCheckpointDates['research_review_date'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tl-cp-action">
                                        <input type="date" name="research_review_date"
                                            class="tl-input {{ !empty($actualCheckpointDates['research_review_date']) ? 'filled' : '' }}"
                                            value="{{ old('research_review_date', optional($checkpoint?->research_review_date)->format('Y-m-d')) }}"
                                            {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                        @error('research_review_date')
                                            <span style="font-size:11px; color:#dc2626; display:block; margin-top:3px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                            </div>{{-- /stage-body --}}

                            <div class="tl-stage-foot">
                                <span class="tl-foot-meta">
                                    <strong>{{ $s1CompletedCount }} / 4</strong> fields completed
                                </span>
                                <button type="submit" class="tl-btn tl-btn-primary" {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                    <i class="fas fa-floppy-disk"></i> Save
                                </button>
                            </div>
                        </div>{{-- /stage 1 --}}

                        {{-- Stage 2 --}}
                        <div class="tl-stage stage-2 {{ $stage2Locked ? 'locked' : '' }}">
                            <div class="tl-stage-bar">
                                <div class="tl-stage-bar-fill"
                                     style="width: {{ $stage2Locked ? 0 : round(($s2CompletedCount / 2) * 100) }}%"></div>
                            </div>
                            <div class="tl-stage-head">
                                <div class="tl-stage-num">2</div>
                                <div class="tl-stage-title">
                                    <strong>Final Submission</strong>
                                    <span>{{ $stage2Locked ? 'Locked — complete Stage 1 first' : 'Enter submission checkpoint dates' }}</span>
                                </div>
                                @if($readOnlyTimeline)
                                    <span class="tl-badge badge-done"><i class="fas fa-check"></i> Done</span>
                                @elseif($stage2Locked)
                                    <span class="tl-badge badge-locked"><i class="fas fa-lock"></i> Locked</span>
                                @else
                                    <span class="tl-badge badge-progress">{{ $s2CompletedCount }}/2</span>
                                @endif
                            </div>

                            <div class="tl-stage-body">

                                @if($stage2Locked)
                                    <div class="tl-lock-notice">
                                        <i class="fas fa-lock"></i>
                                        <span>Complete all four Stage 1 checkpoints — EKC500 approval, proposal review, publish paper, and research review — to unlock Stage 2.</span>
                                    </div>
                                @else

                                    {{-- NOS --}}
                                    <div class="tl-cp-item">
                                        <div class="tl-cp-info">
                                            <strong>Notice of Submission (NOS) Date</strong>
                                            <div class="tl-cp-meta">
                                                @if($baselineCheckpointDates['nos_date'])
                                                    <span class="tl-cp-meta-tag meta-baseline">Baseline {{ $baselineCheckpointDates['nos_date'] }}</span>
                                                @endif
                                                @if($liveForecastCheckpointDates['nos_date'])
                                                    <span class="tl-cp-meta-tag meta-forecast">Forecast {{ $liveForecastCheckpointDates['nos_date'] }}</span>
                                                @endif
                                                @if($actualCheckpointDates['nos_date'])
                                                    <span class="tl-cp-meta-tag meta-actual">Actual {{ $actualCheckpointDates['nos_date'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="tl-cp-action">
                                            <input type="date" name="nos_date"
                                                class="tl-input {{ !empty($actualCheckpointDates['nos_date']) ? 'filled' : '' }}"
                                                value="{{ old('nos_date', optional($checkpoint?->nos_date)->format('Y-m-d')) }}"
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            @error('nos_date')
                                                <span style="font-size:11px; color:#dc2626; display:block; margin-top:3px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Final Draft Submission --}}
                                    <div class="tl-cp-item">
                                        <div class="tl-cp-info">
                                            <strong>Final Draft Submission Date</strong>
                                            <div class="tl-cp-meta">
                                                @if($baselineCheckpointDates['final_draft_submission_date'])
                                                    <span class="tl-cp-meta-tag meta-baseline">Baseline {{ $baselineCheckpointDates['final_draft_submission_date'] }}</span>
                                                @endif
                                                @if($liveForecastCheckpointDates['final_draft_submission_date'])
                                                    <span class="tl-cp-meta-tag meta-forecast">Forecast {{ $liveForecastCheckpointDates['final_draft_submission_date'] }}</span>
                                                @endif
                                                @if($actualCheckpointDates['final_draft_submission_date'])
                                                    <span class="tl-cp-meta-tag meta-actual">Actual {{ $actualCheckpointDates['final_draft_submission_date'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="tl-cp-action">
                                            <input type="date" name="final_draft_submission_date"
                                                class="tl-input {{ !empty($actualCheckpointDates['final_draft_submission_date']) ? 'filled' : '' }}"
                                                value="{{ old('final_draft_submission_date', optional($checkpoint?->final_draft_submission_date)->format('Y-m-d')) }}"
                                                {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            @error('final_draft_submission_date')
                                                <span style="font-size:11px; color:#dc2626; display:block; margin-top:3px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="tl-stage-foot">
                                        <span class="tl-foot-meta">
                                            <strong>{{ $s2CompletedCount }} / 2</strong> fields completed
                                        </span>
                                        <button type="submit" class="tl-btn tl-btn-primary" {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                            <i class="fas fa-floppy-disk"></i> Save
                                        </button>
                                    </div>

                                @endif
                            </div>{{-- /stage-body --}}
                        </div>{{-- /stage 2 --}}

                    </div>{{-- /stages-grid --}}
                </form>
            @endif

            {{-- ── Forecast Summary ──────────────────────────────── --}}
            <div class="tl-forecast">
                <div class="tl-forecast-head">
                    <h3><i class="fas fa-chart-line" style="color:var(--c-purple); margin-right:6px;"></i>Forecast Summary</h3>
                    @if($checkpoint?->last_predicted_at)
                        <span style="font-size:11px; color:var(--c-muted);">
                            Updated {{ $checkpoint->last_predicted_at->timezone(config('app.display_timezone'))->format('d M Y, H:i') }}
                        </span>
                    @endif
                </div>
                <div class="tl-forecast-body">

                    <div class="tl-status-row">
                        <div class="tl-status-dot" style="background: {{ $risk['dot'] }};"></div>
                        <div>
                            <div class="tl-status-label">{{ $forecastHeadline }}</div>
                            <div class="tl-status-sub">
                                @if($hasCompletedStudies)
                                    All required checkpoints are completed.
                                @elseif(($predictionState ?? null) === 'ready')
                                    Generated from your checkpoint and profile data.
                                @elseif(($predictionState ?? null) === 'unavailable_prerequisites')
                                    Missing profile details required to run the model.
                                @elseif(($predictionState ?? null) === 'refresh_failed')
                                    Latest forecast could not be refreshed right now.
                                @else
                                    Forecast is initialising.
                                @endif
                            </div>
                        </div>
                    </div>

                    @unless($hasCompletedStudies)
                        <div class="tl-kv-row">
                            <div class="tl-kv">
                                <div class="tl-kv-label">Risk Level</div>
                                <div class="tl-kv-value" style="color: {{ $risk['text'] }};">
                                    {{ $checkpoint?->risk_level ? ucfirst(str_replace('_', ' ', $checkpoint->risk_level)) : '—' }}
                                </div>
                            </div>
                            <div class="tl-kv">
                                <div class="tl-kv-label">Predicted GOT</div>
                                <div class="tl-kv-value">{{ $checkpoint?->predicted_got ? 'Yes' : '—' }}</div>
                            </div>
                        </div>
                    @endunless

                    {{-- Checkpoint Targets Table --}}
                    <div class="tl-table-wrap">
                        <div class="tl-table-head">
                            <i class="fas fa-calendar-check" style="color:var(--c-purple); font-size:12px;"></i>
                            Checkpoint Targets
                        </div>
                        <table class="tl-table">
                            <thead>
                                <tr>
                                    <th>Checkpoint</th>
                                    <th>Baseline</th>
                                    <th>Forecast</th>
                                    <th>Actual</th>
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
                                            <div class="cp-name">{{ $row['label'] }}</div>
                                            <div class="cp-stage">Stage {{ (int) $row['stage'] }}</div>
                                        </td>
                                        <td>{{ $baseline ?: '—' }}</td>
                                        <td style="color: #2563eb;">{{ $forecast ?: '—' }}</td>
                                        <td style="color: {{ $actual ? 'var(--c-green)' : 'inherit' }}; font-weight: {{ $actual ? '600' : '400' }};">
                                            {{ $actual ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Warnings / Missing prerequisites --}}
                    @if(!$hasCompletedStudies && ($predictionState ?? null) === 'unavailable_prerequisites' && !empty($missingPrerequisites))
                        <div class="tl-banner warning" style="margin-top: 12px; margin-bottom: 0;">
                            <i class="fas fa-circle-exclamation"></i>
                            Missing: {{ implode(', ', $missingPrerequisites) }}
                        </div>
                    @elseif(!$hasCompletedStudies && ($predictionState ?? null) === 'refresh_failed' && !empty($predictionWarning))
                        <div class="tl-banner warning" style="margin-top: 12px; margin-bottom: 0;">
                            <i class="fas fa-triangle-exclamation"></i>
                            {{ $predictionWarning }}
                        </div>
                    @endif

                </div>
            </div>

        </div>{{-- /checkpoint tab --}}


        {{-- ════════════════════════════════════════════════════
             TAB: ADD EVENT
        ════════════════════════════════════════════════════ --}}
        <div class="{{ $activeTab === 'crud' ? '' : 'd-none' }}">

            @if(auth()->user()->hasRole('student'))
                <div class="tl-add-event">
                    <div class="tl-add-event-head">
                        <h4>New Event</h4>
                        <p>Add an extra timeline event — requires supervisor review before approval.</p>
                    </div>
                    <div class="tl-add-event-body">
                        @if($readOnlyTimeline)
                            <div class="tl-banner success" style="margin-bottom: 12px;">
                                <i class="fas fa-lock"></i>
                                Timeline is read-only after completion. New events cannot be added.
                            </div>
                        @endif
                        <form action="{{ route('timeline.store') }}" method="POST">
                            @csrf
                            <div class="tl-form-row">
                                <div class="tl-form-group" style="flex: 1;">
                                    <label for="crud_title">Title</label>
                                    <input type="text" id="crud_title" name="title"
                                           class="tl-form-input title"
                                           value="{{ old('title') }}"
                                           placeholder="e.g. Internal mock presentation" required
                                           {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                    @error('title') <span style="font-size:11px;color:#dc2626;">{{ $message }}</span> @enderror
                                </div>
                                <div class="tl-form-group">
                                    <label for="crud_event_date">Target Date</label>
                                    <input type="date" id="crud_event_date" name="event_date"
                                           class="tl-form-input date"
                                           value="{{ old('event_date') }}" required
                                           {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                    @error('event_date') <span style="font-size:11px;color:#dc2626;">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" class="tl-btn tl-btn-primary" {{ $readOnlyTimeline ? 'disabled' : '' }}>
                                    <i class="fas fa-paper-plane"></i> Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif(auth()->user()->hasRole('lecturer'))
                <div class="tl-banner warning" style="margin-bottom: 12px;">
                    <i class="fas fa-circle-exclamation"></i>
                    Event creation is available for students only.
                </div>
            @endif

            {{-- Events List --}}
            <div class="tl-events-list">
                <div class="tl-events-head">
                    <h4>Additional Events</h4>
                    <p>Student-created checkpoint requests and their approval status.</p>
                </div>

                @if($customRows->isEmpty())
                    <div class="tl-empty">
                        <i class="fas fa-calendar-xmark"></i>
                        No additional events yet.
                    </div>
                @else
                    @foreach($customRows as $row)
                        <div class="tl-event-item">
                            <div class="tl-event-info">
                                <strong>{{ $row->title }}</strong>
                                <div class="tl-event-meta">
                                    {{ optional($row->event_date)->format('d M Y') }}
                                    &middot;
                                    {{ ucfirst($row->status ?? 'approved') }}
                                    @if($row->status === 'approved')
                                        &middot; {{ $row->completed ? 'Completed' : 'Not completed' }}
                                    @endif
                                </div>
                                @if($row->status === 'rejected' && $row->review_note)
                                    <div class="tl-banner warning" style="margin-top:6px; margin-bottom:0; font-size:12px;">
                                        <i class="fas fa-ban"></i>
                                        <span><strong>Reason:</strong> {{ $row->review_note }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="tl-event-actions">
                                @if(auth()->user()->hasRole('lecturer') && $row->status === 'pending')
                                    <form action="{{ route('timeline.approve', $row->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <button type="submit" class="tl-btn tl-btn-primary" style="width:100%; font-size:12px; padding:5px 10px;">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('timeline.reject', $row->id) }}" method="POST" style="display:flex; flex-direction:column; gap:4px;">
                                        @csrf @method('PUT')
                                        <textarea name="review_note" class="tl-textarea" rows="2"
                                                  maxlength="1000" placeholder="Reason for rejection" required></textarea>
                                        <button type="submit" class="tl-btn tl-btn-danger" style="font-size:12px; padding:5px 10px;">
                                            <i class="fas fa-ban"></i> Reject
                                        </button>
                                    </form>

                                @elseif(auth()->user()->hasRole('student') && (int) $row->proposed_by_user_id === (int) auth()->id() && $row->status === 'approved')
                                    @if($readOnlyTimeline)
                                        <span class="tl-badge badge-done">Read-only</span>
                                    @else
                                        <form action="{{ route('timeline.completion.update', $row->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="completed" value="0">
                                            <div class="tl-event-action-row">
                                                <label class="tl-toggle">
                                                    <input type="checkbox" name="completed" value="1" {{ $row->completed ? 'checked' : '' }}>
                                                    <span>Mark complete</span>
                                                </label>
                                                <button type="submit" class="tl-btn tl-btn-outline" style="font-size:12px; padding:4px 10px;">
                                                    <i class="fas fa-floppy-disk"></i>
                                                </button>
                                            </div>
                                        </form>
                                        <form action="{{ route('timeline.destroy', $row->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this approved checkpoint?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="tl-btn tl-btn-danger" style="font-size:12px; padding:5px 10px; width:100%;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endif

                                @else
                                    @php
                                        $sc = $row->status === 'approved' ? 'badge-done'
                                            : ($row->status === 'rejected' ? 'badge-locked' : 'badge-progress');
                                        $sl = $row->status === 'approved' ? 'Approved'
                                            : ($row->status === 'rejected' ? 'Rejected' : 'Pending review');
                                    @endphp
                                    <span class="tl-badge {{ $sc }}">{{ $sl }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        </div>{{-- /crud tab --}}

    @endif
</div>
@endsection