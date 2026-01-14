<?php
$page_title = 'Service Details - ' . ($service->name ?? 'Unknown');
$current_page = 'services';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/dashboard" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title"><?= htmlspecialchars($service->name) ?></h1>
            <p class="page-subtitle"><?= htmlspecialchars($service->primaryDomain?->domain ?? 'No domain configured') ?></p>
        </div>
    </div>
    <div class="page-header-actions">
        <?php if ($service->status === 'running'): ?>
            <button onclick="serviceAction('stop')" class="btn btn-secondary">
                <i data-lucide="square"></i> Stop
            </button>
            <button onclick="serviceAction('restart')" class="btn btn-info">
                <i data-lucide="refresh-cw"></i> Restart
            </button>
        <?php elseif ($service->status === 'stopped' || $service->status === 'pending'): ?>
            <button onclick="serviceAction('start')" class="btn btn-success">
                <i data-lucide="play"></i> Start
            </button>
        <?php endif; ?>
        <button onclick="serviceAction('rebuild')" class="btn btn-primary">
            <i data-lucide="wrench"></i> Rebuild
        </button>
    </div>
</div>

<!-- Suspended Banner -->
<?php if (method_exists($service, 'isSuspended') && $service->isSuspended()): ?>
    <div class="alert alert-danger mb-20">
        <i data-lucide="alert-triangle"></i>
        <div>
            <strong>This service is suspended</strong>
            <p>Please contact support or renew your subscription.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid mb-20">
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <i data-lucide="cpu"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($containerStats['cpu'] ?? 0, 1) ?>%</div>
            <div class="stat-label">CPU Usage</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <i data-lucide="memory-stick"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($containerStats['memory_percent'] ?? 0, 1) ?>%</div>
            <div class="stat-label">Memory</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <i data-lucide="download"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $containerStats['network_rx_human'] ?? '0 B' ?></div>
            <div class="stat-label">Network In</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <i data-lucide="upload"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $containerStats['network_tx_human'] ?? '0 B' ?></div>
            <div class="stat-label">Network Out</div>
        </div>
    </div>
</div>

