@extends('layouts.app')

@section('title', 'Messages - USMGoGrad')

@section('content')


<style>
    .messages-header {
        background: transparent;
        color: var(--text);
        padding: 0 0 1rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.5rem;
    }

    .messages-header h3 {
        font-weight: 700;
        margin: 0;
        font-family: var(--font-display);
    }

    .student-list {
        display: grid;
        gap: 0.75rem;
    }

    .student-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1rem 1.25rem;
        box-shadow: 0 1px 4px rgba(17, 17, 17, 0.04);
        transition: all 0.25s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-left: 4px solid var(--lav-500);
        border: 1px solid var(--border);
        border-left: 4px solid var(--lav-500);
    }

    .student-card:hover {
        box-shadow: 0 4px 12px rgba(109, 91, 208, 0.10);
        transform: translateX(3px);
    }

    .student-info {
        flex-grow: 1;
    }

    .student-name {
        font-size: 0.98rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.25rem;
        font-family: var(--font-display);
    }

    .unread-badge {
        background: #e07d45;
        color: white;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
        margin-left: 0.5rem;
    }

    .student-actions {
        display: flex;
        gap: 0.5rem;
    }

    .student-actions .btn {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--n-200);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .student-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .student-actions {
            width: 100%;
            margin-top: 1rem;
        }

        .student-actions .btn {
            flex: 1;
        }
    }
</style>

<div class="messages-header">
    <h3><i class="fas fa-envelope" style="margin-right: 20px;"></i> Chat with Students Under Your Supervision</h3>
</div>

@forelse ($students as $student)
    <div class="student-card my-3">
        <div class="student-info">
            <div class="student-name">
                <i class="fas fa-user-circle"></i> {{ $student->name }}
                @if ($student->unread_count > 0)
                    <span class="unread-badge">
                        <i class="fas fa-bell"></i> {{ $student->unread_count }} Unread
                    </span>
                @endif
            </div>
            <small class="text-muted">
                @if($student->cluster)
                    {{ $student->cluster }}
                @endif
            </small>
        </div>
        <div class="student-actions">
            <a href="{{ route('lecturer.messages.student.chat', ['studentId' => $student->id]) }}" class="btn btn-primary">
                <i class="fas fa-comment"></i> View Chat
            </a>
        </div>
    </div>

    
@empty
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h4>No Students Assigned</h4>
        <p class="text-muted">You currently have no students assigned. Once students are assigned, you'll be able to message them here.</p>
    </div>
@endforelse

@endsection
