<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
</div>

<!-- Stats Overview -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-card-icon green">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-value"><?= $stats['users'] ?? 0 ?></div>
        <div class="stat-card-label">Total Users</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon blue">
            <i data-lucide="box"></i>
        </div>
        <div class="stat-card-value"><?= $stats['services'] ?? 0 ?></div>
        <div class="stat-card-label">Total Services</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon orange">
            <i data-lucide="database"></i>
        </div>
        <div class="stat-card-value"><?= $stats['databases'] ?? 0 ?></div>
        <div class="stat-card-label">Databases</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon red">
            <i data-lucide="globe"></i>
        </div>
        <div class="stat-card-value"><?= $stats['domains'] ?? 0 ?></div>
        <div class="stat-card-label">Domains</div>
    </div>
</div>

<!-- Docker Status & Recent Activity -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Docker Status -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="container"></i>
                Docker Status
            </h2>
        </div>
        <div class="card-body">
            <div class="d-flex align-center gap-10 mb-15">
                <?php if ($dockerConnected ?? false): ?>
                    <span class="badge badge-success">
                        <span class="badge-dot"></span>
                        Connected
                    </span>
                <?php else: ?>
                    <span class="badge badge-danger">
                        <span class="badge-dot"></span>
                        Disconnected
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($dockerInfo ?? null): ?>
                <div class="info-list">
                    <div class="info-list-item">
                        <span class="info-list-label">Version</span>
                        <span class="info-list-value"><?= htmlspecialchars($dockerInfo['ServerVersion'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-list-label">Containers</span>
                        <span class="info-list-value"><?= $dockerInfo['Containers'] ?? 0 ?>
                            (<?= $dockerInfo['ContainersRunning'] ?? 0 ?> running)</span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-list-label">Images</span>
                        <span class="info-list-value"><?= $dockerInfo['Images'] ?? 0 ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Docker info not available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="activity"></i>
                Recent Activity
            </h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentActivity)): ?>
                <p class="text-muted" style="padding: 15px;">No recent activity</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach (array_slice((array) $recentActivity, 0, 8) as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <strong><?= htmlspecialchars($activity->user_name ?? 'System') ?></strong>
                                <span
                                    class="text-muted"><?= htmlspecialchars($activity->description ?? $activity->action) ?></span>
                            </div>
                            <span class="activity-time"><?= date('M d, H:i', strtotime($activity->created_at)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-list-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 13px;
    }

    .info-list-item:last-child {
        border-bottom: none;
    }

    .info-list-label {
        color: var(--text-secondary);
    }

    .info-list-value {
        font-weight: 500;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
    }

    .activity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
        font-size: 12px;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .activity-time {
        color: var(--text-muted);
        white-space: nowrap;
    }

    @media (max-width: 991px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>