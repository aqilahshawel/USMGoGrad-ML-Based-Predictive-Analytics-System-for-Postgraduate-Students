@extends('layouts.app')

@section('styles')
<style>
    .password-header {
        background: transparent;
        color: var(--text);
        padding: 0 0 1rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.5rem;
    }
    .password-header h2 {
        font-family: var(--font-display);
        font-weight: 600;
        margin: 0;
    }
    .password-form-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        border: 1px solid var(--border);
        box-shadow: 0 2px 6px rgba(17,17,17,0.04);
        max-width: 550px;
    }
    .password-form-card .form-label {
        font-weight: 600;
        color: var(--text);
        font-size: 0.92rem;
    }
    .password-form-card .form-control {
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        padding: 0.7rem 0.85rem;
        transition: all 0.25s ease;
    }
    .password-form-card .form-control:focus {
        border-color: var(--lav-500);
        box-shadow: 0 0 0 3px rgba(109,91,208,0.12);
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="password-header">
        <h2><i class="fas fa-lock"></i> Change Password</h2>
    </div>

    <div class="password-form-card">
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <!-- Current Password -->
        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
            @error('current_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <!-- New Password -->
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
            @error('new_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm New Password -->
        <div class="mb-3">
            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
            <input type="password" name="new_password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
    </div>
</div>
@endsection
