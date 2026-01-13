<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme ?? 'light') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'LogicPanel') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- LogicPanel cPanel-style CSS -->
    <style>
        :root {
            /* Node.js Inspired Professional Green Palette */
            --primary: #3C873A;
            /* Node.js Green */
            --primary-dark: #2D6A2E;
            /* Darker green */
            --primary-light: #68A063;
            /* Lighter green */
            --primary-hover: #4E9C4B;
            --accent: #215732;
            /* Deep forest green */

            /* Light Theme */
            --bg-body: #f5f6f8;
            --bg-content: #ffffff;
            --bg-sidebar: #2b3e50;
            --bg-header: #ffffff;
            --bg-card: #ffffff;
            --bg-input: #f8f9fa;

            --text-primary: #333333;
            --text-secondary: #666666;
            --text-muted: #999999;
            --text-sidebar: #d1d8dd;
            --text-sidebar-hover: #ffffff;

            --border-color: #e3e6e8;
            --border-radius: 4px;

            /* Status Colors */
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #f44336;
            --info: #2196F3;

            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.12);

            /* Dimensions */
            --sidebar-width: 220px;
            --header-height: 55px;
        }

        /* Dark Theme Override */
        [data-theme="dark"] {
            --bg-body: #1a1d21;
            --bg-content: #252830;
            --bg-sidebar: #1e2127;
            --bg-header: #252830;
            --bg-card: #2d313a;
            --bg-input: #1e2127;

            --text-primary: #e4e6eb;
            --text-secondary: #b0b3b8;
            --text-muted: #8a8d91;

            --border-color: #3e4249;
        }

        /* Reset & Base */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: var(--text-primary);
            background: var(--bg-body);
            min-height: 100vh;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - cPanel Style */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand-icon svg {
            color: white;
            width: 20px;
            height: 20px;
        }

        .sidebar-brand-text {
            color: #fff;
            font-size: 16px;
            font-weight: 600;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .sidebar-nav-section {
            padding: 12px 20px 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--text-sidebar);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-sidebar-hover);
            text-decoration: none;
        }

        .sidebar-nav-link.active {
            background: rgba(60, 135, 58, 0.2);
            color: #fff;
            border-left-color: var(--primary);
        }

        .sidebar-nav-link svg {
            width: 18px;
            height: 18px;
            opacity: 0.8;
        }

        .sidebar-user {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-user-avatar {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .sidebar-user-info {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user-name {
            color: white;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            color: var(--text-muted);
            font-size: 11px;
            text-transform: capitalize;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header - cPanel Style */
        .header {
            height: var(--header-height);
            background: var(--bg-header);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-btn {
            width: 36px;
            height: 36px;
            background: none;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.15s ease;
        }

        .header-btn:hover {
            background: var(--bg-input);
            color: var(--text-primary);
        }

        .header-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 20px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Cards - cPanel Box Style */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg {
            width: 16px;
            height: 16px;
            color: var(--primary);
        }

        .card-body {
            padding: 15px;
        }

        /* Stats Cards Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--shadow-sm);
        }

        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .stat-card-icon.green {
            background: rgba(60, 135, 58, 0.15);
            color: var(--primary);
        }

        .stat-card-icon.blue {
            background: rgba(33, 150, 243, 0.15);
            color: var(--info);
        }

        .stat-card-icon.orange {
            background: rgba(255, 152, 0, 0.15);
            color: var(--warning);
        }

        .stat-card-icon.red {
            background: rgba(244, 67, 54, 0.15);
            color: var(--danger);
        }

        .stat-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .stat-card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .stat-card-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            text-decoration: none;
            color: white;
        }

        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
            text-decoration: none;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            background: var(--bg-input);
        }

        .table tr:hover td {
            background: var(--bg-input);
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 3px;
        }

        .badge-success {
            background: rgba(60, 135, 58, 0.15);
            color: var(--primary);
        }

        .badge-warning {
            background: rgba(255, 152, 0, 0.15);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.15);
            color: var(--danger);
        }

        .badge-info {
            background: rgba(33, 150, 243, 0.15);
            color: var(--info);
        }

        .badge-secondary {
            background: var(--bg-input);
            color: var(--text-secondary);
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Quick Links Grid - cPanel Style */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px;
        }

        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            text-align: center;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.15s ease;
        }

        .quick-link:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .quick-link-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            background: rgba(60, 135, 58, 0.12);
            color: var(--primary);
        }

        .quick-link-icon svg {
            width: 22px;
            height: 22px;
        }

        .quick-link-text {
            font-size: 12px;
            font-weight: 500;
        }

        /* Service Cards Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .service-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .service-card-header {
            padding: 12px 15px;
            background: var(--bg-input);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .service-card-icon {
            width: 38px;
            height: 38px;
            background: var(--primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .service-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .service-card-info {
            flex: 1;
            min-width: 0;
        }

        .service-card-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .service-card-domain {
            font-size: 12px;
            color: var(--primary);
        }

        .service-card-body {
            padding: 12px 15px;
        }

        .service-card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }

        .service-card-meta-item {
            font-size: 12px;
        }

        .service-card-meta-label {
            color: var(--text-muted);
        }

        .service-card-meta-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .service-card-actions {
            display: flex;
            gap: 6px;
        }

        .service-card-actions .btn {
            flex: 1;
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            color: var(--text-primary);
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            transition: border-color 0.15s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(60, 135, 58, 0.1);
        }

        /* Terminal */
        .terminal {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .terminal-header {
            background: #323232;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .terminal-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .terminal-dot.red {
            background: #ff5f56;
        }

        .terminal-dot.yellow {
            background: #ffbd2e;
        }

        .terminal-dot.green {
            background: #27c93f;
        }

        .terminal-body {
            padding: 15px;
            height: 300px;
            overflow-y: auto;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            width: 60px;
            height: 60px;
            background: var(--bg-input);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .empty-state-icon svg {
            width: 28px;
            height: 28px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .empty-state-text {
            font-size: 13px;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex !important;
            }

            .mobile-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 99;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .mobile-overlay.visible {
                opacity: 1;
                visibility: visible;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        /* Utility */
        .text-success {
            color: var(--success);
        }

        .text-warning {
            color: var(--warning);
        }

        .text-danger {
            color: var(--danger);
        }

        .text-muted {
            color: var(--text-muted);
        }

        .text-primary {
            color: var(--primary);
        }

        .mt-0 {
            margin-top: 0;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-15 {
            margin-bottom: 15px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .d-flex {
            display: flex;
        }

        .align-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-5 {
            gap: 5px;
        }

        .gap-10 {
            gap: 10px;
        }

        /* Progress Bar */
        .progress {
            height: 6px;
            background: var(--bg-input);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="mobile-overlay" onclick="toggleSidebar()"></div>

        <div class="main-content">
            <?php include __DIR__ . '/../partials/header.php'; ?>

            <main class="content">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Theme handling
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('logicpanel-theme', theme);
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);

            fetch('<?= $base_url ?? '' ?>/settings/theme', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: newTheme })
            });
        }

        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
            document.querySelector('.mobile-overlay').classList.toggle('visible');
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('logicpanel-theme');
        if (savedTheme) {
            setTheme(savedTheme);
        }
    </script>
</body>

</html>