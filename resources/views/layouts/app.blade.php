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
    <!-- Google Fonts — DM Sans + DM Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════
           Timeline UI System — Design Tokens
           ═══════════════════════════════════════════════ */
        :root {
            --radius: 10px;
            --radius-sm: 6px;
            /* Primary + Secondary */
            --lav-100: #ede9fc;
            --lav-200: #dcd6f5;
            --lav-300: #c9c1ee;
            --lav-400: #9a8fe0;
            --lav-500: #6d5bd0;

            --peach-100: #fff5ef;
            --peach-200: #fde5d2;
            --peach-300: #f8c9a8;
            --peach-400: #f2a97a;
            --peach-500: #e07d45;

            --gold-100: #fffbf0;
            --gold-200: #fff0c2;
            --gold-300: #ffe08a;
            --gold-400: #e6c355;
            --gold-500: #c9a227;

            /* Neutrals */
            --n-50:  #fafafa;
            --n-100: #f4f4f5;
            --n-200: #e4e4e7;
            --n-700: #71717a;
            --n-900: #111111;

            /* Semantic shortcuts */
            --surface: #ffffff;
            --bg:      var(--n-50);
            --text:    var(--n-900);
            --muted:   var(--n-700);
            --border:  var(--n-200);
            --hue-gradient: linear-gradient(135deg, rgba(243, 240, 255, 0.96) 0%, rgba(255, 245, 239, 0.94) 100%);
            --hue-gradient-strong: linear-gradient(135deg, rgba(243, 240, 255, 0.96) 0%, rgba(255, 245, 239, 0.94) 100%);

            /* Typography */
            --font-display: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-body:    'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-mono:    'DM Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
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
            letter-spacing: -0.01em;
        }

        /* ── Shared UI Kit ───────────────────────────── */
        .ui-page {
            font-family: var(--font-body);
            color: var(--text);
        }

        .ui-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .ui-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ui-header-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--hue-gradient);
            color: var(--lav-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .ui-header-text {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }

        .ui-header-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .ui-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 2px 6px rgba(17, 17, 17, 0.04);
            overflow: hidden;
        }

        .ui-card-head {
            padding: 12px 16px;
            border-bottom: 1px solid var(--n-100);
            background: var(--hue-gradient);
        }

        .ui-card-title {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .ui-card-sub {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--muted);
        }

        .ui-card-body {
            padding: 16px;
        }

        .ui-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all .15s ease;
        }

        .ui-btn-primary {
            background: var(--lav-500);
            color: #ffffff;
        }

        .ui-btn-primary:hover {
            background: #5a4ab8;
        }

        .ui-btn-outline {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .ui-btn-outline:hover {
            background: var(--n-50);
        }

        .ui-input {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 7px 10px;
            font-size: 13px;
            font-family: var(--font-body);
            background: #ffffff;
            color: var(--text);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .ui-input:focus {
            border-color: var(--lav-500);
            box-shadow: 0 0 0 3px rgba(109, 91, 208, 0.12);
        }

        .ui-alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            border: 1px solid;
            margin-bottom: 12px;
        }

        .ui-alert.success { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .ui-alert.warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }

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
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: nowrap;
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
            min-width: 0;
        }

        .sidebar-brand i {
            color: var(--gold-500);
        }

        .sidebar-collapse-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1.5px solid var(--lav-200);
            background: var(--surface);
            color: var(--lav-500);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
            font-weight: 700;
            flex-shrink: 0;
        }

        .sidebar-collapse-btn:hover {
            background: var(--lav-100);
        }

        /* ── Desktop Sidebar Collapse ────────────── */
        .sidebar.sidebar-collapsed {
            width: 72px;
            min-width: 72px;
        }

        .sidebar.sidebar-collapsed .sidebar-brand {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.1rem 0.75rem 0.9rem;
            gap: 0.6rem;
        }

        .sidebar.sidebar-collapsed .sidebar-brand a {
            justify-content: center;
        }

        .sidebar.sidebar-collapsed .sidebar-brand span {
            display: none;
        }

        .sidebar.sidebar-collapsed .sidebar-user-toggle-left span {
            display: none;
        }

        .sidebar.sidebar-collapsed .sidebar-user-toggle .caret {
            display: none;
        }

        .sidebar.sidebar-collapsed .sidebar-nav-link {
            justify-content: center;
            font-size: 0;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
        }

        .sidebar.sidebar-collapsed .sidebar-nav-link i {
            font-size: 1rem;
        }

        .sidebar.sidebar-collapsed .sidebar-logout button {
            justify-content: center;
            font-size: 0;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
        }

        .sidebar.sidebar-collapsed .sidebar-logout button i {
            font-size: 1rem;
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

        .notification-dock {
            position: fixed;
            right: 1.25rem;
            top: 1rem;
            z-index: 1090;
            margin: 0;
            pointer-events: none;
        }

        .notification-popover {
            position: relative;
            pointer-events: auto;
        }

        .notification-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid var(--lav-200);
            background: var(--surface);
            color: var(--n-900);
            font-weight: 600;
            padding: 0.5rem 0.85rem;
            box-shadow: 0 8px 18px rgba(46, 41, 36, 0.16);
            border-radius: 999px;
        }

        .notification-toggle:hover,
        .notification-toggle:focus {
            background: var(--lav-100);
            color: var(--lav-500);
            border-color: var(--lav-300);
        }

        .notification-toggle .caret {
            font-size: 0.78rem;
            color: var(--muted);
        }

        .notification-count-pill {
            background: var(--peach-500);
            color: #fff;
            border-radius: 999px;
            font-size: 0.72rem;
            line-height: 1;
            padding: 0.25rem 0.45rem;
            min-width: 1.35rem;
            text-align: center;
        }

        .notification-menu {
            width: min(420px, 92vw);
            padding: 0;
            border-radius: 14px;
            border: 1px solid var(--lav-200);
            box-shadow: 0 14px 28px rgba(46, 41, 36, 0.12);
            overflow: hidden;
            position: absolute;
            top: calc(100% + 0.45rem);
            right: 0;
            bottom: auto;
            z-index: 1080;
            display: none;
        }

        .notification-menu.is-open {
            display: block;
        }

        .notification-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0.95rem;
            background: var(--hue-gradient);
            border-bottom: 1px solid var(--lav-200);
            color: var(--n-900);
            font-size: 0.85rem;
        }

        .notification-menu-body {
            max-height: 340px;
            overflow-y: auto;
            background: var(--surface);
        }

        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0.9rem;
            border-bottom: 1px solid var(--n-100);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item-copy {
            flex: 1;
            min-width: 0;
        }

        .notification-item-copy p {
            margin: 0;
            font-size: 0.86rem;
            color: var(--n-900);
            line-height: 1.35;
        }

        .notification-item-copy small {
            display: block;
            margin-top: 0.3rem;
            color: var(--muted);
            font-size: 0.74rem;
        }

        .notification-item-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .notification-item-actions .btn {
            padding: 0.35rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .notification-empty {
            text-align: center;
            padding: 1.2rem 0.9rem;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .notification-empty i {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--n-200);
            font-size: 1.1rem;
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
            .notification-dock {
                right: 0.75rem;
                top: 0.75rem;
            }

            .notification-menu {
                width: min(94vw, 360px);
            }

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
                align-items: center;
                justify-content: center;
            }

            .sidebar-toggle-btn i {
                line-height: 1;
            }

            .sidebar-collapse-btn {
                display: none;
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
                <button
                    type="button"
                    class="sidebar-collapse-btn"
                    id="sidebarCollapseBtn"
                    aria-label="Collapse sidebar"
                    aria-expanded="true"
                >&lt;</button>
            </div>

            @auth
            <div class="sidebar-user-dropdown" style="display: block;">
                <a class="sidebar-nav-link" href="{{ route('profile.show') }}">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
            @endauth

            <nav class="sidebar-nav">
                @auth
                    <a class="sidebar-nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}"><i class="fas fa-home"></i> Home</a>

                    @role('lecturer')
                    <a class="sidebar-nav-link {{ request()->is('timeline') ? 'active' : '' }}" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a>
                    <a class="sidebar-nav-link {{ request()->is('lecturer/messages') ? 'active' : '' }}" href="{{ url('/lecturer/messages') }}"><i class="fas fa-envelope"></i> Messenger</a>
                    <a class="sidebar-nav-link {{ request()->is('scheduling') ? 'active' : '' }}" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a>
                    @endrole

                    @role('student')
                    <a class="sidebar-nav-link {{ request()->is('timeline') ? 'active' : '' }}" href="{{ url('/timeline') }}"><i class="fas fa-timeline"></i> Timeline</a>
                    <a class="sidebar-nav-link {{ request()->is('student/messages') ? 'active' : '' }}" href="{{ url('/student/messages') }}"><i class="fas fa-envelope"></i> Messenger</a>
                    <a class="sidebar-nav-link {{ request()->is('scheduling') ? 'active' : '' }}" href="{{ url('/scheduling') }}"><i class="fas fa-calendar"></i> Schedule</a>
                    @php
                        $feedbackAvailableOn = optional(Auth::user()->start_date)->copy()->addMonthsNoOverflow(2);
                        $canAccessFeedback = $feedbackAvailableOn && now()->gte($feedbackAvailableOn->startOfDay());
                    @endphp
                    @if ($canAccessFeedback)
                        <a class="sidebar-nav-link {{ request()->is('feedback') ? 'active' : '' }}" href="{{ url('/feedback') }}"><i class="fas fa-comment"></i> Feedback</a>
                    @endif
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
                    <button type="submit" aria-label="Logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </div>
            @endauth
        </aside>

        <!-- Main Content + Footer -->
        <div class="main-wrapper">

    <!-- Main Content -->
    <main class="container-main">
        <div class="container-fluid px-4">
            @auth
                @php
                    $globalNotificationItems = $globalNotificationItems ?? collect();
                    $globalUnreadNotificationCount = $globalUnreadNotificationCount ?? 0;
                @endphp
                <div class="notification-dock">
                    <div class="notification-popover">
                        <button
                            class="btn notification-toggle"
                            type="button"
                            id="globalNotificationToggle"
                            aria-expanded="false"
                        >
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <span class="notification-count-pill">{{ $globalUnreadNotificationCount > 99 ? '99+' : $globalUnreadNotificationCount }}</span>
                            <i class="fas fa-chevron-down caret"></i>
                        </button>

                        <div class="notification-menu" id="globalNotificationPanel" aria-labelledby="globalNotificationToggle">
                            <div class="notification-menu-header">
                                <strong>Notifications</strong>
                                <span>{{ $globalUnreadNotificationCount }}</span>
                            </div>
                            <div class="notification-menu-body">
                                @forelse ($globalNotificationItems as $item)
                                    <div class="notification-item">
                                        <div class="notification-item-copy">
                                            <p>{{ $item['message'] ?? 'You have a new notification.' }}</p>
                                            <small><i class="fas fa-clock"></i> {{ optional($item['created_at'] ?? null)->diffForHumans() ?? 'Just now' }}</small>
                                        </div>
                                        <div class="notification-item-actions">
                                            @if (($item['action_type'] ?? 'link') === 'post')
                                                <form method="POST" action="{{ $item['action_url'] ?? '#' }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary">{{ $item['action_label'] ?? 'Open' }}</button>
                                                </form>
                                            @elseif (!empty($item['action_url']))
                                                <a href="{{ $item['action_url'] }}" class="btn btn-outline-primary">{{ $item['action_label'] ?? 'Open' }}</a>
                                            @endif

                                            @if (!empty($item['dismiss_url']))
                                                <form method="POST" action="{{ $item['dismiss_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light" aria-label="Dismiss notification">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="notification-empty">
                                        <i class="fas fa-bell-slash"></i>
                                        No new notification.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endauth

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

        </div><!-- /.main-wrapper -->
    </div><!-- /.layout-wrapper -->

    @include('partials.chatbot-widget')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js for Dashboard Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        // Desktop sidebar collapse toggle
        (function () {
            var sidebar = document.getElementById('appSidebar');
            var btn = document.getElementById('sidebarCollapseBtn');
            if (!sidebar || !btn) return;

            function syncButton() {
                var isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                btn.innerHTML = isCollapsed ? '&gt;' : '&lt;';
                btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                btn.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
            }

            btn.addEventListener('click', function () {
                sidebar.classList.toggle('sidebar-collapsed');
                syncButton();
            });

            syncButton();
        })();

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
            toggleBtn.style.cssText = 'display:none;position:fixed;top:12px;left:12px;z-index:1060;width:38px;height:38px;padding:0;border-radius:8px;border:1.5px solid var(--lav-200);align-items:center;justify-content:center;line-height:1;';
            toggleBtn.innerHTML = '<i class="fas fa-bars" style="line-height:1;"></i>';
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

        // Global notification popover toggle
        (function () {
            var toggle = document.getElementById('globalNotificationToggle');
            var panel = document.getElementById('globalNotificationPanel');
            if (!toggle || !panel) return;

            function closePanel() {
                panel.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var isOpen = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            document.addEventListener('click', function () {
                closePanel();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closePanel();
                }
            });
        })();
    </script>

    @yield('scripts')

</body>
</html>
