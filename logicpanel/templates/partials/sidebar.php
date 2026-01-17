<!-- cPanel Style Minimal Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="<?= $base_url ?? '' ?>/" class="sidebar-brand-text">LogicPanel</a>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $base_url ?? '' ?>/"
            class="sidebar-nav-link <?= ($current_page ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i data-lucide="layout-grid"></i>
            <span>Tools</span>
        </a>

        <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'reseller'])): ?>
            <div class="sidebar-nav-section">Reseller</div>
            <a href="<?= $base_url ?? '' ?>/reseller"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'reseller' ? 'active' : '' ?>">
                <i data-lucide="briefcase"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/reseller/users"
                class="sidebar-nav-link <?= in_array($current_page ?? '', ['reseller_users']) ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>My Users</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/reseller/packages"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'reseller_packages' ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>My Packages</span>
            </a>
        <?php endif; ?>

        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <div class="sidebar-nav-section">Admin</div>
            <a href="<?= $base_url ?? '' ?>/admin"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'admin' ? 'active' : '' ?>">
                <i data-lucide="shield"></i>
                <span>Admin Panel</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/packages"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'packages' ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>Packages</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/reseller-packages"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'reseller_packages_admin' ? 'active' : '' ?>">
                <i data-lucide="layers"></i>
                <span>Reseller Plans</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/api-keys"
                class="sidebar-nav-link <?= ($current_page ?? '') === 'apikeys' ? 'active' : '' ?>">
                <i data-lucide="key"></i>
                <span>API Keys</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
            <div class="sidebar-user-role"><?= htmlspecialchars($_SESSION['user_role'] ?? 'user') ?></div>
        </div>
        <a href="<?= $base_url ?? '' ?>/logout" class="sidebar-logout" title="Logout">
            <i data-lucide="log-out"></i>
        </a>
    </div>
</aside>

<style>
    .sidebar {
        background: #1E2127 !important;
    }

    .sidebar-brand {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-brand-text {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        text-decoration: none;
        letter-spacing: -0.5px;
    }

    .sidebar-brand-text:hover {
        color: #fff;
        text-decoration: none;
    }

    .sidebar-logout {
        color: var(--text-muted);
        padding: 5px;
        transition: color 0.15s ease;
    }

    .sidebar-logout:hover {
        color: var(--danger);
    }

    .sidebar-logout svg {
        width: 18px;
        height: 18px;
    }
</style>