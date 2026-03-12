@extends('layouts.app')

@section('title', 'Assignment - USMGoGrad')

@section('content')

<style>
    .supervisors-header {
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
        color: var(--n-900);
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .supervisors-header h2 {
        font-weight: 700;
        margin: 0;
        font-size: 2rem;
        font-family: var(--font-display);
    }

    .assignment-form-section {
        background: var(--surface);
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        margin-bottom: 2rem;
        border: 1px solid var(--lav-200);
    }

    .assignment-form-section h4 {
        font-family: var(--font-display);
        color: var(--lav-500);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        font-weight: 600;
        color: var(--n-900);
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
        font-size: 0.92rem;
    }

    .form-group label i {
        margin-right: 0.5rem;
        color: var(--lav-400);
        font-size: 1rem;
    }

    .form-select {
        border-radius: 8px;
        border: 1.5px solid var(--n-200);
        padding: 0.85rem 1rem;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }

    .form-select:focus {
        border-color: var(--lav-400);
        box-shadow: 0 0 0 3px rgba(124,111,179,0.12);
        outline: none;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .btn-assign {
        background: var(--lav-500);
        color: white;
        padding: 0.85rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .btn-assign:hover {
        background: var(--lav-400);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(124,111,179,0.25);
        color: white;
    }

    .assignments-section {
        margin-top: 2rem;
    }

    .assignments-section h3 {
        color: var(--lav-500);
        font-weight: 700;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        font-family: var(--font-display);
    }

    .assignments-section h3 i {
        margin-right: 0.75rem;
    }

    .supervisor-card {
        background: var(--surface);
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        margin-bottom: 1.5rem;
        border-top: 4px solid var(--lav-400);
        border: 1px solid var(--lav-200);
        border-top: 4px solid var(--lav-400);
        transition: all 0.25s ease;
    }

    .supervisor-card:hover {
        box-shadow: 0 4px 16px rgba(124,111,179,0.12);
        transform: translateY(-1px);
    }

    .supervisor-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--lav-500);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        font-family: var(--font-display);
    }

    .supervisor-name i {
        margin-right: 0.75rem;
        font-size: 1.5rem;
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
        .form-row {
            grid-template-columns: 1fr;
        }

        .supervisors-header h2 {
            font-size: 1.5rem;
        }

        .assignment-form-section {
            padding: 1.5rem;
        }
    }
</style>

<div class="supervisors-header">
    <h2><i class="fas fa-users-cog"></i> Assignment</h2>
</div>

<!-- Assignment Form -->
<div class="assignment-form-section">
    <h4 class="mb-3"><i class="fas fa-plus-circle"></i> Assign Student to Supervisor</h4>
    
    <form action="{{ route('supervisors.assign') }}" method="POST">
        @csrf
        
        <div class="form-row">
            <div class="form-group">
                <label for="student">
                    <i class="fas fa-user-graduate"></i> Select Student
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
                    <i class="fas fa-user-tie"></i> Select Supervisor
                </label>
                <select name="supervisor_id" id="supervisor" class="form-select" required>
                    <option value="">-- Choose a Supervisor --</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn-assign">
            <i class="fas fa-link"></i> Assign Supervisor
        </button>
    </form>
</div>

<!-- Cluster Update Form (Admin Only) -->
<div class="assignment-form-section">
    <h4 class="mb-3"><i class="fas fa-layer-group"></i> Update Student Cluster</h4>

    <form action="{{ route('students.cluster.update') }}" method="POST">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label for="cluster_student">
                    <i class="fas fa-user-graduate"></i> Select Student
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
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
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
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-assign">
            <i class="fas fa-save"></i> Update Cluster
        </button>
    </form>
</div>

<div class="assignment-form-section">
    <h4 class="mb-3"><i class="fas fa-calendar-day"></i> Update Registration Date</h4>

    <form action="{{ route('students.registration-date.update') }}" method="POST">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label for="registration_student">
                    <i class="fas fa-user-graduate"></i> Select Student
                </label>
                <select name="registration_student_id" id="registration_student" class="form-select" required>
                    <option value="">-- Choose a Student --</option>
                    @foreach(($allStudents ?? collect()) as $student)
                        <option value="{{ $student->id }}" {{ (string)old('registration_student_id') === (string)$student->id ? 'selected' : '' }}>
                            {{ $student->name }}
                            @if($student->cluster)
                                ({{ $student->cluster }})
                            @endif
                            @if($student->study_mode)
                                - {{ str_replace('_', ' ', ucwords($student->study_mode, '_')) }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('registration_student_id')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
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
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-assign">
            <i class="fas fa-save"></i> Update Registration Date
        </button>
    </form>
</div>

<div class="assignment-form-section">
    <h4 class="mb-3"><i class="fas fa-user-clock"></i> Update Study Type</h4>

    <form action="{{ route('students.study-mode.update') }}" method="POST">
        @csrf

        <div class="form-row">
            <div class="form-group">
                <label for="study_mode_student">
                    <i class="fas fa-user-graduate"></i> Select Student
                </label>
                <select name="study_mode_student_id" id="study_mode_student" class="form-select" required>
                    <option value="">-- Choose a Student --</option>
                    @foreach(($allStudents ?? collect()) as $student)
                        <option value="{{ $student->id }}" {{ (string)old('study_mode_student_id') === (string)$student->id ? 'selected' : '' }}>
                            {{ $student->name }}
                            @if($student->cluster)
                                ({{ $student->cluster }})
                            @endif
                            @if($student->start_date)
                                - {{ $student->start_date->format('d M Y') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('study_mode_student_id')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="candidature_study_mode">
                    <i class="fas fa-user-clock"></i> Study Type
                </label>
                <select name="study_mode" id="candidature_study_mode" class="form-select @error('study_mode') is-invalid @enderror" required>
                    <option value="">-- Select Study Type --</option>
                    @foreach(($studyModes ?? []) as $value => $label)
                        <option value="{{ $value }}" {{ old('study_mode') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('study_mode')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-assign">
            <i class="fas fa-save"></i> Update Study Type
        </button>
    </form>
</div>

@endsection
