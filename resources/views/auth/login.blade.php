<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - USMGoGrad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --lav-100: #ede9fc;
            --lav-200: #dcd6f5;
            --lav-300: #c9c1ee;
            --lav-400: #9a8fe0;
            --lav-500: #6d5bd0;
            --n-50:  #fafafa;
            --n-200: #e4e4e7;
            --n-700: #71717a;
            --n-900: #111111;
            --surface: #ffffff;
            --font-display: 'DM Sans', sans-serif;
            --font-body: 'DM Sans', sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: var(--font-body); background: var(--n-50); }

        .split-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .panel-left {
            width: 50%;
            background: linear-gradient(160deg, #9a8fe0 0%, #6d5bd0 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .panel-left .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            flex: 1;
        }

        .panel-left .logo-wrap img {
            width: 100%;
            max-width: 800px;
            opacity: 0.5;
            object-fit: contain;
        }

        .panel-left .footer-links {
            width: 100%;
            padding: 0 2rem 1.6rem;
            display: flex;
            justify-content: center;
            gap: 1.4rem;
        }

        .panel-left .footer-links a {
            color: rgba(255,255,255,0.65);
            font-size: 0.75rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .panel-left .footer-links a:hover { color: #fff; }

        .panel-right {
            width: 62%;
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 4rem;
        }

        .login-box { width: 100%; max-width: 380px; }

        .login-box h1 {
            font-size: 1.5rem;
            font-weight: 600;
            font-family: var(--font-display);
            color: var(--n-900);
            line-height: 1.25;
            margin-bottom: 1.6rem;
            letter-spacing: -0.01em;
        }

        .field-group { margin-bottom: 1rem; }

        .field-group input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--n-200);
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: var(--font-body);
            color: var(--n-900);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--surface);
        }

        .field-group input::placeholder {
            color: #a1a1aa;
            font-size: 0.8rem;
        }

        .field-group input:focus {
            border-color: var(--lav-500);
            box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
        }
        .field-group input.is-invalid { border-color: #cc4b4b; }

        .error-msg {
            background: #fdecea;
            color: #993333;
            padding: 0.65rem 0.85rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.82rem;
            border-left: 3px solid #cc4b4b;
        }

        .invalid-text { color: #cc4b4b; font-size: 0.75rem; margin-top: 0.3rem; display: block; }

        .btn-row { display: flex; align-items: center; gap: 1.2rem; margin-top: 1.4rem; }

        .btn-login {
            background: var(--lav-500);
            color: #fff;
            border: none;
            padding: 0.6rem 1.6rem;
            font-size: 0.85rem;
            font-family: var(--font-body);
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }

        .btn-login:hover {
            background: #5a4ab8;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(109, 91, 208, 0.28);
        }

        .forgot-link {
            font-size: 0.75rem;
            color: var(--lav-500);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover { color: var(--lav-400); text-decoration: underline; }

        /* Mobile logo banner — hidden on desktop */
        .mobile-logo-banner {
            display: none;
        }

        @media (max-width: 768px) {
            .panel-left { display: none; }

            .panel-right {
                width: 100%;
                padding: 0;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
            }

            /* Show the logo banner on mobile */
            .mobile-logo-banner {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                padding: 2.5rem 1.5rem;
                background: linear-gradient(160deg, #9a8fe0 0%, #6d5bd0 100%);
            }

            .mobile-logo-banner img {
                width: 100%;
                max-width: 220px;
                opacity: 0.85;
                object-fit: contain;
            }

            .login-box {
                width: 100%;
                max-width: 380px;
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="split-wrapper">

    <div class="panel-left">
        <div class="logo-wrap">
            <img src="{{ asset('logo.png') }}" alt="USM Logo" style="width: 500px;">
        </div>
    </div>

    <div class="panel-right">

        <!-- Mobile-only logo, hidden on desktop -->
        <div class="mobile-logo-banner">
            <img src="{{ asset('logo.png') }}" alt="USM Logo">
        </div>

        <div class="login-box">

            <h1>Login to your<br>student dashboard</h1>

            @if ($errors->any())
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field-group">
                    <input id="email" type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="USER ID"
                           autocomplete="email"
                           autofocus
                           required
                           class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @error('email')
                        <span class="invalid-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field-group">
                    <input id="password" type="password"
                           name="password"
                           placeholder="PASSWORD"
                           autocomplete="current-password"
                           required
                           class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
                    @error('password')
                        <span class="invalid-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                           style="width:1rem; height:1rem; accent-color:var(--lav-500); cursor:pointer; flex-shrink:0;">
                    <label for="remember" style="margin:0; font-size:0.78rem; color:var(--n-700); cursor:pointer; user-select:none;">
                        Remember me
                    </label>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-login">LOGIN</button>
                    @if (Route::has('password.request'))
                        <a class="forgot-link" href="{{ route('password.request') }}">
                            Forgot your User ID or Password?
                        </a>
                    @endif
                </div>

            </form>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>