<!-- Main Content Layout -->
<div class="service-layout">
    <!-- Left Column -->
    <div class="service-main">
        <!-- Quick Actions -->
        <div class="card mb-20">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="zap"></i> Quick Actions
                </h2>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="<?= $base_url ?>/terminal/<?= $service->id ?>" class="quick-action-item">
                        <div class="quick-action-icon" style="background: rgba(33, 150, 243, 0.15); color: #2196F3;">
                            <i data-lucide="terminal"></i>
                        </div>
                        <span>Terminal</span>
                    </a>
                    <a href="<?= $base_url ?>/files/<?= $service->id ?>" class="quick-action-item">
                        <div class="quick-action-icon" style="background: rgba(255, 152, 0, 0.15); color: #FF9800;">
                            <i data-lucide="folder"></i>
                        </div>
                        <span>Files</span>
                    </a>
                    <a href="<?= $base_url ?>/git/<?= $service->id ?>" class="quick-action-item">
                        <div class="quick-action-icon" style="background: rgba(156, 39, 176, 0.15); color: #9C27B0;">
                            <i data-lucide="git-branch"></i>
                        </div>
                        <span>Git Deploy</span>
                    </a>
                    <a href="<?= $base_url ?>/services/<?= $service->id ?>/env" class="quick-action-item">
                        <div class="quick-action-icon" style="background: rgba(96, 125, 139, 0.15); color: #607D8B;">
                            <i data-lucide="settings"></i>
                        </div>
                        <span>Env Vars</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Container Logs -->
        <div class="card mb-20">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="scroll-text"></i> Container Logs
                </h2>
                <button onclick="refreshLogs()" class="btn btn-sm btn-secondary">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>
            <div class="card-body" style="padding: 0;">
                <div id="logsContainer" class="logs-container">
                    <p class="text-muted">Loading logs...</p>
                </div>
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="rocket"></i> Recent Deployments
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($service->deployments) || (is_object($service->deployments) && $service->deployments->isEmpty())): ?>
                    <p class="text-muted">No deployments yet.</p>
                <?php else: ?>
                    <div class="deployments-list">
                        <?php 
                        $deployments = is_object($service->deployments) ? $service->deployments->take(5) : array_slice($service->deployments, 0, 5);
                        foreach ($deployments as $deploy): 
                            $statusClass = match($deploy->status ?? 'pending') {
                                'completed' => 'success',
                                'failed' => 'danger',
                                'installing' => 'info',
                                default => 'warning'
                            };
                        ?>
                            <div class="deployment-item">
                                <span class="deployment-status badge badge-<?= $statusClass ?>">
                                    <span class="badge-dot"></span>
                                    <?= ucfirst($deploy->status ?? 'pending') ?>
                                </span>
                                <div class="deployment-info">
                                    <p class="deployment-message"><?= htmlspecialchars($deploy->commit_message ?? 'No message') ?></p>
                                    <p class="deployment-meta">
                                        <code><?= $deploy->getShortHash() ?? 'N/A' ?></code>
                                        &middot; <?= date('M d, H:i', strtotime($deploy->created_at)) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="service-sidebar">
        <!-- Service Info -->
        <div class="card mb-15">
            <div class="card-header">
                <h3 class="card-title">Service Info</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <?php 
                            $statusClass = match($service->status) {
                                'running' => 'success',
                                'stopped' => 'secondary',
                                'error' => 'danger',
                                default => 'warning'
                            };
                            ?>
                            <span class="badge badge-<?= $statusClass ?>">
                                <?php if ($service->status === 'running'): ?>
                                    <span class="badge-dot"></span>
                                <?php endif; ?>
                                <?= ucfirst($service->status) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Node Version</span>
                        <span class="info-value">v<?= htmlspecialchars($service->node_version) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Port</span>
                        <span class="info-value"><?= $service->port ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Plan</span>
                        <span class="info-value" style="text-transform: capitalize;"><?= htmlspecialchars($service->plan) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created</span>
                        <span class="info-value"><?= date('M d, Y', strtotime($service->created_at)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Domains -->
        <div class="card mb-15">
            <div class="card-header">
                <h3 class="card-title">Domains</h3>
                <a href="<?= $base_url ?>/domains" class="btn btn-sm btn-link">Manage</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($service->domains) || (is_object($service->domains) && $service->domains->isEmpty())): ?>
                    <div style="padding: 15px;">
                        <p class="text-muted">No domains configured.</p>
                    </div>
                <?php else: ?>
                    <div class="domain-list">
                        <?php foreach ($service->domains as $domain): ?>
                            <div class="domain-item">
                                <i data-lucide="globe"></i>
                                <span class="domain-name"><?= htmlspecialchars($domain->domain) ?></span>
                                <?php if ($domain->is_primary): ?>
                                    <span class="badge badge-success">Primary</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Databases -->
        <div class="card mb-15">
            <div class="card-header">
                <h3 class="card-title">Databases</h3>
                <a href="<?= $base_url ?>/databases" class="btn btn-sm btn-link">Manage</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($service->databases) || (is_object($service->databases) && $service->databases->isEmpty())): ?>
                    <div style="padding: 15px;">
                        <p class="text-muted">No databases created.</p>
                    </div>
                <?php else: ?>
                    <div class="database-list">
                        <?php foreach ($service->databases as $db): ?>
                            <div class="database-item">
                                <i data-lucide="database"></i>
                                <span class="database-name"><?= htmlspecialchars($db->db_name) ?></span>
                                <span class="badge badge-secondary"><?= strtoupper($db->type) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Backups -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Backups</h3>
                <a href="<?= $base_url ?>/backups" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body">
                <button onclick="createBackup()" class="btn btn-outline" style="width: 100%;">
                    <i data-lucide="plus"></i> Create Backup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.back-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    transition: all 0.15s ease;
}

.back-btn:hover {
    background: var(--bg-input);
    color: var(--text-primary);
    text-decoration: none;
}

.back-btn svg {
    width: 20px;
    height: 20px;
}

.page-header-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.page-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.page-subtitle {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
}

.page-header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon svg {
    width: 20px;
    height: 20px;
}

