@extends('layouts.app')

@section('title', 'Timeline - USMGoGrad')

@section('content')

@php
    // Stage icon and colour mapping
    $stageStyles = [
        'prep'            => ['icon' => 'fa-book-open',     'color' => '#7c6fb3', 'bg' => '#f3f0ff'],
        'proposal'        => ['icon' => 'fa-file-signature','color' => '#e07d45', 'bg' => '#fff5ef'],
        'data_collection' => ['icon' => 'fa-chart-bar',     'color' => '#3d9970', 'bg' => '#e8f5e9'],
        'thesis'          => ['icon' => 'fa-graduation-cap','color' => '#c9a227', 'bg' => '#fffbf0'],
        'extension'       => ['icon' => 'fa-hourglass-half','color' => '#cc4b4b', 'bg' => '#fdecea'],
        'overdue'         => ['icon' => 'fa-exclamation-triangle','color' => '#cc4b4b', 'bg' => '#fdecea'],
        'max_duration'    => ['icon' => 'fa-flag-checkered','color' => '#cc4b4b', 'bg' => '#fdecea'],
    ];
    $defaultStyle = ['icon' => 'fa-circle-dot', 'color' => '#a99dd6', 'bg' => '#f3f0ff'];

    $stageCheckpoints = $timelines->filter(fn($t) => $t->system_key && str_starts_with($t->system_key, 'stage:'));
    $semesterRows     = $timelines->filter(fn($t) => $t->system_key && str_starts_with($t->system_key, 'semester:'));
    $customRows       = $timelines->filter(fn($t) => !$t->is_system_generated);
    $maxDurationRow   = $timelines->firstWhere('system_key', 'max_duration');

    $roadStops = collect();
    $semestersByStage = $semesterRows->groupBy('stage_key');

    foreach ($stageCheckpoints as $cp) {
        $roadStops->push($cp);
    }
    foreach ($customRows as $custom) {
        $roadStops->push($custom);
    }
    if ($maxDurationRow && !$stageCheckpoints->contains('system_key', 'max_duration')) {
        $roadStops->push($maxDurationRow);
    }

    $roadStops = $roadStops->sortBy('event_date')->values();

    $useRoadView = $roadStops->isNotEmpty();
    if (!$useRoadView) {
        $roadStops = $timelines->sortBy('event_date')->values();
    }

    $totalStops = $roadStops->count();
    $completedStops = $roadStops->where('completed', true)->count();
@endphp

