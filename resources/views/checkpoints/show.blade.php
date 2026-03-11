@extends('layouts.app')

@section('title', 'Checkpoint Tracking - USMGoGrad')

@section('content')
<style>
    .checkpoint-shell {
        max-width: 1120px;
        margin: 0 auto;
        padding: 2rem 1rem 3rem;
    }

    .checkpoint-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 1.5rem;
    }

    .checkpoint-card {
        background: var(--surface);
        border: 1px solid var(--lav-200);
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(124, 111, 179, 0.08);
        overflow: hidden;
    }

    .checkpoint-header {
        padding: 1.35rem 1.5rem 1rem;
        border-bottom: 1px solid var(--lav-200);
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
    }

    .checkpoint-header h2,
    .checkpoint-header h3 {
        margin: 0;
        color: var(--n-900);
    }

    .checkpoint-header p {
        margin: 0.35rem 0 0;
        color: var(--n-700);
    }

    .checkpoint-body {
        padding: 1.5rem;
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

    .checkpoint-field input,
    .checkpoint-select {
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

    .checkpoint-actions {
        margin-top: 1.25rem;
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    @media (max-width: 900px) {
        .checkpoint-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .checkpoint-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="checkpoint-shell">
    <div class="checkpoint-header mb-4 checkpoint-card">
        <div class="checkpoint-body">
            <h2><i class="fas fa-brain"></i> ML Checkpoint Tracking</h2>
            <p>Students update actual checkpoint dates here. The model converts them into semester-based signals and estimates the probability of graduating on time.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('prediction_warning'))
        <div class="alert alert-warning">{{ session('prediction_warning') }}</div>
    @endif

    @if(Auth::user()->hasRole('lecturer'))
        <div class="checkpoint-card mb-4">
            <div class="checkpoint-body">
                <form method="GET" action="{{ route('checkpoints.show') }}">
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
        </div>
    @endif

    @if($targetStudent)
    <div class="checkpoint-grid">
        <section class="checkpoint-card">
            <div class="checkpoint-header">
                <h3>Checkpoint Dates</h3>
                <p>{{ $targetStudent->name }} updates actual milestone dates here.</p>
            </div>
            <div class="checkpoint-body">
                <div class="checkpoint-meta">
                    <span class="checkpoint-pill">Cluster: {{ $targetStudent->cluster ?? 'Missing' }}</span>
                    <span class="checkpoint-pill">Study Type: {{ $targetStudent->study_mode ? str_replace('_', ' ', ucwords($targetStudent->study_mode, '_')) : 'Missing' }}</span>
                    <span class="checkpoint-pill">Registration: {{ optional($targetStudent->start_date)->format('d M Y') ?? 'Missing' }}</span>
                </div>

                @if($missingPrerequisites !== [])
                    <div class="checkpoint-note warning mb-3">
                        Prediction cannot run yet. Missing: {{ implode(', ', $missingPrerequisites) }}.
                    </div>
                @endif

                @if(Auth::user()->hasRole('student'))
                <form method="POST" action="{{ route('checkpoints.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="checkpoint-form-grid">
                        <div class="checkpoint-field">
                            <label for="proposal_review_date">Proposal Review Date</label>
                            <input id="proposal_review_date" type="date" name="proposal_review_date" value="{{ old('proposal_review_date', optional($checkpoint->proposal_review_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="checkpoint-field">
                            <label for="research_review_date">Research Review Date</label>
                            <input id="research_review_date" type="date" name="research_review_date" value="{{ old('research_review_date', optional($checkpoint->research_review_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="checkpoint-field">
                            <label for="nos_date">Notice of Submission Date</label>
                            <input id="nos_date" type="date" name="nos_date" value="{{ old('nos_date', optional($checkpoint->nos_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="checkpoint-field">
                            <label for="final_draft_submission_date">Final Draft Submission Date</label>
                            <input id="final_draft_submission_date" type="date" name="final_draft_submission_date" value="{{ old('final_draft_submission_date', optional($checkpoint->final_draft_submission_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="checkpoint-field">
                            <label for="viva_date">Viva Date</label>
                            <input id="viva_date" type="date" name="viva_date" value="{{ old('viva_date', optional($checkpoint->viva_date)->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="checkpoint-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save And Recalculate</button>
                    </div>
                </form>
                @else
                <div class="checkpoint-form-grid">
                    <div class="checkpoint-field">
                        <label>Proposal Review Date</label>
                        <input type="text" value="{{ optional($checkpoint->proposal_review_date)->format('d M Y') ?? 'Not submitted' }}" disabled>
                    </div>
                    <div class="checkpoint-field">
                        <label>Research Review Date</label>
                        <input type="text" value="{{ optional($checkpoint->research_review_date)->format('d M Y') ?? 'Not submitted' }}" disabled>
                    </div>
                    <div class="checkpoint-field">
                        <label>Notice of Submission Date</label>
                        <input type="text" value="{{ optional($checkpoint->nos_date)->format('d M Y') ?? 'Not submitted' }}" disabled>
                    </div>
                    <div class="checkpoint-field">
                        <label>Final Draft Submission Date</label>
                        <input type="text" value="{{ optional($checkpoint->final_draft_submission_date)->format('d M Y') ?? 'Not submitted' }}" disabled>
                    </div>
                    <div class="checkpoint-field">
                        <label>Viva Date</label>
                        <input type="text" value="{{ optional($checkpoint->viva_date)->format('d M Y') ?? 'Not submitted' }}" disabled>
                    </div>
                </div>
                @endif
            </div>
        </section>

        <aside class="prediction-panel">
            <section class="checkpoint-card">
                <div class="checkpoint-header">
                    <h3>Prediction Output</h3>
                    <p>Probability-based early warning from the trained GoT classifier.</p>
                </div>
                <div class="checkpoint-body">
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
                            <div class="fw-semibold text-uppercase risk-pending">
                                Cold Start
                            </div>
                            <p class="mb-0 mt-2 text-muted">
                                The model is waiting for the first actual checkpoint date before generating a GoT probability.
                            </p>
                        </div>

                        <div class="checkpoint-note info mt-3">
                            Last checked: {{ optional($checkpoint->last_predicted_at)->format('d M Y H:i') ?? 'Not available' }}<br>
                            Model version: {{ $checkpoint->model_version ?? 'Unknown' }}
                        </div>
                    @else
                        <div class="checkpoint-note info">
                            No prediction has been generated yet. Save checkpoint dates after the student registration details are complete.
                        </div>
                    @endif
                </div>
            </section>

            <section class="checkpoint-card">
                <div class="checkpoint-header">
                    <h3>How The Model Reads This</h3>
                    <p>The model does not use raw date strings directly.</p>
                </div>
                <div class="checkpoint-body">
                    <div class="checkpoint-note info">
                        The Python pipeline converts each submitted date into a semester index relative to the student's registration date, then uses cluster, study type, and checkpoint timing to estimate GoT probability. Until the first checkpoint is recorded, the student stays in a cold-start pending state instead of being flagged as high risk.
                    </div>
                </div>
            </section>
        </aside>
    </div>
    @else
        <div class="alert alert-info">No student is available for checkpoint tracking yet.</div>
    @endif
</div>
@endsection