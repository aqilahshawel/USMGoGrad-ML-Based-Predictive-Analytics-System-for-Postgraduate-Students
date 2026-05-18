@extends('layouts.app')

@section('styles')
    <style>
        .chat-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-hero {
            background: var(--hue-gradient);
            color: var(--text);
            padding: 1.1rem 1.25rem;
            border: 1px solid var(--lav-200);
            border-radius: var(--radius);
            box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
        }

        .chat-hero h3 {
            font-family: var(--font-display);
            color: var(--n-900);
            margin: 0;
            font-weight: 700;
            font-size: 1.05rem;
        }

        .chat-hero p {
            margin: 0.25rem 0 0;
            color: var(--n-700);
            font-size: 0.86rem;
        }

        .chat-actions {
            margin-bottom: 0;
        }

        .chat-actions .btn {
            border-radius: 999px;
        }

        .chat-shell {
            background: var(--surface);
            border: 1px solid var(--lav-200);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(46, 41, 36, 0.05);
        }

        .chat-shell-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: var(--hue-gradient);
            border-bottom: 1px solid var(--lav-200);
        }

        .chat-shell-head .person-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--lav-200);
            color: var(--lav-500);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .chat-box {
            padding: 1rem;
            background: var(--n-50);
            height: 460px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .message {
            max-width: 82%;
            padding: 0.85rem 1rem;
            border-radius: 16px;
            font-size: 0.9rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .message strong {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.78rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--lav-500);
        }

        .message p {
            margin: 0;
            white-space: pre-wrap;
            color: var(--text);
        }

        .sent {
            align-self: flex-end;
            background: var(--hue-gradient);
            text-align: left;
            border: 1px solid var(--lav-200);
        }

        .received {
            align-self: flex-start;
            background: var(--surface);
            text-align: left;
            border: 1px solid var(--border);
            border-left: 4px solid var(--peach-400);
        }

        .chat-box .text-muted {
            font-size: 0.76rem;
            display: block;
            margin-top: 0.45rem;
            color: var(--muted);
        }

        .chat-input-wrap {
            padding: 1rem 1.25rem;
            background: var(--surface);
            border-top: 1px solid var(--n-100);
        }

        .chat-input-wrap .form-control {
            border-radius: 12px 0 0 12px;
            border: 1px solid var(--border);
            padding: 0.7rem 0.9rem;
        }

        .chat-input-wrap .form-control:focus {
            border-color: var(--lav-400);
            box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
        }

        .chat-input-wrap .btn-primary {
            border-radius: 0 12px 12px 0;
            background: var(--hue-gradient);
            color: var(--lav-500);
            border-color: var(--lav-200);
        }

        .chat-input-wrap .btn-primary:hover {
            box-shadow: 0 6px 14px rgba(124, 111, 179, 0.12);
        }

        .chat-empty {
            padding: 1rem;
            text-align: center;
            color: var(--muted);
        }
    </style>
@endsection

@section('content')
    <div class="chat-page">
        <div class="chat-hero">
            <div class="chat-actions mb-2">
                <a
                    href="{{ route('lecturer.messages.index') }}"
                    class="btn btn-outline-secondary btn-sm"
                    onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }"
                >
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <h3><i class="fas fa-comments me-2"></i> Chat with {{ $student->name }}</h3>
            <p>Send guidance, check-ins, and updates in one conversation thread.</p>
        </div>

        <div class="chat-shell">
            <div class="chat-shell-head">
                <div class="person-chip"><i class="fas fa-user-graduate"></i> {{ $student->name }}</div>
                <div class="person-chip"><i class="fas fa-clock"></i> Live conversation</div>
            </div>

            <div class="chat-box">
                @forelse ($messages as $message)
                    <div class="message @if ($message->sender_id == Auth::id()) sent @else received @endif">
                        <strong>{{ $message->sender->name ?? 'Unknown' }}</strong>
                        <p>{{ $message->message }}</p>
                        <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <div class="chat-empty">
                        <i class="fas fa-message"></i>
                        <p class="mb-0">No messages yet. Start the conversation below.</p>
                    </div>
                @endforelse
            </div>

            <form action="{{ route('lecturer.messages.send', ['studentId' => $student->id]) }}" method="POST">
                @csrf
                <div class="chat-input-wrap">
                    <div class="input-group">
                        <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
