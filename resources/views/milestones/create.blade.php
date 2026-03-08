@extends('layouts.app')

@section('title', 'Propose Milestone - USMGoGrad')

@section('content')

<style>
    .milestone-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
    }

    .milestone-header h2 {
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-family: var(--font-display);
    }

    .milestone-header p {
        margin: 0;
        opacity: 0.92;
    }

    .form-container {
        background: var(--surface);
        border-radius: 14px;
        padding: 2.5rem;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        max-width: 700px;
        border: 1px solid var(--lav-200);
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

    .form-group label .required {
        color: #cc4b4b;
        margin-left: 0.25rem;
    }

    .form-control,
    .form-textarea {
        border-radius: 8px;
        border: 1.5px solid var(--n-200);
        padding: 0.8rem 1rem;
        font-size: 0.95rem;
        transition: all 0.25s ease;
        font-family: inherit;
    }

    .form-control:focus,
    .form-textarea:focus {
        border-color: var(--lav-400);
        box-shadow: 0 0 0 3px rgba(124, 111, 179, 0.12);
        outline: none;
    }

    .form-control::placeholder,
    .form-textarea::placeholder {
        color: var(--n-200);
    }

    .form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-submit,
    .btn-cancel {
        padding: 0.85rem;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 8px;
        transition: all 0.25s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-submit {
        background: var(--lav-500);
        color: white;
        border: none;
    }

    .btn-submit:hover {
        background: var(--lav-400);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(124, 111, 179, 0.25);
        color: white;
    }

    .btn-cancel {
        background: var(--n-100);
        color: var(--n-700);
        border: 1.5px solid var(--n-200);
    }

    .btn-cancel:hover {
        background: var(--n-200);
        text-decoration: none;
    }

    .help-text {
        font-size: 0.82rem;
        color: var(--muted);
        margin-top: 0.4rem;
    }

    @media (max-width: 768px) {
        .form-container {
            padding: 1.5rem;
        }

        .milestone-header {
            padding: 1.5rem;
        }

        .milestone-header h2 {
            font-size: 1.5rem;
        }

        .form-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="milestone-header">
    <h2><i class="fas fa-flag"></i> Propose New Milestone</h2>
    <p>Create a new milestone for your academic project</p>
</div>

<div class="form-container">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
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

    <form action="{{ route('milestones.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="title">
                <i class="fas fa-bookmark"></i> Milestone Title
                <span class="required">*</span>
            </label>
            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                   value="{{ old('title') }}" placeholder="Enter milestone title" required>
            <div class="help-text">Give your milestone a clear, descriptive title</div>
            @error('title')
                <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">
                <i class="fas fa-pencil-alt"></i> Description
                <span class="required">*</span>
            </label>
            <textarea name="description" id="description" class="form-control form-textarea @error('description') is-invalid @enderror" 
                      rows="5" placeholder="Describe what this milestone entails..." required>{{ old('description') }}</textarea>
            <div class="help-text">Provide a detailed description of your milestone and its objectives</div>
            @error('description')
                <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="due_date">
                <i class="fas fa-calendar-alt"></i> Due Date
                <span class="required">*</span>
            </label>
            <input type="date" name="due_date" id="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                   value="{{ old('due_date') }}" required>
            <div class="help-text">Set the target date for completing this milestone</div>
            @error('due_date')
                <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Milestone
            </button>
            <a href="{{ route('milestones.index') }}" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

@endsection
