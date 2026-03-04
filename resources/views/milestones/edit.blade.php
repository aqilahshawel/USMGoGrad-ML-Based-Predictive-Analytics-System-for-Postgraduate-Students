@extends('layouts.app')

@section('title', 'Edit Milestone')

@section('styles')
<style>
    .edit-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        border-radius: 14px;
        margin-bottom: 2rem;
    }
    .edit-header h2 {
        font-weight: 700;
        margin: 0;
        font-family: var(--font-display);
        font-size: 2rem;
    }
    .edit-form-card {
        background: var(--surface);
        border-radius: 14px;
        padding: 2.5rem;
        border: 1px solid var(--lav-200);
        box-shadow: 0 2px 8px rgba(46,41,36,0.04);
        max-width: 700px;
    }
    .edit-form-card label {
        font-weight: 600;
        color: var(--n-900);
        font-size: 0.92rem;
    }
    .edit-form-card .form-control,
    .edit-form-card .form-select {
        border-radius: 8px;
        border: 1.5px solid var(--n-200);
        padding: 0.8rem 1rem;
        transition: all 0.25s ease;
    }
    .edit-form-card .form-control:focus,
    .edit-form-card .form-select:focus {
        border-color: var(--lav-400);
        box-shadow: 0 0 0 3px rgba(124,111,179,0.12);
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="edit-header">
        <h2><i class="fas fa-edit"></i> Edit Milestone</h2>
    </div>

    <div class="edit-form-card">
    <form action="{{ route('milestones.update', $milestone->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="{{ $milestone->title }}" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control">{{ $milestone->description }}</textarea>
        </div>

        <div class="mb-3">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ $milestone->due_date }}">
        </div>

        @if(auth()->user()->hasRole('lecturer'))
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="pending" {{ $milestone->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $milestone->status == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="finalized" {{ $milestone->status == 'finalized' ? 'selected' : '' }}>Finalized</option>
                </select>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
    </div>
</div>
@endsection