.stat-icon-blue { background: rgba(33, 150, 243, 0.15); color: #2196F3; }
.stat-icon-purple { background: rgba(156, 39, 176, 0.15); color: #9C27B0; }
.stat-icon-green { background: rgba(76, 175, 80, 0.15); color: #4CAF50; }
.stat-icon-orange { background: rgba(255, 152, 0, 0.15); color: #FF9800; }

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.stat-label {
    font-size: 12px;
    color: var(--text-muted);
}

/* Service Layout */
.service-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
}

.service-main {
    min-width: 0;
}

.service-sidebar {
    position: sticky;
    top: 75px;
}

/* Quick Actions Grid */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px 10px;
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.15s ease;
}

.quick-action-item:hover {
    background: var(--bg-input);
    text-decoration: none;
}

.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-icon svg {
    width: 20px;
    height: 20px;
}

.quick-action-item span {
    font-size: 12px;
    font-weight: 500;
}

/* Logs Container */
.logs-container {
    background: #1e1e1e;
    color: #d4d4d4;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 12px;
    padding: 15px;
    height: 250px;
    overflow-y: auto;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
}

.logs-container pre {
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}

/* Deployments List */
.deployments-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.deployment-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    background: var(--bg-input);
    border-radius: var(--border-radius);
}

.deployment-status {
    flex-shrink: 0;
}

.deployment-info {
    flex: 1;
    min-width: 0;
}

.deployment-message {
    font-size: 13px;
    font-weight: 500;
    margin: 0 0 4px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.deployment-meta {
    font-size: 11px;
    color: var(--text-muted);
    margin: 0;
}

.deployment-meta code {
    background: var(--bg-card);
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 10px;
}

/* Info List */
.info-list {
    display: flex;
    flex-direction: column;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 12px;
    color: var(--text-muted);
}

.info-value {
    font-size: 13px;
    font-weight: 500;
}

/* Domain & Database Lists */
.domain-list, .database-list {
    display: flex;
    flex-direction: column;
}

.domain-item, .database-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    border-bottom: 1px solid var(--border-color);
}

.domain-item:last-child, .database-item:last-child {
    border-bottom: none;
}

.domain-item svg, .database-item svg {
    width: 16px;
    height: 16px;
    color: var(--text-muted);
    flex-shrink: 0;
}

.domain-name, .database-name {
    flex: 1;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Alert */
.alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border-radius: var(--border-radius);
}

.alert-danger {
    background: rgba(244, 67, 54, 0.1);
    border: 1px solid rgba(244, 67, 54, 0.3);
    color: #d32f2f;
}

.alert svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.alert p {
    margin: 0;
    font-size: 13px;
}

/* Button Styles */
.btn-info {
    background: #2196F3;
    color: white;
    border: none;
}

.btn-info:hover {
    background: #1976D2;
}

.btn-success {
    background: var(--success);
    color: white;
    border: none;
}

.btn-success:hover {
    background: #43A047;
}

.btn-outline {
    background: transparent;
    border: 2px dashed var(--border-color);
    color: var(--text-secondary);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-link {
    background: none;
    border: none;
    color: var(--primary);
    padding: 0;
}

.btn-link:hover {
    text-decoration: underline;
}

/* Utilities */
.mb-15 { margin-bottom: 15px; }
.mb-20 { margin-bottom: 20px; }
.text-muted { color: var(--text-muted); }

/* Responsive */
@media (max-width: 991px) {
    .service-layout {
        grid-template-columns: 1fr;
    }
    
    .service-sidebar {
        position: static;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-actions {
        width: 100%;
    }
    
    .page-header-actions .btn {
        flex: 1;
    }
}
</style>

<script>
const serviceId = <?= $service->id ?>;

async function serviceAction(action) {
    if (!confirm(`Are you sure you want to ${action} this service?`)) return;
    
    try {
        const response = await fetch(`<?= $base_url ?>/services/${serviceId}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Action failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function refreshLogs() {
    const container = document.getElementById('logsContainer');
    container.innerHTML = '<p class="text-muted">Loading...</p>';
    
    try {
        const response = await fetch(`<?= $base_url ?>/services/${serviceId}/logs?tail=100`);
        const data = await response.json();
        
        if (data.success) {
            container.innerHTML = data.logs 
                ? `<pre>${escapeHtml(data.logs)}</pre>` 
                : '<p class="text-muted">No logs available</p>';
            container.scrollTop = container.scrollHeight;
        } else {
            container.innerHTML = `<p style="color: #f44336;">${data.error || 'Failed to load logs'}</p>`;
        }
    } catch (error) {
        container.innerHTML = `<p style="color: #f44336;">Error: ${error.message}</p>`;
    }
}

async function createBackup() {
    if (!confirm('Create a new backup of this service?')) return;
    
    try {
        const response = await fetch(`<?= $base_url ?>/backups/${serviceId}/create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Backup created successfully!');
            location.reload();
        } else {
            alert(data.error || 'Backup failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load logs on page load
document.addEventListener('DOMContentLoaded', refreshLogs);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>