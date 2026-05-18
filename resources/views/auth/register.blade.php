@extends('layouts.app')

@section('title', 'Register - USMGoGrad')

@section('content')

<style>
    .register-header {
        background: transparent;
        color: var(--text);
        padding: 0 0 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .register-header h2 {
        font-weight: 700;
        margin: 0;
        font-size: 1.1rem;
        font-family: var(--font-display);
    }

    .register-form-section {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
        margin-bottom: 2rem;
        border: 1px solid var(--border);
    }

    .register-form-section h4 {
        font-family: var(--font-display);
        color: var(--text);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .register-form {
        display: grid;
        gap: 0.2rem;
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
    .form-select {
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        padding: 0.7rem 0.85rem;
        font-size: 0.9rem;
        transition: all 0.25s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--lav-500);
        box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
        outline: none;
    }

    .register-subsection {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.1rem;
        margin-bottom: 1.5rem;
        border: 1px solid var(--border);
    }

    .btn-register {
        background: var(--lav-500);
        color: white;
        padding: 0.65rem 1.2rem;
        font-weight: 600;
        border-radius: var(--radius-sm);
        border: none;
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .btn-register:hover {
        background: #5a4ab8;
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(109, 91, 208, 0.25);
        color: white;
    }

    .register-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }

    .feedback-message {
        padding: 1rem;
        border-radius: var(--radius-sm);
        margin-bottom: 1.5rem;
        border-left: 4px solid transparent;
    }

    .feedback-message.success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left-color: #3d9970;
    }

    .feedback-message.danger {
        background: #fff5f5;
        color: #b42318;
        border-left-color: #d92d20;
    }

    .feedback-message i {
        margin-right: 0.5rem;
    }

    .feedback-message strong {
        display: block;
        margin-bottom: 0.5rem;
    }

    .section-note {
        margin: 0 0 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: var(--radius-sm);
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        color: #4f46e5;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .register-header h2 {
            font-size: 1.5rem;
        }

        .register-form-section {
            padding: 1.5rem;
        }

        .register-actions {
            justify-content: stretch;
        }

        .btn-register {
            width: 100%;
        }
    }
</style>

<div class="register-page">
    <div class="register-header">
        <h2><i class="fas fa-user-plus"></i> {{ __('Register User') }}</h2>
    </div>

    @if(session('generated_password'))
        <div class="feedback-message success">
            <strong><i class="fas fa-check-circle"></i> User Registered Successfully!</strong>
            <p class="mb-0">Temporary password: <code style="background: rgba(255,255,255,0.3); padding: 0.25rem 0.5rem; border-radius: 4px;">{{ session('generated_password') }}</code></p>
        </div>
    @endif

    @if ($errors->any())
        <div class="feedback-message danger">
            <strong>Registration Failed!</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="register-form">
        @csrf

        <!-- Basic Information Section -->
        <div class="register-form-section">
            <h4 class="mb-3"><i class="fas fa-user"></i> Basic Information</h4>
            
            <div class="form-group">
                <label for="name">
                    <i class="fas fa-user"></i> {{ __('Full Name') }}
                </label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                       name="name" value="{{ old('name') }}" required autocomplete="name" 
                       placeholder="Enter full name" autofocus>
                @error('name')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email_username">
                    <i class="fas fa-envelope"></i> {{ __('Email Address') }}
                </label>
                <div class="form-row">
                    <input id="email_username" type="text" class="form-control @error('email_username') is-invalid @enderror"
                           name="email_username" value="{{ old('email_username') }}" required autocomplete="username"
                           placeholder="Email username">
                    <select id="email_domain" name="email_domain" class="form-select @error('email_domain') is-invalid @enderror" required>
                        @foreach(($emailDomains ?? []) as $domain => $label)
                            <option value="{{ $domain }}" {{ old('email_domain', '@student.usm.my') === $domain ? 'selected' : '' }}>
                                {{ $domain }} ({{ $label }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('email_username')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
                @error('email_domain')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
                @error('email')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Student Information Section -->
        <div id="student-fields" class="register-form-section">
            <h4 class="mb-3"><i class="fas fa-layer-group"></i> Student Information</h4>
            
            <div class="section-note">
                <i class="fas fa-info-circle"></i> Student registration requires the cluster and registration date.
            </div>

            <div class="form-group">
                <label for="cluster">
                    <i class="fas fa-layer-group"></i> {{ __('Cluster') }}
                </label>
                <select id="cluster" name="cluster" class="form-select @error('cluster') is-invalid @enderror">
                    <option value="">-- Select Cluster --</option>
                    @foreach(($clusters ?? []) as $cluster)
                        <option value="{{ $cluster }}" {{ old('cluster') === $cluster ? 'selected' : '' }}>
                            {{ $cluster }}
                        </option>
                    @endforeach
                </select>
                @error('cluster')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>

            <input type="hidden" id="study_mode" name="study_mode" value="full_time">

            <div class="form-group">
                <label for="start_date">
                    <i class="fas fa-calendar-alt"></i> {{ __('Registration Date') }}
                </label>
                <input id="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror"
                       name="start_date" value="{{ old('start_date') }}">
                @error('start_date')
                    <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="register-actions">
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> {{ __('Register') }}
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailDomainField = document.getElementById('email_domain');
        const studentFieldsSection = document.getElementById('student-fields');
        const studentOnlyInputs = studentFieldsSection.querySelectorAll('select, input');

        function syncRegistrationFields() {
            const isStudent = emailDomainField.value === '@student.usm.my';
            studentFieldsSection.style.display = isStudent ? 'block' : 'none';

            studentOnlyInputs.forEach(function (field) {
                if (isStudent) {
                    field.removeAttribute('disabled');
                } else {
                    field.setAttribute('disabled', 'disabled');
                }
            });
        }

        emailDomainField.addEventListener('change', syncRegistrationFields);
        syncRegistrationFields();
    });
</script>

@endsection
