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

<!-- Services Management -->
<div class="card mt-20">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="server"></i>
            All Services
        </h2>
        <a href="<?= $base_url ?? '' ?>/admin/services" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($services) || (method_exists($services, 'isEmpty') && $services->isEmpty())): ?>
            <p class="text-muted" style="padding: 15px;">No services found</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>User</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($service->name ?? 'Unnamed') ?></strong>
                                    <br><small
                                        class="text-muted"><?= htmlspecialchars($service->container_id ? substr($service->container_id, 0, 12) : 'N/A') ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($service->user->name ?? 'Unknown') ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($service->user->email ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if ($service->primaryDomain): ?>
                                        <a href="https://<?= htmlspecialchars($service->primaryDomain->domain) ?>" target="_blank"
                                            class="text-primary">
                                            <?= htmlspecialchars($service->primaryDomain->domain) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No domain</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($service->status === 'running'): ?>
                                        <span class="badge badge-success"><span class="badge-dot"></span> Running</span>
                                    <?php elseif ($service->status === 'suspended'): ?>
                                        <span class="badge badge-warning"><span class="badge-dot"></span> Suspended</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><span class="badge-dot"></span>
                                            <?= ucfirst($service->status ?? 'Unknown') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($service->status === 'suspended'): ?>
                                            <button onclick="unsuspendService(<?= $service->id ?>)" class="btn btn-sm btn-secondary"
                                                title="Unsuspend">
                                                <i data-lucide="play"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="suspendService(<?= $service->id ?>)" class="btn btn-sm btn-warning"
                                                title="Suspend">
                                                <i data-lucide="pause"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button
                                            onclick="terminateService(<?= $service->id ?>, '<?= htmlspecialchars($service->name ?? '') ?>')"
                                            class="btn btn-sm btn-danger" title="Terminate">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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

    .mt-20 {
        margin-top: 20px;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-warning {
        background: var(--warning);
        color: white;
        border-color: var(--warning);
    }

    .btn-warning:hover {
        background: #e68900;
    }
</style>

<script>
    async function suspendService(id) {
        if (!confirm('Are you sure you want to suspend this service?')) return;

        try {
            const response = await fetch(`<?= $base_url ?? '' ?>/admin/services/${id}/suspend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to suspend service');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function unsuspendService(id) {
        if (!confirm('Are you sure you want to unsuspend this service?')) return;

        try {
            const response = await fetch(`<?= $base_url ?? '' ?>/admin/services/${id}/unsuspend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to unsuspend service');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function terminateService(id, name) {
        if (!confirm(`Are you sure you want to PERMANENTLY DELETE "${name}"? This action cannot be undone!`)) return;
        if (!confirm('Are you ABSOLUTELY SURE? All data will be lost!')) return;

        try {
            const response = await fetch(`<?= $base_url ?? '' ?>/admin/services/${id}/terminate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to terminate service');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>