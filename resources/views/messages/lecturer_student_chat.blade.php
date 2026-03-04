@extends('layouts.app')

@section('styles')
    <style>
        .chat-box {
            border: 1px solid var(--lav-200);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-radius: 14px;
            background: var(--n-50);
            height: 400px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .sent {
            background: var(--lav-100);
            text-align: right;
            border-left: 3px solid var(--lav-400);
        }
        .received {
            background: var(--surface);
            text-align: left;
            border: 1px solid var(--n-200);
            border-left: 3px solid var(--peach-400);
        }
        .chat-box .text-muted { font-size: 0.78rem; display: block; margin-top: 4px; color: var(--muted); }
        h3 { font-family: var(--font-display); color: var(--lav-500); margin-bottom: 1.5rem; }
        .form-group { margin-top: 0.75rem; }
    </style>
@endsection

@section('content')
    <div class="container">
        <h3>Chat with Student: {{ $student->name }}</h3>

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

        <form action="{{ route('lecturer.messages.send', ['studentId' => $student->id]) }}" method="POST">
            @csrf
            <div class="form-group">
                <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Send</button>
        </form>
    </div>
@endsection
