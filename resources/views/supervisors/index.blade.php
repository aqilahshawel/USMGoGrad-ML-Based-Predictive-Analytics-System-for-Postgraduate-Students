@extends('layouts.app')

@section('title', 'Assignment - USMGoGrad')

@section('content')

<style>
    .supervisors-header {
        background: var(--hue-gradient);
        color: var(--n-900);
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--lav-200);
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
    }

    .supervisors-header h2 {
        font-weight: 700;
        margin: 0;
        font-size: 1.1rem;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    /* ── Accordion ─────────────────────────────── */
    .accordion-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .accordion-item {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .accordion-item:has(.accordion-body.open) {
        box-shadow: 0 4px 16px rgba(109, 91, 208, 0.1);
    }

    .accordion-header {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.1rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s ease;
        border-bottom: 1px solid transparent;
    }

    .accordion-header:hover {
        background: rgba(124, 111, 179, 0.04);
    }

    .accordion-header.open {
        border-bottom-color: var(--border);
    }

    .accordion-header-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .accordion-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .accordion-icon.purple { background: rgba(124, 111, 179, 0.12); color: var(--lav-500); }
    .accordion-icon.amber  { background: rgba(239, 159, 39, 0.12);  color: #854F0B; }
    .accordion-icon.red    { background: rgba(226, 75, 74, 0.12);   color: #A32D2D; }

    .accordion-header-text .acc-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text);
        font-family: var(--font-display);
        display: block;
    }

    .accordion-header-text .acc-desc {
        font-size: 0.82rem;
        color: var(--muted);
        display: block;
        margin-top: 1px;
    }

    .accordion-chevron {
        font-size: 0.75rem;
        color: var(--muted);
        transition: transform 0.25s ease;
        flex-shrink: 0;
    }

    .accordion-header.open .accordion-chevron {
        transform: rotate(180deg);
    }

    .accordion-body {
        display: none;
        padding: 1.5rem;
    }

    .accordion-body.open {
        display: block;
    }

    /* ── Form elements ──────────────────────────── */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-group label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .form-group label i {
        color: var(--lav-500);
        font-size: 0.85rem;
    }

    .form-select,
    .form-control {
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        padding: 0.7rem 0.85rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background: var(--surface);
        color: var(--text);
        width: 100%;
        box-sizing: border-box;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: var(--lav-500);
        box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
        outline: none;
    }

    .btn-submit {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: var(--radius-sm);
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-submit.purple {
        background: var(--lav-500);
        color: white;
    }

    .btn-submit.purple:hover {
        background: #5a4ab8;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(109, 91, 208, 0.3);
    }

    .btn-submit.red {
        background: #A32D2D;
        color: white;
    }

    .btn-submit.red:hover {
        background: #791F1F;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(162, 45, 45, 0.3);
    }

    .note-box {
        background: var(--n-50);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.88rem;
        color: var(--muted);
        border-left: 3px solid var(--lav-300);
        height: fit-content;
        margin-top: auto;
    }

    .warning-box {
        background: #FAEEDA;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.88rem;
        color: #633806;
        border-left: 3px solid #EF9F27;
        height: fit-content;
        margin-top: auto;
    }

    .invalid-feedback {
        font-size: 0.82rem;
        color: #A32D2D;
    }

    .feedback-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }

    .feedback-table th,
    .feedback-table td {
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }

    .feedback-table th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.04em;
        color: var(--muted);
        text-align: left;
    }

    .feedback-table td p {
        margin: 0;
        color: var(--text);
    }

    .feedback-meta {
        color: var(--muted);
        font-size: 0.78rem;
    }

    @media (max-width: 768px) {
        .supervisors-header h2 { font-size: 1.5rem; }
        .form-row { grid-template-columns: 1fr; }
        .accordion-header { padding: 1rem 1.25rem; }
        .accordion-body { padding: 1.25rem; }
    }
</style>

<div class="supervisors-header">
    <h2><i class="fas fa-users-cog"></i> Assignment</h2>
</div>

<div class="accordion-list">

    {{-- 1. Assign supervisor --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header open" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-link"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Assign Supervisor</span>
                    <span class="acc-desc">Link a student to their supervisor</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body open">
            <form action="{{ route('supervisors.assign') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="student_id" id="student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="supervisor">
                            <i class="fas fa-user-tie"></i> Supervisor
                        </label>
                        <select name="supervisor_id" id="supervisor" class="form-select" required>
                            <option value="">-- Choose a Supervisor --</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-link"></i> Assign Supervisor
                </button>
            </form>
        </div>
    </div>

    {{-- 2. Update cluster --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Update Student Cluster</span>
                    <span class="acc-desc">Change a student's research cluster</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('students.cluster.update') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="cluster_student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="student_id" id="cluster_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($allStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}@if($student->cluster) ({{ $student->cluster }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="cluster_select">
                            <i class="fas fa-layer-group"></i> Cluster
                        </label>
                        <select name="cluster" id="cluster_select" class="form-select">
                            <option value="">-- Clear Cluster --</option>
                            @foreach(($clusters ?? []) as $cluster)
                                <option value="{{ $cluster }}" {{ old('cluster') === $cluster ? 'selected' : '' }}>
                                    {{ $cluster }}
                                </option>
                            @endforeach
                        </select>
                        @error('cluster')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-save"></i> Update Cluster
                </button>
            </form>
        </div>
    </div>

    {{-- 3. Research title --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-book"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Update Research Title</span>
                    <span class="acc-desc">Set or update a student's research title</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('students.research-title.update') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="research_title_student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="research_title_student_id" id="research_title_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($allStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('research_title_student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}
                                    @if($student->cluster) ({{ $student->cluster }})@endif
                                    @if($student->research_title) - {{ $student->research_title }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('research_title_student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="research_title">
                            <i class="fas fa-book"></i> Research Title
                        </label>
                        <input
                            type="text"
                            name="research_title"
                            id="research_title"
                            class="form-control @error('research_title') is-invalid @enderror"
                            value="{{ old('research_title') }}"
                            placeholder="e.g., Predictive analytics for postgraduate milestones"
                        >
                        @error('research_title')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-save"></i> Update Research Title
                </button>
            </form>
        </div>
    </div>

    {{-- 4. Research objective --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Update Research Objective</span>
                    <span class="acc-desc">Set or update a student's research objective</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('students.research-objective.update') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="research_objective_student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="research_objective_student_id" id="research_objective_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($allStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('research_objective_student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}
                                    @if($student->cluster) ({{ $student->cluster }})@endif
                                    @if($student->research_objective) - objective set @endif
                                </option>
                            @endforeach
                        </select>
                        @error('research_objective_student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="research_objective">
                            <i class="fas fa-bullseye"></i> Research Objective
                        </label>
                        <textarea
                            name="research_objective"
                            id="research_objective"
                            class="form-control @error('research_objective') is-invalid @enderror"
                            rows="4"
                            placeholder="e.g., Develop a machine-learning model to forecast postgraduate milestone risks and recommend actionable interventions"
                        >{{ old('research_objective') }}</textarea>
                        @error('research_objective')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                        <small class="text-muted d-block mt-2">Max 2000 characters. Leave blank to clear.</small>
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-save"></i> Update Research Objective
                </button>
            </form>
        </div>
    </div>

    {{-- 5. Registration date --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Update Registration Date</span>
                    <span class="acc-desc">Update a student's candidature start date</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('students.registration-date.update') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="registration_student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="registration_student_id" id="registration_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($allStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('registration_student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}
                                    @if($student->cluster) ({{ $student->cluster }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('registration_student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="candidature_start_date">
                            <i class="fas fa-calendar-day"></i> Registration Date
                        </label>
                        <input
                            type="date"
                            name="start_date"
                            id="candidature_start_date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date') }}"
                            required
                        >
                        @error('start_date')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-save"></i> Update Registration Date
                </button>
            </form>
        </div>
    </div>

    {{-- 6. Unassign --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon amber">
                    <i class="fas fa-unlink"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Unassign Student from Supervisor</span>
                    <span class="acc-desc">Remove a supervisor link for a graduated student</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('supervisors.unassign') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="unassign_student">
                            <i class="fas fa-user-graduate"></i> Assigned Student
                        </label>
                        <select name="unassign_student_id" id="unassign_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($assignedStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('unassign_student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}
                                    @if($student->supervisor) (Supervisor: {{ $student->supervisor->name }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('unassign_student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Note</label>
                        <div class="note-box">
                            Use this to unassign a student who has graduated.
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit purple">
                    <i class="fas fa-unlink"></i> Unassign Student
                </button>
            </form>
        </div>
    </div>

    {{-- 6. Feedback submissions --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon purple">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Student Feedback</span>
                    <span class="acc-desc">View feedback submitted by students</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <div class="note-box" style="margin-bottom:1rem;">
                Feedback entries are read-only. You can remove entries if needed.
            </div>
            <div class="table-responsive">
                <table class="feedback-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Supervisor</th>
                            <th>Submitted</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($feedbackItems ?? collect()) as $item)
                            <tr>
                                <td>
                                    <p>{{ $item->name }}</p>
                                </td>
                                <td>
                                    <p>{{ $item->supervisor_name }}</p>
                                </td>
                                <td class="feedback-meta">
                                    {{ optional($item->created_at)->format('d M Y, H:i') }}
                                </td>
                                <td>
                                    <p>{{ $item->description }}</p>
                                </td>
                                <td>
                                    <form action="{{ route('feedback.delete', $item) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn-submit red" style="padding:0.35rem 0.75rem; font-size:0.78rem;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="feedback-meta">No feedback submissions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 7. Delete student --}}
    <div class="accordion-item">
        <button type="button" class="accordion-header" onclick="toggleAccordion(this)">
            <div class="accordion-header-left">
                <div class="accordion-icon red">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="accordion-header-text">
                    <span class="acc-title">Delete Student Account</span>
                    <span class="acc-desc">Permanently remove a student and all their records</span>
                </div>
            </div>
            <i class="fas fa-chevron-down accordion-chevron"></i>
        </button>
        <div class="accordion-body">
            <form action="{{ route('students.delete') }}" method="POST" onsubmit="return confirm('Delete this student account? This cannot be undone.');">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="delete_student">
                            <i class="fas fa-user-graduate"></i> Student
                        </label>
                        <select name="delete_student_id" id="delete_student" class="form-select" required>
                            <option value="">-- Choose a Student --</option>
                            @foreach(($allStudents ?? collect()) as $student)
                                <option value="{{ $student->id }}" {{ (string)old('delete_student_id') === (string)$student->id ? 'selected' : '' }}>
                                    {{ $student->name }}
                                    @if($student->cluster) ({{ $student->cluster }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('delete_student_id')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-exclamation-triangle"></i> Warning</label>
                        <div class="warning-box">
                            Deleting a student removes all their related records and cannot be undone.
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit red">
                    <i class="fas fa-trash"></i> Delete Student
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function toggleAccordion(header) {
    const isOpen = header.classList.contains('open');

    // Close all
    document.querySelectorAll('.accordion-header').forEach(h => h.classList.remove('open'));
    document.querySelectorAll('.accordion-body').forEach(b => b.classList.remove('open'));

    // Open clicked one if it was closed
    if (!isOpen) {
        header.classList.add('open');
        header.nextElementSibling.classList.add('open');
    }
}
</script>

@endsection