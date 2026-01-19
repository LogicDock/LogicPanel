<!-- LogicPanel Sidebar - cPanel Style -->
<aside class="lp-sidebar">
    <a href="<?= $base_url ?? '' ?>/" class="lp-sidebar-brand">
        <img src="<?= $base_url ?? '' ?>/assets/logo.svg" alt="LogicPanel" class="lp-brand-logo">
    </a>

    <nav class="lp-sidebar-nav">
        <!-- Main Tools -->
        <a href="<?= $base_url ?? '' ?>/"
            class="lp-nav-item <?= ($current_page ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i data-lucide="layout-grid"></i>
            <span>Tools</span>
        </a>

        <?php if (($_SESSION['user_role'] ?? '') === 'reseller'): ?>
            <!-- Reseller Section -->
            <div class="lp-nav-section">Reseller</div>
            <a href="<?= $base_url ?? '' ?>/reseller"
                class="lp-nav-item <?= ($current_page ?? '') === 'reseller' ? 'active' : '' ?>">
                <i data-lucide="briefcase"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/reseller/users"
                class="lp-nav-item <?= ($current_page ?? '') === 'reseller_users' ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>My Users</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/reseller/users/create"
                class="lp-nav-item <?= ($current_page ?? '') === 'reseller_users_create' ? 'active' : '' ?>">
                <i data-lucide="user-plus"></i>
                <span>Create User</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/reseller/packages"
                class="lp-nav-item <?= ($current_page ?? '') === 'reseller_packages' ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>My Packages</span>
            </a>
        <?php endif; ?>

        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <!-- Admin Section -->
            <div class="lp-nav-section">Admin</div>
            <a href="<?= $base_url ?? '' ?>/admin"
                class="lp-nav-item <?= ($current_page ?? '') === 'admin' ? 'active' : '' ?>">
                <i data-lucide="shield"></i>
                <span>Admin Panel</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/nodes"
                class="lp-nav-item <?= ($current_page ?? '') === 'nodes' ? 'active' : '' ?>">
                <i data-lucide="server"></i>
                <span>Nodes / Servers</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/users"
                class="lp-nav-item <?= ($current_page ?? '') === 'users' ? 'active' : '' ?>">
                <i data-lucide="users"></i>
                <span>Users</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/packages"
                class="lp-nav-item <?= ($current_page ?? '') === 'packages' ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>Service Packages</span>
            </a>
            <div class="lp-nav-section">Reseller Management</div>
            <a href="<?= $base_url ?? '' ?>/admin/resellers"
                class="lp-nav-item <?= ($current_page ?? '') === 'resellers' ? 'active' : '' ?>">
                <i data-lucide="user-cog"></i>
                <span>Resellers List</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/reseller-packages"
                class="lp-nav-item <?= ($current_page ?? '') === 'reseller_packages_admin' ? 'active' : '' ?>">
                <i data-lucide="layers"></i>
                <span>Reseller Plans</span>
            </a>
            <a href="<?= $base_url ?? '' ?>/admin/api-keys"
                class="lp-nav-item <?= ($current_page ?? '') === 'apikeys' ? 'active' : '' ?>">
                <i data-lucide="key"></i>
                <span>API Keys</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- User Section -->
    <div class="lp-sidebar-user">
        <a href="<?= $base_url ?? '' ?>/settings" class="lp-user-profile" title="Account Settings">
            <div class="lp-user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="lp-user-info">
                <div class="lp-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="lp-user-role"><?= ucfirst(htmlspecialchars($_SESSION['user_role'] ?? 'user')) ?></div>
            </div>
        </a>
        <a href="<?= $base_url ?? '' ?>/logout" class="lp-logout" title="Logout">
            <i data-lucide="log-out"></i>
        </a>
    </div>
</aside>

<style>
    /* ========================================
   LogicPanel Sidebar - Clean Rebuild
   ======================================== */
    .lp-sidebar {
        width: 200px;
        background: #1E2127;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 100;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Brand */
    .lp-sidebar-brand {
        padding: 14px 18px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        text-decoration: none;
    }

    .lp-brand-logo {
        height: 32px;
        width: auto;
    }

    /* Navigation */
    .lp-sidebar-nav {
        flex: 1;
        padding: 12px 0;
        overflow-y: auto;
    }

    .lp-nav-section {
        padding: 16px 18px 8px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6B7280;
    }

    .lp-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 18px;
        color: #9CA3AF;
        font-size: 13px;
        text-decoration: none !important;
        transition: all 0.15s ease;
        border-left: 3px solid transparent;
        margin: 2px 0;
    }

    .lp-nav-item svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .lp-nav-item:hover {
        color: #E5E7EB;
        background: rgba(255, 255, 255, 0.05);
        text-decoration: none !important;
    }

    .lp-nav-item.active {
        color: #fff;
        background: rgba(60, 135, 58, 0.2);
        border-left-color: #3C873A;
    }

    .lp-nav-item.active svg {
        color: #3C873A;
    }

    /* User Section */
    .lp-sidebar-user {
        padding: 14px 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0, 0, 0, 0.1);
    }

    .lp-user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        text-decoration: none;
        padding: 4px;
        margin: -4px;
        border-radius: 6px;
        transition: background 0.15s ease;
    }

    .lp-user-profile:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .lp-user-avatar {
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #3C873A 0%, #2D6A2E 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 13px;
        flex-shrink: 0;
    }

    .lp-user-info {
        flex: 1;
        min-width: 0;
    }

    .lp-user-name {
        color: #E5E7EB;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lp-user-role {
        color: #6B7280;
        font-size: 10px;
    }

    .lp-logout {
        color: #6B7280;
        padding: 6px;
        border-radius: 6px;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lp-logout:hover {
        color: #EF4444;
        background: rgba(239, 68, 68, 0.1);
    }

    .lp-logout svg {
        width: 16px;
        height: 16px;
    }

    /* Update main content margin for new sidebar width */
    .main-content {
        margin-left: 200px !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .lp-sidebar {
            transform: translateX(-100%);
        }

        .lp-sidebar.open {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0 !important;
        }
    }
</style>