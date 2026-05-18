@extends('layouts.app')

@section('title', 'Messages - USMGoGrad')

@section('content')
<style>
    .messenger-page {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .messages-header {
        background: var(--hue-gradient);
        color: var(--text);
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--lav-200);
        border-radius: var(--radius);
        box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
    }

    .messages-header h3 {
        font-weight: 700;
        margin: 0;
        font-family: var(--font-display);
        font-size: 1.05rem;
        color: var(--n-900);
    }

    .messages-header p {
        margin: 0.25rem 0 0;
        color: var(--n-700);
        font-size: 0.86rem;
    }

    .student-list {
        display: grid;
        gap: 0.9rem;
    }

    .student-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(249,247,255,0.98) 100%);
        border: 1px solid var(--lav-200);
        border-left: 4px solid var(--lav-400);
        border-radius: 16px;
        padding: 1rem 1.1rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(124, 111, 179, 0.12);
    }

    .student-info {
        flex-grow: 1;
        min-width: 0;
    }

    .student-name {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.55rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--n-900);
        margin-bottom: 0.3rem;
        font-family: var(--font-display);
    }

    .student-name i {
        color: var(--lav-500);
    }

    .student-meta {
        color: var(--n-700);
        font-size: 0.85rem;
    }

    .unread-badge {
        background: var(--hue-gradient);
        color: var(--lav-500);
        padding: 0.32rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid var(--lav-200);
    }

    .student-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .student-actions .btn {
        padding: 0.55rem 1rem;
        font-size: 0.85rem;
        border-radius: 999px;
        background: var(--hue-gradient);
        border: 1px solid var(--lav-200);
        color: var(--lav-500);
        font-weight: 700;
    }

    .student-actions .btn:hover {
        box-shadow: 0 6px 16px rgba(124, 111, 179, 0.12);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1.25rem;
        background: var(--surface);
        border-radius: 16px;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--lav-400);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .student-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .student-actions {
            width: 100%;
        }

        .student-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="messenger-page">
    <div class="messages-header">
        <h3><i class="fas fa-envelope-open-text me-2"></i> Messenger</h3>
        <p>Chat with the students under your supervision.</p>
    </div>

    @forelse ($students as $student)
        <div class="student-card">
            <div class="student-info">
                <div class="student-name">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ $student->name }}</span>
                    @if ($student->unread_count > 0)
                        <span class="unread-badge">
                            <i class="fas fa-bell"></i> {{ $student->unread_count }} unread
                        </span>
                    @endif
                </div>
                <div class="student-meta">
                    @if($student->cluster)
                        {{ $student->cluster }}
                    @else
                        No cluster assigned
                    @endif
                </div>
            </div>
            <div class="student-actions">
                <a href="{{ route('lecturer.messages.student.chat', ['studentId' => $student->id]) }}" class="btn">
                    <i class="fas fa-comment-dots me-1"></i> Open chat
                </a>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h4>No Students Assigned</h4>
            <p class="text-muted mb-0">You currently have no students assigned. Once students are assigned, you'll be able to message them here.</p>
        </div>
    @endforelse
</div>

@endsection