<style>
    /* ═══════════════════════════════════════════
       VISUAL PATH TIMELINE — Compact Edition
       ═══════════════════════════════════════════ */

    .path-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 1.25rem 2rem;
        border-radius: 18px;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .path-header::after {
        content: '';
        position: absolute;
        right: -40px; bottom: -40px;
        width: 200px; height: 200px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .path-header h1 { font-family: var(--font-display); font-weight: 700; margin-bottom: 0.2rem; font-size: 1.6rem; }
    .path-header p  { opacity: 0.85; margin: 0; font-size: 0.9rem; }

    /* Progress summary bar */
    .progress-summary {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .progress-card {
        flex: 1;
        min-width: 130px;
        background: var(--surface);
        border-radius: 12px;
        padding: 0.85rem 1.1rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        text-align: center;
    }
    .progress-card .pc-value {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .progress-card .pc-label {
        font-size: 0.78rem;
        color: var(--muted);
        margin-top: 0.25rem;
    }
    .risk-on_track .pc-value  { color: #3d9970; }
    .risk-at_risk  .pc-value  { color: #e07d45; }
    .risk-overdue  .pc-value  { color: #cc4b4b; }

    /* Form sections */
    .form-section {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        margin-bottom: 1.5rem;
        border: 1px solid var(--lav-200);
    }
    .form-section h5 {
        font-family: var(--font-display);
        color: var(--lav-500);
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }

    /* ── The Road / Path ─────────────────────── */
    .road-path {
        position: relative;
        padding: 1rem 0 2rem;
        width: 100%;
    }

    /* Road spine — left-aligned */
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

    /* ── Road Stop ── */
    .road-stop {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 0.4rem;
        padding-left: 100px;
    }

    /* The node on the spine */
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        flex-shrink: 0;
    }
    .road-stop:hover .road-node {
        transform: translateX(-50%) scale(1.1);
        box-shadow: 0 4px 16px rgba(124,111,179,0.22);
    }

    .road-node.node-completed {
        background: #3d9970;
        color: white;
    }
    .road-node.node-current {
        animation: pulse-ring 2s ease-out infinite;
    }
    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(124,111,179,0.4); }
        70%  { box-shadow: 0 0 0 12px rgba(124,111,179,0); }
        100% { box-shadow: 0 0 0 0 rgba(124,111,179,0); }
    }
    .road-node.node-future {
        background: var(--n-100);
        color: var(--n-700);
        border-color: var(--n-200);
    }

    /* Card */
    .road-card {
        flex: 1;
        background: var(--surface);
        border-radius: 12px;
        padding: 0.8rem 1.1rem;
        box-shadow: 0 1px 6px rgba(46,41,36,0.05);
        border: 1px solid var(--lav-200);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
    }
    .road-stop:hover .road-card {
        box-shadow: 0 4px 18px rgba(124,111,179,0.13);
        transform: translateY(-2px);
    }

    /* Speech-bubble pointer */
    .road-card::after {
        content: '';
        position: absolute;
        top: 14px;
        left: -7px;
        width: 12px;
        height: 12px;
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-right: none;
        border-top: none;
        transform: rotate(45deg);
    }

    .road-card.card-completed      { border-left: 4px solid #3d9970; }
    .road-card.card-pending-approval { border-left: 4px solid #e07d45; }
    .road-card.card-rejected       { border-left: 4px solid #cc4b4b; opacity: 0.7; }

    /* Card internals — compact */
    .road-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.2rem;
    }
    .road-card-title {
        font-family: var(--font-display);
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text);
        margin: 0;
    }
    .road-card.card-completed .road-card-title { color: #3d9970; }

    .road-card-date {
        font-size: 0.78rem;
        color: var(--muted);
        margin-bottom: 0.35rem;
    }
    .road-card-date i { margin-right: 0.3rem; }

    /* Semester chips */
    .semester-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin-top: 0.35rem;
    }
    .semester-chip {
        font-size: 0.68rem;
        padding: 0.15rem 0.45rem;
        border-radius: 8px;
        background: var(--lav-100);
        color: var(--lav-500);
        font-weight: 600;
        white-space: nowrap;
    }
    .semester-chip.chip-done {
        background: #e8f5e9;
        color: #2e7d32;
    }

    /* Status pill */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.74rem;
        font-weight: 600;
    }
    .pill-completed        { background: #e8f5e9; color: #2e7d32; }
    .pill-in-progress      { background: var(--lav-100); color: var(--lav-500); }
    .pill-upcoming         { background: var(--n-100); color: var(--n-700); }
    .pill-pending-approval { background: #fff5ef; color: #e07d45; }
    .pill-rejected         { background: #fdecea; color: #cc4b4b; }

    /* Action buttons */
    .road-actions {
        display: flex;
        gap: 0.35rem;
        margin-top: 0.4rem;
        flex-wrap: wrap;
    }
    .road-actions .btn { font-size: 0.76rem; padding: 0.25rem 0.6rem; }

    /* ── Start / Finish markers ─────────────── */
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
        color: white;
        flex-shrink: 0;
    }
    .road-marker-label {
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--muted);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ── Empty state ───────────────────────── */
    .empty-road {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--muted);
    }
    .empty-road i { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; color: var(--lav-300); }

    /* ── Mobile ─────────────────────────────── */
    @media (max-width: 768px) {
        .road-path::before { left: 20px; }

        .road-stop { padding-left: 50px; }

        .road-node {
            left: 20px;
            width: 36px;
            height: 36px;
            font-size: 0.85rem;
        }

        .road-marker { padding-left: 4px; }
        .road-card::after { display: none; }
        .progress-summary { flex-direction: column; gap: 0.6rem; }
        .progress-card { min-width: auto; }
    }
</style>

{{-- ─── Header ──────────────────────────────── --}}
<div class="path-header">
    <h1><i class="fas fa-route"></i> My Journey</h1>
    <p>Your candidature progress path — one step at a time</p>
</div>

{{-- ─── Progress Snapshot Cards ────────────── --}}
@if($progressSnapshot)
<div class="progress-summary">
    <div class="progress-card risk-{{ $progressSnapshot['risk_status'] }}">
        <div class="pc-value">{{ $progressSnapshot['current_semester'] ?: '—' }}</div>
        <div class="pc-label">Current Semester</div>
    </div>
    <div class="progress-card">
        <div class="pc-value" style="color: var(--lav-500);">{{ $completedStops }}/{{ $totalStops }}</div>
        <div class="pc-label">Checkpoints Completed</div>
    </div>
    <div class="progress-card risk-{{ $progressSnapshot['risk_status'] }}">
        <div class="pc-value">
            @if($progressSnapshot['risk_status'] === 'on_track')
                <i class="fas fa-check-circle"></i>
            @elseif($progressSnapshot['risk_status'] === 'at_risk')
                <i class="fas fa-exclamation-circle"></i>
            @else
                <i class="fas fa-times-circle"></i>
            @endif
        </div>
        <div class="pc-label">
            {{ str_replace('_', ' ', ucwords($progressSnapshot['risk_status'], '_')) }}
        </div>
    </div>
    <div class="progress-card">
        <div class="pc-value" style="color: var(--gold-500); font-size: 1rem; padding-top: 0.25rem;">{{ $progressSnapshot['stage_title'] ?? '—' }}</div>
        <div class="pc-label" style="font-size:0.74rem;">Current Stage</div>
    </div>
</div>
@endif

{{-- ─── Lecturer Student Selector ─────────── --}}
@if(auth()->user()->hasRole('lecturer') && $students->isNotEmpty())
<div class="form-section">
    <h5><i class="fas fa-user-graduate"></i> Select Student</h5>
    <form method="GET" action="{{ route('timeline.index') }}">
        <div class="input-group">
            <select name="student_id" id="student_id" class="form-select" onchange="this.form.submit()">
                <option value="">— Choose a student —</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" {{ $selectedStudentId == $student->id ? 'selected' : '' }}>
                        {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                    </option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> View</button>
        </div>
    </form>
</div>
@endif

{{-- ─── Student: Add Event Form ── --}}
@if(auth()->user()->hasRole('student'))
<div class="form-section">
    <h5><i class="fas fa-plus"></i> Propose New Checkpoint</h5>
    <form action="{{ route('timeline.store') }}" method="POST">
        @csrf
        <div class="row g-2">
            <div class="col-md-6">
                <label for="title" class="form-label form-label-sm">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm @error('title') is-invalid @enderror" id="title" name="title" placeholder="e.g. Submit Literature Review" required>
                @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-4">
                <label for="event_date" class="form-label form-label-sm">Target Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm @error('event_date') is-invalid @enderror" id="event_date" name="event_date" required>
                @error('event_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>
        </div>
        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle"></i> New checkpoints need supervisor approval before appearing on your path.</small>
    </form>
</div>
@endif

{{-- ═══════════════════════════════════════════
     THE VISUAL ROAD PATH
     ═══════════════════════════════════════════ --}}
@if($roadStops->isNotEmpty())
<div class="road-path">

    {{-- Start marker --}}
    <div class="road-marker">
        <div class="road-marker-icon" style="background: var(--lav-500);"><i class="fas fa-play"></i></div>
        <div class="road-marker-label">Start of Journey</div>
    </div>

    @foreach($roadStops as $idx => $stop)
        @php
            $sk = $stop->stage_key ?? ($stop->system_key ? str_replace('stage:', '', $stop->system_key) : null);
            $style = $stageStyles[$sk] ?? $defaultStyle;
            $isCompleted = (bool) $stop->completed;
            $status = $stop->status ?? 'approved';

            $isCurrent = false;
            if (!$isCompleted && $status === 'approved') {
                $isCurrent = $roadStops->where('completed', false)->where('status', 'approved')->first()?->id === $stop->id;
            }

            $nodeClass = $isCompleted ? 'node-completed' : ($isCurrent ? 'node-current' : 'node-future');
            $cardClass = $isCompleted ? 'card-completed' : ($status === 'pending' ? 'card-pending-approval' : ($status === 'rejected' ? 'card-rejected' : ''));

            $relatedSemesters = ($sk && isset($semestersByStage[$sk])) ? $semestersByStage[$sk] : collect();
        @endphp

        <div class="road-stop">
            {{-- Node --}}
            <div class="road-node {{ $nodeClass }}"
                 @if(!$isCompleted && $nodeClass !== 'node-current') style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};" @endif>
                @if($isCompleted)
                    <i class="fas fa-check"></i>
                @elseif($isCurrent)
                    <i class="fas fa-location-dot" style="color: {{ $style['color'] }};"></i>
                @else
                    <i class="fas {{ $style['icon'] }}"></i>
                @endif
            </div>

            {{-- Card --}}
            <div class="road-card {{ $cardClass }}">
                {{-- Title + status on same row --}}
                <div class="road-card-header">
                    <div class="road-card-title">
                        <i class="fas {{ $style['icon'] }}" style="color: {{ $style['color'] }}; margin-right: 0.3rem; font-size: 0.85rem;"></i>
                        {{ $stop->title }}
                    </div>
                    {{-- Status pill inline with title --}}
                    @if($isCompleted)
                        <span class="status-pill pill-completed"><i class="fas fa-check-circle"></i> Completed</span>
                    @elseif($status === 'pending')
                        <span class="status-pill pill-pending-approval"><i class="fas fa-clock"></i> Awaiting Approval</span>
                    @elseif($status === 'rejected')
                        <span class="status-pill pill-rejected"><i class="fas fa-ban"></i> Rejected</span>
                    @elseif($isCurrent)
                        <span class="status-pill pill-in-progress"><i class="fas fa-spinner fa-pulse"></i> In Progress</span>
                    @else
                        <span class="status-pill pill-upcoming"><i class="fas fa-hourglass-start"></i> Upcoming</span>
                    @endif
                </div>

                {{-- Date + semester number --}}
                <div class="road-card-date">
                    <i class="fas fa-calendar-alt"></i>
                    {{ \Carbon\Carbon::parse($stop->event_date)->format('M d, Y') }}
                    @if($stop->semester_number)
                        &middot; Sem {{ $stop->semester_number }}
                    @endif
                </div>

                {{-- Semester chips --}}
                @if($relatedSemesters->isNotEmpty())
                    <div class="semester-chips">
                        @foreach($relatedSemesters->sortBy('semester_number') as $sem)
                            <span class="semester-chip {{ $sem->completed ? 'chip-done' : '' }}">
                                S{{ $sem->semester_number }}
                                @if($sem->completed) ✓ @endif
                            </span>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="road-actions">
                    @if(auth()->user()->hasRole('lecturer') && !$isCompleted && $status === 'approved')
                        <form action="{{ route('timeline.update', $stop->id) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $stop->title }}">
                            <input type="hidden" name="event_date" value="{{ $stop->event_date }}">
                            <input type="hidden" name="completed" value="1">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Complete</button>
                        </form>
                    @endif
                    @if(auth()->user()->hasRole('lecturer') && $status === 'pending')
                        <form action="{{ route('timeline.approve', $stop->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check-circle"></i> Approve</button>
                        </form>
                        <form action="{{ route('timeline.reject', $stop->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-ban"></i> Reject</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        {{-- Road connector removed — margin-bottom on .road-stop handles spacing --}}
    @endforeach

    {{-- Finish marker --}}
    <div class="road-marker" style="margin-top: 0.6rem;">
        <div class="road-marker-icon" style="background: {{ $completedStops >= $totalStops ? '#3d9970' : 'var(--n-200)' }};">
            <i class="fas fa-flag-checkered"></i>
        </div>
        <div class="road-marker-label">
            {{ $completedStops >= $totalStops ? 'Journey Complete!' : 'Finish Line' }}
        </div>
    </div>
</div>

@else
    <div class="empty-road">
        <i class="fas fa-road"></i>
        <h4 style="font-family: var(--font-display); margin-bottom: 0.5rem;">No Checkpoints Yet</h4>
        @if(auth()->user()->hasRole('student'))
            <p>Your journey path will appear once your start date and study mode are set.<br>Ask your supervisor or admin to configure your candidature details.</p>
        @elseif(auth()->user()->hasRole('lecturer'))
            <p>Select a student above to view their candidature journey.</p>
        @else
            <p>No timeline data to display.</p>
        @endif
    </div>
@endif

@endsection