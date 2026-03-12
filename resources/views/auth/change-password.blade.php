@extends('layouts.app')

@section('styles')
<style>
    .password-header {
        background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
        color: var(--n-900);
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
    }
    .password-header h2 {
        font-family: var(--font-display);
        font-weight: 700;
        margin: 0;
    }
    .password-form-card {
        background: var(--surface);
        border-radius: 14px;
        padding: 2.5rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        max-width: 550px;
    }
    .password-form-card .form-label {
        font-weight: 600;
        color: var(--n-900);
        font-size: 0.92rem;
    }
    .password-form-card .form-control {
        border-radius: 8px;
        border: 1.5px solid var(--n-200);
        padding: 0.8rem 1rem;
        transition: all 0.25s ease;
    }
    .password-form-card .form-control:focus {
        border-color: var(--lav-400);
        box-shadow: 0 0 0 3px rgba(124,111,179,0.12);
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
