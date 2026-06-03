@extends('layouts.app')

@section('title', 'Provide Feedback - USMGoGrad')

@section('content')

<style>
    .feedback-header {
        background: var(--hue-gradient);
        color: var(--text);
        padding: 1.1rem 1.25rem;
        margin-bottom: 1.5rem;
        border: 1px solid var(--lav-200);
        border-radius: var(--radius);
        box-shadow: 0 8px 18px rgba(124, 111, 179, 0.08);
    }

    .feedback-header::after {
        content: none;
    }

    .feedback-header h1 {
        font-weight: 700;
        margin-bottom: 0.2rem;
        font-size: 1.1rem;
        font-family: var(--font-display);
        color: var(--n-900);
    }

    .feedback-header p {
        margin: 0;
        opacity: 0.85;
        font-size: 0.9rem;
        color: var(--n-700);
    }

    .feedback-form-container {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
        margin: 0 auto;
        border: 1px solid var(--border);
        transition: all 0.25s ease;
    }

    .feedback-form-container:hover {
        box-shadow: 0 6px 16px rgba(17, 17, 17, 0.08);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        font-weight: 600;
        color: var(--text);
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
        font-size: 0.92rem;
    }

    .form-group label i {
        margin-right: 0.5rem;
        color: var(--lav-500);
        font-size: 1rem;
    }

    .form-control,
    .form-textarea {
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        padding: 0.7rem 0.85rem;
        font-size: 0.9rem;
        transition: all 0.25s ease;
        font-family: inherit;
    }

    .form-control:focus,
    .form-textarea:focus {
        border-color: var(--lav-500);
        box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.10);
        outline: none;
    }

    .form-control::placeholder,
    .form-textarea::placeholder {
        color: #a1a1aa;
    }

    .btn-submit {
        width: 100%;
        padding: 0.7rem;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--radius-sm);
        background: var(--lav-500);
        color: white;
        border: none;
        transition: all 0.25s ease;
        margin-top: 1rem;
    }

    .btn-submit:hover {
        background: #5a4ab8;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(109, 91, 208, 0.25);
        color: white;
    }

    .success-alert {
        background: #eef2ff;
        color: #4f46e5;
        border-left: 4px solid #818cf8;
        border-radius: var(--radius-sm);
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .help-text {
        font-size: 0.82rem;
        color: var(--muted);
        margin-top: 0.4rem;
    }

    @media (max-width: 768px) {
        .feedback-form-container {
            padding: 1.5rem;
        }

        .feedback-header {
            padding: 1.25rem 1.5rem;
        }

        .feedback-header h1 {
            font-size: 1.3rem;
        }
    }
</style>

<div class="feedback-header">
    <h1><i class="fas fa-comment-dots"></i> Provide Feedback</h1>
    <p>Share your thoughts and feedback about your academic supervision experience</p>
</div>

<div class="feedback-form-container">
    @if (session('success'))
        <div class="alert success-alert alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-circle"></i> Please fix the errors below:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('feedback.submit') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">
                <i class="fas fa-user"></i> Your Name
            </label>
            <input type="text" class="form-control" id="name" name="name"
                   value="{{ auth()->user()->name ?? 'Unknown' }}" readonly>
            <div class="help-text">This is submitted under your account</div>
        </div>

        <div class="form-group">
            <label for="supervisor_name">
                <i class="fas fa-user-tie"></i> Supervisor's Name
            </label>
            <input type="text" class="form-control" id="supervisor_name" name="supervisor_name"
                   value="{{ auth()->user()->supervisor->name ?? 'Unassigned' }}" readonly>
            <div class="help-text">Assigned supervisor for your account</div>
        </div>

        <div class="form-group">
            <label for="description">
                <i class="fas fa-pencil-alt"></i> Feedback Description
            </label>
            <textarea class="form-control form-textarea @error('description') is-invalid @enderror" 
                      id="description" name="description" rows="6" 
                      placeholder="Please provide your detailed feedback..." required>{{ old('description') }}</textarea>
            <div class="help-text">Share your detailed feedback, suggestions, or concerns</div>
            @error('description')
                <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-submit">
            <i class="fas fa-paper-plane"></i> Submit Feedback
        </button>
    </form>
</div>

@endsection
