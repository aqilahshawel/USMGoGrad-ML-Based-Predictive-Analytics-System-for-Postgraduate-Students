<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - USMGoGrad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --lav-100: #f3f0ff;
            --lav-200: #e2daff;
            --lav-300: #c9bdff;
            --lav-400: #a99dd6;
            --lav-500: #7c6fb3;
            --n-50:  #faf9f7;
            --n-200: #e3ddd5;
            --n-700: #5a524a;
            --n-900: #2e2924;
            --surface: #ffffff;
            --font-display: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'DM Sans', sans-serif;
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
            background: linear-gradient(160deg, var(--lav-400) 0%, var(--lav-500) 100%);
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
            font-size: 2rem;
            font-weight: 400;
            font-family: var(--font-display);
            color: var(--n-700);
            line-height: 1.25;
            margin-bottom: 2.2rem;
            letter-spacing: 0.01em;
        }

        .field-group { margin-bottom: 1rem; }

        .field-group input {
            width: 100%;
            padding: 0.72rem 0.9rem;
            border: 1.5px solid var(--n-200);
            border-radius: 8px;
            font-size: 0.78rem;
            font-family: var(--font-body);
            letter-spacing: 0.06em;
            color: var(--n-900);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--surface);
        }

        .field-group input::placeholder {
            color: var(--n-200);
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .field-group input:focus {
            border-color: var(--lav-400);
            box-shadow: 0 0 0 3px rgba(169, 157, 214, 0.15);
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
            padding: 0.62rem 2rem;
            font-size: 0.8rem;
            font-family: var(--font-body);
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.06em;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }

        .btn-login:hover {
            background: var(--lav-400);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(124, 111, 179, 0.28);
        }

        .forgot-link {
            font-size: 0.75rem;
            color: var(--lav-500);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover { color: var(--lav-400); text-decoration: underline; }

        @media (max-width: 768px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
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