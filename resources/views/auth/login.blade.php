@extends('layouts.app')

@section('title', 'Login - USMGradView 360')

@section('content')

<style>
    body {
        background: linear-gradient(135deg, var(--lav-100) 0%, var(--lav-200) 100%) !important;
        min-height: 100vh;
    }

    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 150px);
    }

    .login-card {
        background: var(--surface);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(124, 111, 179, 0.12);
        overflow: hidden;
        width: 100%;
        max-width: 450px;
        border: 1px solid var(--lav-200);
    }

    .login-header {
        background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .login-header h2 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
        font-family: var(--font-display);
    }

    .login-header p {
        margin: 0;
        opacity: 0.92;
    }

    .login-body {
        padding: 2.5rem;
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

    .form-control::placeholder {
        color: var(--n-200);
    }

    .form-check {
        display: flex;
        align-items: center;
    }

    .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        margin-right: 0.5rem;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: var(--lav-500);
        border-color: var(--lav-500);
    }

    .form-check-label {
        margin: 0;
        cursor: pointer;
        color: var(--n-700);
        font-weight: 500;
    }

    .btn-login {
        width: 100%;
        padding: 0.85rem;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 8px;
        background: var(--lav-500);
        color: white;
        border: none;
        transition: all 0.25s ease;
        margin-bottom: 1rem;
    }

    .btn-login:hover {
        background: var(--lav-400);
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(124, 111, 179, 0.25);
        color: white;
    }

    .login-footer {
        text-align: center;
        padding: 1.5rem 2.5rem 0;
        border-top: 1px solid var(--n-200);
        margin-top: 1.5rem;
    }

    .forgot-link {
        color: var(--lav-500);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    .forgot-link:hover {
        color: var(--lav-400);
        text-decoration: underline;
    }

    .error-message {
        background: #fdecea;
        color: #993333;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        border-left: 4px solid #cc4b4b;
    }

    .icon-logo {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: var(--gold-300);
    }

    .password-wrapper {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--n-200);
    }

    .password-toggle:hover {
        color: var(--lav-500);
    }

    @media (max-width: 576px) {
        .login-body {
            padding: 1.5rem;
        }

        .login-header {
            padding: 1.5rem;
        }

        .login-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="icon-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>{{ __('Welcome Back') }}</h2>
            <p>Sign in to your USMGradView account</p>
        </div>

        <div class="login-body">
            @if ($errors->any())
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Login Failed!</strong><br>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> {{ __('Email Address') }}
                    </label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                           name="email" value="{{ old('email') }}" required autocomplete="email" 
                           placeholder="Enter your email address" autofocus>
                    @error('email')
                        <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> {{ __('Password') }}
                    </label>
                    <div class="password-wrapper">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                               name="password" required autocomplete="current-password" 
                               placeholder="Enter your password">
                        <span class="password-toggle" id="togglePassword" aria-label="Show password" role="button">
                            <i class="far fa-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block mt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                               {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            {{ __('Remember Me') }}
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> {{ __('Sign In') }}
                </button>
            </form>

            <div class="login-footer">
                @if (Route::has('password.request'))
                    <a class="forgot-link" href="{{ route('password.request') }}">
                        <i class="fas fa-question-circle"></i> {{ __('Forgot Your Password?') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var passwordInput = document.getElementById('password');
        var toggleButton = document.getElementById('togglePassword');
        var toggleIcon = document.getElementById('togglePasswordIcon');

        if (passwordInput && toggleButton && toggleIcon) {
            toggleButton.addEventListener('click', function () {
                var isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.classList.toggle('fa-eye');
                toggleIcon.classList.toggle('fa-eye-slash');
                toggleButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        }
    });
</script>
@endsection
