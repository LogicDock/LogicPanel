<?php
$page_title = 'Admin Dashboard';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
</div>

<!-- Stats Overview -->
<div class="admin-stats-grid">
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
<div class="admin-panel-grid">
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
                        <?php
                        // Support both object and array access
                        $userName = is_object($activity) ? ($activity->user_name ?? 'System') : ($activity['user_name'] ?? 'System');
                        $desc = is_object($activity) ? ($activity->description ?? $activity->action ?? '') : ($activity['description'] ?? $activity['action'] ?? '');
                        $createdAt = is_object($activity) ? ($activity->created_at ?? null) : ($activity['created_at'] ?? null);
                        ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <strong><?= htmlspecialchars($userName) ?></strong>
                                <span class="text-muted"><?= htmlspecialchars($desc) ?></span>
                            </div>
                            <?php if ($createdAt): ?>
                                <span class="activity-time"><?= date('M d, H:i', strtotime($createdAt)) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Admin Stats Grid - Responsive */
    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    /* Admin Panel Grid - Responsive */
    .admin-panel-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

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
        min-width: 0;
        flex: 1;
    }

    .activity-info span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .activity-time {
        color: var(--text-muted);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1200px) {
        .admin-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 991px) {
        .admin-panel-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 600px) {
        .admin-stats-grid {
            grid-template-columns: 1fr;
        }

        .activity-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>