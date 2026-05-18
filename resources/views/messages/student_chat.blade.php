@extends('layouts.app')

@section('styles')
    <style>
        .chat-box {
            border: 1px solid var(--border);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-radius: var(--radius);
            background: var(--n-50);
            height: 400px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
        }
        .sent {
            background: #eef2ff;
            text-align: right;
            border-left: 3px solid var(--lav-500);
        }
        .received {
            background: var(--surface);
            text-align: left;
            border: 1px solid var(--border);
            border-left: 3px solid var(--peach-400);
        }
        .chat-box .text-muted { font-size: 0.78rem; display: block; margin-top: 4px; color: var(--muted); }
        h3 { font-family: var(--font-display); color: var(--text); margin-bottom: 1.5rem; }
        .form-group { margin-top: 0.75rem; }
    </style>
@endsection

@section('content')
    <div class="container">
        <h3>Chat with Your Supervisor: {{ $supervisor->name ?? 'No Supervisor Assigned' }}</h3>

        <div class="chat-box">
            @forelse ($messages as $message)
                <div class="message @if ($message->sender_id == Auth::id()) sent @else received @endif">
                    <strong>{{ $message->sender->name ?? 'Unknown' }}:</strong>
                    <p>{{ $message->message }}</p>
                    <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                </div>
            @empty
                <p>No messages yet.</p>
            @endforelse
        </div>

        @if ($supervisor)
            <form action="{{ route('student.messages.send', ['lecturerId' => $supervisor->id]) }}" method="POST">
                @csrf
                <div class="form-group">
                    <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Send</button>
            </form>
        @else
            <p>You are not currently assigned a supervisor.</p>
        @endif
    </div>
@endsection
