@extends('layouts.app')

@section('title', 'Register - USMGoGrad')

@section('content')

<style>
    .register-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 150px);
        padding: 2rem 0;
    }

    .register-card {
        background: var(--surface);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(124, 111, 179, 0.12);
        overflow: hidden;
        width: 100%;
        max-width: 500px;
        border: 1px solid var(--lav-200);
    }

    .register-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .register-header h2 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
        font-family: var(--font-display);
    }

    .register-header p {
        margin: 0;
        opacity: 0.92;
    }

    .register-body {
        padding: 2.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 0.9rem;
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

    .form-control {
        border-radius: 8px;
        border: 1.5px solid var(--n-200);
        padding: 0.8rem 1rem;
        font-size: 0.95rem;
        transition: all 0.25s ease;
    }

    .form-control:focus {
        border-color: var(--lav-400);
        box-shadow: 0 0 0 3px rgba(124, 111, 179, 0.12);
        outline: none;
    }

    .btn-register {
        width: 100%;
        padding: 0.85rem;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 8px;
        background: var(--lav-500);
        color: white;
        border: none;
        transition: all 0.25s ease;
        margin-top: 1rem;
    }

    .btn-register:hover {
        background: var(--lav-400);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(124, 111, 179, 0.25);
        color: white;
    }

    .success-message {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #3d9970;
    }

    .success-message i {
        margin-right: 0.5rem;
    }

    .success-message strong {
        display: block;
        margin-bottom: 0.5rem;
    }

    .section-note {
        margin: -0.5rem 0 1rem;
        padding: 0.9rem 1rem;
        border-radius: 10px;
        background: var(--lav-100);
        border: 1px solid var(--lav-200);
        color: var(--lav-500);
        font-size: 0.9rem;
    }

    .icon-logo {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: var(--gold-300);
    }

    @media (max-width: 576px) {
        .register-body {
            padding: 1.5rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .register-header {
            padding: 1.5rem;
        }

        .register-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <div class="icon-logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>{{ __('Register User') }}</h2>
            <p>Create a new account for USMGoGrad</p>
        </div>

        <div class="register-body">
            @if(session('generated_password'))
                <div class="success-message">
                    <strong><i class="fas fa-check-circle"></i> User Registered Successfully!</strong>
                    <p class="mb-0">Temporary password: <code style="background: rgba(255,255,255,0.3); padding: 0.25rem 0.5rem; border-radius: 4px;">{{ session('generated_password') }}</code></p>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Registration Failed!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

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
                        <select id="email_domain" name="email_domain" class="form-control @error('email_domain') is-invalid @enderror" required>
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

                <div id="student-fields">
                    <div class="section-note">
                        Student registration requires the cluster, study type, and registration date so the GoT model has the right baseline context.
                    </div>

                    <div class="form-group">
                    <label for="cluster">
                        <i class="fas fa-layer-group"></i> {{ __('Cluster (Students only)') }}
                    </label>
                    <select id="cluster" name="cluster" class="form-control @error('cluster') is-invalid @enderror">
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

                    <div class="form-group">
                        <label for="study_mode">
                            <i class="fas fa-user-graduate"></i> {{ __('Study Type') }}
                        </label>
                        <select id="study_mode" name="study_mode" class="form-control @error('study_mode') is-invalid @enderror">
                            <option value="">-- Select Study Type --</option>
                            @foreach(($studyModes ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ old('study_mode') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('study_mode')
                            <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                        @enderror
                    </div>

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

                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> {{ __('Register') }}
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailDomainField = document.getElementById('email_domain');
        const studentFields = document.getElementById('student-fields');
        const studentOnlyInputs = studentFields.querySelectorAll('select, input');

        function syncRegistrationFields() {
            const isStudent = emailDomainField.value === '@student.usm.my';
            studentFields.style.display = isStudent ? 'block' : 'none';

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
