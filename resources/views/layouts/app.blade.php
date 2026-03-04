<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'USMGradView 360')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts — Cormorant Garamond + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════
           USM PASTEL COLOUR SYSTEM — Design Tokens
           ═══════════════════════════════════════════════ */
        :root {
            /* Lavender — Primary (Pastel USM Purple) */
            --lav-100: #f3f0ff;
            --lav-200: #e2daff;
            --lav-300: #c9bdff;
            --lav-400: #a99dd6;
            --lav-500: #7c6fb3;

            /* Peach Blossom — Secondary (Pastel USM Orange) */
            --peach-100: #fff5ef;
            --peach-200: #fde5d2;
            --peach-300: #f8c9a8;
            --peach-400: #f2a97a;
            --peach-500: #e07d45;

            /* Champagne Gold — Accent (Softened USM Gold) */
            --gold-100: #fffbf0;
            --gold-200: #fff0c2;
            --gold-300: #ffe08a;
            --gold-400: #e6c355;
            --gold-500: #c9a227;

            /* Warm Mist — Neutrals */
            --n-50:  #faf9f7;
            --n-100: #f2efeb;
            --n-200: #e3ddd5;
            --n-700: #5a524a;
            --n-900: #2e2924;

            /* Semantic shortcuts */
            --surface: #ffffff;
            --bg:      var(--n-50);
            --text:    var(--n-900);
            --muted:   var(--n-700);
            --border:  var(--lav-200);

            /* Typography */
            --font-display: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
            --font-body:    'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* ── Base ──────────────────────────────────── */
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, .page-title, .navbar-brand {
            font-family: var(--font-display);
        }

        /* ── Navigation Bar ────────────────────────── */
        .navbar {
            background: var(--surface) !important;
            box-shadow: 0 1px 0 var(--border);
            padding: 0.85rem 0;
            border-bottom: 2px solid var(--lav-200);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.3px;
            color: var(--lav-500) !important;
        }

        .navbar-brand i {
            color: var(--gold-500);
        }

        .nav-link {
            margin: 0 4px;
            font-weight: 500;
            font-size: 0.92rem;
            color: var(--n-700) !important;
            transition: all 0.25s ease;
            position: relative;
            padding: 0.5rem 0.75rem !important;
            border-radius: 8px;
        }

        .nav-link:hover {
            color: var(--lav-500) !important;
            background: var(--lav-100);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--lav-400);
            transition: all 0.25s ease;
            transform: translateX(-50%);
            border-radius: 1px;
        }

        .nav-link:hover::after {
            width: 60%;
        }

        .navbar-toggler {
            border-color: var(--lav-300);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%237c6fb3' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(46, 41, 36, 0.08);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background: var(--lav-100);
        }

        /* ── Main Content ──────────────────────────── */
        main { flex: 1; }

        .container-main {
            padding: 2.5rem 0;
        }

        /* ── Cards ─────────────────────────────────── */
        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(46, 41, 36, 0.04);
            transition: all 0.3s ease;
            overflow: hidden;
            background: var(--surface);
        }

        .card:hover {
            box-shadow: 0 6px 20px rgba(124, 111, 179, 0.10);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--lav-400) 0%, var(--lav-500) 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1.25rem;
            font-family: var(--font-display);
            font-size: 1.1rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* ── Badges ────────────────────────────────── */
        .badge {
            padding: 0.45rem 0.75rem;
            font-weight: 500;
            border-radius: 20px;
            font-size: 0.8rem;
            letter-spacing: 0.2px;
        }

        .badge.bg-primary, .bg-primary {
            background: var(--lav-500) !important;
        }

        /* ── Buttons ───────────────────────────────── */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.6rem 1.5rem;
            transition: all 0.25s ease;
            border: none;
            font-family: var(--font-body);
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--lav-500);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--lav-400);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(124, 111, 179, 0.25);
        }

        .btn-secondary {
            background: var(--n-200);
            color: var(--n-700);
        }

        .btn-secondary:hover {
            background: var(--n-100);
            color: var(--n-900);
            transform: translateY(-1px);
        }

        .btn-success {
            background: #3d9970;
        }

        .btn-success:hover {
            background: #2d8659;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #cc4b4b;
        }

        .btn-danger:hover {
            background: #b33a3a;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: var(--gold-400);
            color: var(--n-900);
        }

        .btn-warning:hover {
            background: var(--gold-500);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-light {
            background: var(--surface);
            color: var(--lav-500);
            border: 1px solid var(--lav-200);
        }

        .btn-light:hover {
            background: var(--lav-100);
            color: var(--lav-500);
        }

        .btn-outline-primary {
            border: 2px solid var(--lav-400);
            color: var(--lav-500);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--lav-500);
            border-color: var(--lav-500);
            color: #fff;
        }

        /* ── Forms ─────────────────────────────────── */
        .form-label {
            font-weight: 600;
            color: var(--n-900);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1.5px solid var(--n-200);
            padding: 0.75rem 1rem;
            transition: all 0.25s ease;
            font-family: var(--font-body);
            color: var(--text);
            background: var(--surface);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--lav-400);
            box-shadow: 0 0 0 3px rgba(124, 111, 179, 0.12);
        }

        .form-control::placeholder {
            color: var(--n-200);
        }

        /* ── Footer ────────────────────────────────── */
        footer {
            background: var(--n-900);
            color: var(--n-200);
            padding: 2rem 0;
            margin-top: auto;
            text-align: center;
            font-size: 0.85rem;
            letter-spacing: 0.2px;
        }

        footer p {
            opacity: 0.85;
        }

        footer .fa-heart {
            color: var(--peach-400) !important;
        }

        /* ── Page Title ────────────────────────────── */
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--lav-500);
            margin-bottom: 1.5rem;
            font-family: var(--font-display);
        }

        /* ── Alert Styling ─────────────────────────── */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #3d9970;
        }

        .alert-danger {
            background: #fdecea;
            color: #993333;
            border-left: 4px solid #cc4b4b;
        }

        .alert-info {
            background: var(--lav-100);
            color: var(--lav-500);
            border-left: 4px solid var(--lav-400);
        }

        .alert-warning {
            background: var(--gold-100);
            color: #8a6d0b;
            border-left: 4px solid var(--gold-400);
        }

        /* ── User Info Section ─────────────────────── */
        .user-info-card {
            background: var(--lav-100);
            border-left: 4px solid var(--lav-400);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .user-info-card h5 {
            color: var(--lav-500);
            font-weight: 700;
            font-family: var(--font-display);
        }

        .user-info-card p {
            margin: 0.5rem 0;
            color: var(--n-700);
        }

        /* ── Nav Tabs ──────────────────────────────── */
        .nav-tabs {
            border-bottom: 2px solid var(--lav-200);
        }

        .nav-tabs .nav-link {
            color: var(--n-700) !important;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 0.75rem 1.25rem !important;
            font-weight: 600;
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            background: transparent;
            color: var(--lav-500) !important;
            border-bottom-color: var(--lav-300);
        }

        .nav-tabs .nav-link.active {
            color: var(--lav-500) !important;
            background: transparent;
            border-bottom-color: var(--lav-500);
        }

        /* ── Tables ────────────────────────────────── */
        .table {
            border-color: var(--n-200);
        }

        .table thead th {
            background: var(--lav-100);
            color: var(--lav-500);
            font-weight: 600;
            border-bottom: 2px solid var(--lav-200);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Scrollbar ─────────────────────────────── */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: var(--n-100);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--lav-300);
            border-radius: 3px;
        }

        /* ── Responsive ────────────────────────────── */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.75rem;
            }

            .navbar-nav {
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--lav-200);
            }

            .nav-link {
                margin: 2px 0;
            }

            .container-main {
                padding: 1.5rem 0;
            }
        }
    </style>

    @yield('styles')
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-graduation-cap"></i> USMGradView 360
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}"><i class="fas fa-home"></i> Home</a></li>

                        @role('lecturer')
                        <li class="nav-item"><a class="nav-link" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/milestones') }}"><i class="fas fa-flag"></i> Milestones</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/lecturer/messages') }}"><i class="fas fa-envelope"></i> Messenger</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a></li>
                        @endrole

                        @role('student')
                        <li class="nav-item"><a class="nav-link" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/student/messages') }}"><i class="fas fa-envelope"></i> Messenger</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/chatbot') }}"><i class="fas fa-robot"></i> Chatbot</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/feedback') }}"><i class="fas fa-comment"></i> Feedback</a></li>
                        @endrole

                        @role('admin')
                            <li class="nav-item"><a class="nav-link" href="{{ url('/supervisors') }}"><i class="fas fa-users"></i> Supervisors</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ url('/register') }}"><i class="fas fa-user-plus"></i> Register</a></li>
                        @endrole

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="fas fa-key"></i> Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container-main">
        <div class="container">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Oops!</strong> There were some errors with your input:
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; 2025 USMGradView 360. All rights reserved. | Developed with <i class="fas fa-heart"></i> for USM Students</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @yield('scripts')

</body>
</html>
