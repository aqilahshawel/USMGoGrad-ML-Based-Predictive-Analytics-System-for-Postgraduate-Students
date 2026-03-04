@extends('layouts.app')

@section('title', 'Milestones - USMGradView 360')

@section('content')

<style>
    .milestones-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .milestones-header h1 {
        font-family: var(--font-display);
    }

    .milestone-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1.25rem;
        margin-top: 2rem;
    }

    .milestone-card {
        background: var(--surface);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
        transition: all 0.25s ease;
        display: flex;
        flex-direction: column;
        border-top: 4px solid var(--lav-400);
        border: 1px solid var(--lav-200);
        border-top: 4px solid var(--lav-400);
    }

    .milestone-card:hover {
        box-shadow: 0 6px 20px rgba(124, 111, 179, 0.10);
        transform: translateY(-3px);
    }

    .milestone-card.completed {
        border-top-color: #3d9970;
        background: linear-gradient(to bottom, rgba(61, 153, 112, 0.03), transparent);
    }

    .milestone-card.pending {
        border-top-color: var(--gold-400);
    }

    .milestone-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--n-200);
    }

    .milestone-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--lav-500);
        margin: 0;
        margin-bottom: 0.5rem;
        font-family: var(--font-display);
    }

    .milestone-card.completed .milestone-title {
        color: #2e7d32;
    }

    .milestone-date {
        color: var(--muted);
        font-size: 0.82rem;
        display: flex;
        align-items: center;
    }

    .milestone-date i {
        margin-right: 0.5rem;
        color: var(--lav-400);
    }

    .milestone-body {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .milestone-description {
        color: var(--n-700);
        font-size: 0.9rem;
        margin-bottom: 1rem;
        flex-grow: 1;
    }

    .milestone-footer {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        width: fit-content;
    }

    .status-badge.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.pending {
        background: var(--gold-100);
        color: #8a6d0b;
    }

    .status-badge.approved {
        background: var(--lav-100);
        color: var(--lav-500);
    }

    .status-badge.rejected {
        background: #fdecea;
        color: #993333;
    }

    .milestone-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .milestone-actions .btn {
        padding: 0.5rem;
        font-size: 0.82rem;
        border-radius: 8px;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-item {
        background: var(--surface);
        padding: 1rem;
        border-radius: 14px;
        border: 1px solid var(--lav-200);
        text-align: center;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--lav-500);
        font-family: var(--font-display);
    }

    .stat-label {
        color: var(--muted);
        font-size: 0.82rem;
        margin-top: 0.4rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--n-200);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .milestones-header {
            flex-direction: column;
            text-align: center;
        }

        .milestones-header h1 {
            margin-bottom: 1rem;
        }

        .milestone-grid {
            grid-template-columns: 1fr;
        }

        .milestone-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="milestones-header">
    <div>
        <h1><i class="fas fa-flag-checkered"></i> Milestones</h1>
        <p class="lead mb-0">Track and manage your project milestones</p>
    </div>
    @if(auth()->user()->hasRole('student'))
        <a href="{{ route('milestones.create') }}" class="btn btn-light">
            <i class="fas fa-plus-circle"></i> Propose New Milestone
        </a>
    @endif
</div>

<!-- Statistics -->
@php
    $totalMilestones = count($milestones);
    $completedCount = $milestones->where('status', 'completed')->count();
    $pendingCount = $milestones->where('status', 'pending')->count();
@endphp

<div class="stats-row">
    <div class="stat-item">
        <div class="stat-number">{{ $totalMilestones }}</div>
        <div class="stat-label">Total Milestones</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">{{ $completedCount }}</div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">{{ $pendingCount }}</div>
        <div class="stat-label">Pending</div>
    </div>
</div>

<!-- Success Alert -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Milestones Grid -->
@if($milestones->count() > 0)
    <div class="milestone-grid">
        @foreach($milestones as $milestone)
            <div class="milestone-card {{ strtolower($milestone->status) }}">
                <div class="milestone-header">
                    <h5 class="milestone-title">{{ $milestone->title }}</h5>
                    <div class="milestone-date">
                        <i class="fas fa-calendar-alt"></i>
                        Due: {{ \Carbon\Carbon::parse($milestone->due_date)->format('M d, Y') }}
                    </div>
                </div>
                <div class="milestone-body">
                    <div class="milestone-description">
                        {{ Str::limit($milestone->description, 150) }}
                    </div>
                    <div class="milestone-footer">
                        <div>
                            <span class="status-badge {{ strtolower($milestone->status) }}">
                                @if(strtolower($milestone->status) == 'completed')
                                    <i class="fas fa-check-circle"></i> Completed
                                @elseif(strtolower($milestone->status) == 'pending')
                                    <i class="fas fa-hourglass-half"></i> Pending
                                @elseif(strtolower($milestone->status) == 'approved')
                                    <i class="fas fa-thumbs-up"></i> Approved
                                @elseif(strtolower($milestone->status) == 'rejected')
                                    <i class="fas fa-thumbs-down"></i> Rejected
                                @endif
                            </span>
                        </div>
                        @if(auth()->user()->hasRole('student'))
                            <div class="milestone-actions">
                                <a href="{{ route('milestones.edit', $milestone->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="card border-0 bg-light">
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h4>No Milestones Yet</h4>
            <p class="text-muted">
                @if(auth()->user()->hasRole('student'))
                    You haven't proposed any milestones yet. <a href="{{ route('milestones.create') }}">Create one now</a> to get started.
                @else
                    There are no milestones to display.
                @endif
            </p>
        </div>
    </div>
@endif

@endsection
