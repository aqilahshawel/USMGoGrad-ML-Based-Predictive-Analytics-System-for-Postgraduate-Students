@extends('layouts.app')

@section('title', 'Supervisor Assignment - USMGradView 360')

@section('content')

<style>
    .supervisors-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
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
    <h2><i class="fas fa-users-cog"></i> Supervisor Assignment</h2>
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
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
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

<!-- Current Assignments -->
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
                                <i class="fas fa-user-graduate"></i> {{ $student->name }}
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

@endsection
