@extends('layouts.app')

@section('title', 'Profile - USMGoGrad')

@section('styles')
<style>
    .profile-hero {
        background: transparent;
        color: var(--text);
        padding: 0 0 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .profile-card {
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .profile-card.profile-details {
        border-left: 4px solid rgba(109, 91, 208, 0.5);
        background: linear-gradient(135deg, rgba(237, 233, 252, 0.6) 0%, rgba(255, 255, 255, 1) 70%);
    }

    .profile-card.password-card {
        border-left: 4px solid rgba(224, 125, 69, 0.5);
        background: linear-gradient(135deg, rgba(255, 245, 239, 0.7) 0%, rgba(255, 255, 255, 1) 70%);
    }

    .profile-card-header {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid var(--n-100, #f4f4f5);
    }

    .profile-card-header h5 {
        margin: 0;
        font-family: var(--font-display);
        color: var(--text);
    }

    .profile-card-body {
        padding: 1.1rem 1.25rem;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (max-width: 900px) {
        .profile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 640px) {
        .profile-grid { grid-template-columns: 1fr; }
    }

    .profile-item {
        background: var(--n-50);
        border: 1px solid var(--n-100);
        border-radius: var(--radius-sm);
        padding: 0.8rem 0.9rem;
        box-shadow: 0 1px 3px rgba(17, 17, 17, 0.04);
    }

    .profile-label {
        font-size: 0.75rem;
        color: var(--muted);
    }

    .profile-value {
        font-weight: 600;
        color: var(--text);
        margin-top: 0.15rem;
    }

    .profile-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #4f46e5;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        margin-top: 0.4rem;
    }

    .password-form .form-label {
        font-weight: 600;
        color: var(--text);
        font-size: 0.92rem;
    }

    .password-form .form-control {
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        padding: 0.7rem 0.85rem;
        transition: all 0.25s ease;
    }

    .password-form .form-control:focus {
        border-color: var(--lav-500);
        box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
    }
</style>
@endsection

@section('content')
<div class="profile-hero">
    <h2 style="margin:0; font-family: var(--font-display);"><i class="fas fa-user"></i> Profile</h2>
    <p class="mb-0" style="color: var(--n-700);">View your candidature details and manage your password.</p>
</div>

<div class="profile-card profile-details">
    <div class="profile-card-header">
        <h5>Profile Details</h5>
    </div>
    <div class="profile-card-body">
        <div class="profile-grid">
            <div class="profile-item">
                <div class="profile-label">Name</div>
                <div class="profile-value">{{ $user->name }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Email</div>
                <div class="profile-value">{{ $user->email }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Cluster</div>
                <div class="profile-value">{{ $user->cluster ?: '—' }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Research Title</div>
                <div class="profile-value">{{ $user->research_title ?: '—' }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Research Objective</div>
                <div class="profile-value">{{ $user->research_objective ?: '—' }}</div>
            </div>
            <div class="profile-item">
                <div class="profile-label">Registration Date</div>
                <div class="profile-value">{{ $user->start_date ? $user->start_date->format('d M Y') : '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="profile-card password-card">
    <div class="profile-card-header">
        <h5>Change Password</h5>
        <span class="profile-pill" style="color:#b56739; background:rgba(255, 245, 239, 0.8); border-color:rgba(224, 125, 69, 0.2);"><i class="fas fa-key"></i> Security</span>
    </div>
    <div class="profile-card-body">
        <form method="POST" action="{{ route('password.update') }}" class="password-form">
            @csrf

            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                @error('current_password')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                @error('new_password')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>
@endsection
