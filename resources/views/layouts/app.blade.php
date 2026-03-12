<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'USMGoGrad')</title>

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
            -webkit-font-smoothing: antialiased;
            margin: 0;
        }

        h1, h2, h3, .page-title {
            font-family: var(--font-display);
        }

        /* ── Layout Wrapper ──────────────────────── */
        .layout-wrapper {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }

        /* ── Sidebar ───────────────────────────────── */
        .sidebar {
            width: 230px;
            min-width: 230px;
            background: var(--surface);
            border-right: 2px solid var(--lav-200);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .sidebar-brand {
            padding: 1.4rem 1.25rem 1rem;
            border-bottom: 1px solid var(--lav-200);
        }

        .sidebar-brand a {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--lav-500) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-brand i {
            color: var(--gold-500);
        }

        .sidebar-nav {
            flex: 1;
            padding: 0.85rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--n-700);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar-nav-link:hover {
            background: var(--lav-100);
            color: var(--lav-500);
        }

        .sidebar-nav-link.active {
            background: var(--lav-200);
            color: var(--lav-500);
            font-weight: 600;
        }

        .sidebar-nav-link i {
            width: 1.1rem;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-divider {
            border: none;
            border-top: 1px solid var(--lav-200);
            margin: 0.25rem 0.85rem;
        }

        /* ── Sidebar User Dropdown ───────────────── */
        .sidebar-user-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            gap: 0.5rem;
            cursor: pointer;
            border-bottom: 1px solid var(--lav-200);
            user-select: none;
        }

        .sidebar-user-toggle:hover {
            background: var(--lav-100);
        }

        .sidebar-user-toggle-left {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--lav-500);
            font-family: var(--font-body);
            min-width: 0;
        }

        .sidebar-user-toggle-left i.avatar-icon {
            color: var(--lav-400);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .sidebar-user-toggle-left span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-user-toggle .caret {
            color: var(--n-700);
            font-size: 0.7rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .sidebar-user-toggle.open .caret {
            transform: rotate(180deg);
        }

        .sidebar-user-dropdown {
            display: none;
            flex-direction: column;
            background: var(--lav-100);
            border-bottom: 1px solid var(--lav-200);
            padding: 0.35rem 0.6rem;
        }

        .sidebar-user-dropdown.open {
            display: flex;
        }

        /* ── Sidebar Logout (bottom) ────────────── */
        .sidebar-logout {
            padding: 0.6rem 0.75rem;
            border-top: 1px solid var(--lav-200);
            margin-top: auto;
        }

        .sidebar-logout button {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #cc4b4b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
            font-family: var(--font-body);
        }

        .sidebar-logout button:hover {
            background: #fdecea;
        }

        .sidebar-logout button i {
            width: 1.1rem;
            text-align: center;
            flex-shrink: 0;
        }

        /* ── Main Wrapper ──────────────────────────── */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            min-width: 0;
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
            background: linear-gradient(135deg, rgba(243, 240, 255, 0.95) 0%, rgba(255, 245, 239, 0.92) 100%);
            color: var(--n-900);
            border: none;
            border-bottom: 1px solid var(--lav-200);
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

            .sidebar {
                width: 200px;
                min-width: 200px;
            }

            .container-main {
                padding: 1.5rem 0;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                position: fixed;
                left: -230px;
                z-index: 1050;
                transition: left 0.3s ease;
                box-shadow: 4px 0 16px rgba(46,41,36,0.12);
            }

            .sidebar.sidebar-open {
                left: 0;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.3);
                z-index: 1040;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .sidebar-toggle-btn {
                display: flex !important;
            }
        }
    </style>

    @yield('styles')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>

    <div class="layout-wrapper">

        <!-- Sidebar -->
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <a href="{{ url('/') }}">
                    <i class="fas fa-graduation-cap"></i>
                    <span>USMGoGrad</span>
                </a>
            </div>

            @auth
            <!-- User section at top -->
            <div class="sidebar-user-toggle" id="sidebarUserToggle">
                <div class="sidebar-user-toggle-left">
                    <i class="fas fa-user-circle avatar-icon"></i>
                    <span title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</span>
                </div>
                <i class="fas fa-chevron-down caret"></i>
            </div>
            <div class="sidebar-user-dropdown" id="sidebarUserDropdown">
                <a class="sidebar-nav-link" href="{{ route('password.change') }}"><i class="fas fa-key"></i> Change Password</a>
            </div>
            @endauth

            <nav class="sidebar-nav">
                @auth
                    <a class="sidebar-nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}"><i class="fas fa-home"></i> Home</a>

                    @role('lecturer')
                    <a class="sidebar-nav-link {{ request()->is('timeline') ? 'active' : '' }}" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a>
                    <a class="sidebar-nav-link {{ request()->is('milestones') ? 'active' : '' }}" href="{{ url('/milestones') }}"><i class="fas fa-flag"></i> Milestones</a>
                    <a class="sidebar-nav-link {{ request()->is('lecturer/messages') ? 'active' : '' }}" href="{{ url('/lecturer/messages') }}"><i class="fas fa-envelope"></i> Messenger</a>
                    <a class="sidebar-nav-link {{ request()->is('scheduling') ? 'active' : '' }}" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a>
                    @endrole

                    @role('student')
                    <a class="sidebar-nav-link {{ request()->is('timeline') ? 'active' : '' }}" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a>
                    <a class="sidebar-nav-link {{ request()->is('student/messages') ? 'active' : '' }}" href="{{ url('/student/messages') }}"><i class="fas fa-envelope"></i> Messenger</a>
                    <a class="sidebar-nav-link {{ request()->is('scheduling') ? 'active' : '' }}" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a>
                    <a class="sidebar-nav-link {{ request()->is('feedback') ? 'active' : '' }}" href="{{ url('/feedback') }}"><i class="fas fa-comment"></i> Feedback</a>
                    @endrole

                    @role('admin')
                    <a class="sidebar-nav-link {{ request()->is('supervisors') ? 'active' : '' }}" href="{{ url('/supervisors') }}"><i class="fas fa-users"></i> Assignment</a>
                    <a class="sidebar-nav-link {{ request()->is('register') ? 'active' : '' }}" href="{{ url('/register') }}"><i class="fas fa-user-plus"></i> Register</a>
                    @endrole
                @endauth
            </nav>

            @auth
            <div class="sidebar-logout">
                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
            @endauth
        </aside>

        <!-- Main Content + Footer -->
        <div class="main-wrapper">

    <!-- Main Content -->
    <main class="container-main">
        <div class="container-fluid px-4">
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
        <div class="container-fluid px-4">
            <p class="mb-0">&copy; 2025 USMGoGrad. All rights reserved. | Developed with <i class="fas fa-heart"></i> for USM Students</p>
        </div>
    </footer>

        </div><!-- /.main-wrapper -->
    </div><!-- /.layout-wrapper -->

    @include('partials.chatbot-widget')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar user dropdown toggle
        (function () {
            var toggle = document.getElementById('sidebarUserToggle');
            var dropdown = document.getElementById('sidebarUserDropdown');
            if (!toggle || !dropdown) return;
            toggle.addEventListener('click', function () {
                toggle.classList.toggle('open');
                dropdown.classList.toggle('open');
            });
        })();

        // Mobile sidebar toggle
        (function () {
            var sidebar = document.getElementById('appSidebar');
            if (!sidebar) return;
            var overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);

            var toggleBtn = document.createElement('button');
            toggleBtn.className = 'sidebar-toggle-btn btn btn-light';
            toggleBtn.style.cssText = 'display:none;position:fixed;top:12px;left:12px;z-index:1060;width:38px;height:38px;padding:0;border-radius:8px;border:1.5px solid var(--lav-200);';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(toggleBtn);

            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('sidebar-open');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('sidebar-open');
                overlay.classList.remove('active');
            });
        })();
    </script>

    @yield('scripts')

</body>
</html>
