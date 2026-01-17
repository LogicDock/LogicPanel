<?php
$page_title = 'Reseller Dashboard';
$current_page = 'reseller';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Reseller Dashboard</h1>
</div>

<!-- Stats Overview -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-card-icon green">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-value">
            <?= $totalUsers ?? 0 ?> /
            <?= $maxUsers ?? '∞' ?>
        </div>
        <div class="stat-card-label">Users</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon blue">
            <i data-lucide="box"></i>
        </div>
        <div class="stat-card-value">
            <?= $totalServices ?? 0 ?>
        </div>
        <div class="stat-card-label">Total Services</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon orange">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-value">
            <?= $activeServices ?? 0 ?>
        </div>
        <div class="stat-card-label">Active Services</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon purple">
            <i data-lucide="package"></i>
        </div>
        <div class="stat-card-value">
            <?= isset($resellerPackage) ? htmlspecialchars($resellerPackage->display_name) : 'None' ?>
        </div>
        <div class="stat-card-label">Your Plan</div>
    </div>
</div>

<!-- Quick Actions & Users List -->
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="zap"></i>
                Quick Actions
            </h2>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="<?= $base_url ?>/reseller/users/create" class="btn btn-primary"
                    style="width: 100%; margin-bottom: 10px;">
                    <i data-lucide="user-plus"></i>
                    Create New User
                </a>
                <a href="<?= $base_url ?>/reseller/packages" class="btn btn-secondary"
                    style="width: 100%; margin-bottom: 10px;">
                    <i data-lucide="package"></i>
                    Manage Packages
                </a>
                <a href="<?= $base_url ?>/reseller/users" class="btn btn-secondary" style="width: 100%;">
                    <i data-lucide="users"></i>
                    View All Users
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="users"></i>
                Your Users
            </h2>
            <a href="<?= $base_url ?>/reseller/users" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($users) || count($users) === 0): ?>
                <p class="text-muted" style="padding: 20px; text-align: center;">
                    No users yet. <a href="<?= $base_url ?>/reseller/users/create">Create your first user</a>
                </p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Services</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($users->toArray(), 0, 5) as $u): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($u['username']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($u['email']) ?>
                                </td>
                                <td>
                                    <?= count($u['services'] ?? []) ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= $base_url ?>/reseller/users/<?= $u['id'] ?>/edit"
                                        class="btn btn-sm btn-secondary">
                                        <i data-lucide="edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Resource Usage -->
<?php if (isset($resellerPackage)): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="pie-chart"></i>
                Resource Usage
            </h2>
        </div>
        <div class="card-body">
            <div class="resource-grid">
                <div class="resource-item">
                    <div class="resource-label">Users</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($totalUsers / max(1, $maxUsers)) * 100) ?>%">
                        </div>
                    </div>
                    <div class="resource-text">
                        <?= $totalUsers ?> /
                        <?= $maxUsers ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .quick-actions {
        display: flex;
        flex-direction: column;
    }

    .resource-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .resource-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .resource-label {
        font-size: 13px;
        color: var(--text-secondary);
    }

    .progress-bar {
        height: 8px;
        background: var(--bg-tertiary);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--primary);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .resource-text {
        font-size: 12px;
        color: var(--text-muted);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-secondary);
    }

    .table tbody tr:hover {
        background: var(--bg-secondary);
    }

    @media (max-width: 991px) {
        div[style*="grid-template-columns: 1fr 2fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>