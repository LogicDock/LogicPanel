<?php
$page_title = 'My Services';
$current_page = 'services';
ob_start();
?>

<div class="cpanel-app-header">
    <div class="app-icon">
        <i data-lucide="layers"></i>
    </div>
    <div class="app-title">
        <h1>Web Applications</h1>
        <span class="app-subtitle">Manage your deployed applications</span>
    </div>
    <div class="header-actions">
        <a href="<?= $base_url ?>/services/create" class="btn-primary">
            <i data-lucide="plus"></i> Create Application
        </a>
    </div>
</div>

<div class="cpanel-tabs">
    <div class="tab-nav">
        <a href="#" class="tab-link active">
            <i data-lucide="grid-3x3"></i> ALL APPLICATIONS
        </a>
    </div>
    <div class="tab-info">
        <span class="count-badge"><?= count($services ?? []) ?> apps</span>
    </div>
</div>

<?php if (empty($services) || count($services) === 0): ?>
    <!-- Empty State -->
    <div class="empty-state">
        <div class="empty-icon">
            <i data-lucide="rocket"></i>
        </div>
        <h2>No Applications Yet</h2>
        <p>Deploy your first application to get started. You can use Node.js, Python, Java, or Go.</p>
        <a href="<?= $base_url ?>/services/create" class="btn-primary-lg">
            <i data-lucide="plus-circle"></i> Create Your First App
        </a>
    </div>
<?php else: ?>
    <!-- Services Table -->
    <div class="services-table-wrapper">
        <table class="services-table">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Runtime</th>
                    <th>Status</th>
                    <th>Domain</th>
                    <th>Resources</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service):
                    $runtimeIcons = [
                        'nodejs' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg',
                        'python' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg',
                        'java' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg',
                        'go' => 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg'
                    ];
                    $statusConfig = [
                        'running' => ['class' => 'status-running', 'text' => 'Running'],
                        'stopped' => ['class' => 'status-stopped', 'text' => 'Stopped'],
                        'creating' => ['class' => 'status-creating', 'text' => 'Creating'],
                        'error' => ['class' => 'status-error', 'text' => 'Error'],
                        'suspended' => ['class' => 'status-suspended', 'text' => 'Suspended'],
                    ];
                    $cfg = $statusConfig[$service->status] ?? $statusConfig['stopped'];
                    ?>
                    <tr>
                        <td>
                            <div class="app-info">
                                <div class="app-icon-sm">
                                    <img src="<?= $runtimeIcons[$service->runtime] ?? $runtimeIcons['nodejs'] ?>" alt="">
                                </div>
                                <div>
                                    <div class="app-name"><?= htmlspecialchars($service->name) ?></div>
                                    <div class="app-id">ID: <?= $service->id ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="runtime-badge">
                                <?= ucfirst($service->runtime) ?>         <?= $service->runtime_version ?? '' ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= $cfg['class'] ?>">
                                <?php if ($service->status === 'running' || $service->status === 'creating'): ?>
                                    <span class="status-dot"></span>
                                <?php endif; ?>
                                <?= $cfg['text'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($service->primaryDomain): ?>
                                <a href="https://<?= htmlspecialchars($service->primaryDomain->domain) ?>" target="_blank"
                                    class="domain-link">
                                    <?= htmlspecialchars($service->primaryDomain->domain) ?>
                                    <i data-lucide="external-link"></i>
                                </a>
                            <?php else: ?>
                                <span class="no-domain">No domain</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="resource-info">
                                <span><?= $service->package->memory_limit ?? '512' ?> MB</span>
                                <span><?= $service->package->cpu_limit ?? '0.5' ?> CPU</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="btn-action" title="Manage">
                                    <i data-lucide="settings"></i>
                                </a>
                                <?php if ($service->status === 'running'): ?>
                                    <button onclick="serviceAction(<?= $service->id ?>, 'restart')" class="btn-action"
                                        title="Restart">
                                        <i data-lucide="refresh-cw"></i>
                                    </button>
                                    <button onclick="serviceAction(<?= $service->id ?>, 'stop')" class="btn-action btn-danger"
                                        title="Stop">
                                        <i data-lucide="square"></i>
                                    </button>
                                <?php elseif ($service->status === 'stopped'): ?>
                                    <button onclick="serviceAction(<?= $service->id ?>, 'start')" class="btn-action btn-success"
                                        title="Start">
                                        <i data-lucide="play"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
    /* Header */
    .cpanel-app-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 24px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .cpanel-app-header .app-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }

    .cpanel-app-header .app-icon svg {
        width: 24px;
        height: 24px;
    }

    .app-title h1 {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .app-subtitle {
        font-size: 13px;
        color: var(--text-muted);
    }

    .header-actions {
        margin-left: auto;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: var(--primary);
        color: #fff;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
    }

    .btn-primary:hover {
        opacity: 0.9;
    }

    .btn-primary svg {
        width: 16px;
        height: 16px;
    }

    /* Tabs */
    .cpanel-tabs {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 24px;
    }

    .tab-nav {
        display: flex;
    }

    .tab-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 20px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .tab-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .tab-link svg {
        width: 16px;
        height: 16px;
    }

    .count-badge {
        font-size: 12px;
        color: var(--text-muted);
        background: var(--bg-input);
        padding: 4px 10px;
        border-radius: 12px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(135deg, rgba(60, 135, 58, 0.1), rgba(60, 135, 58, 0.05));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-icon svg {
        width: 40px;
        height: 40px;
        color: var(--primary);
    }

    .empty-state h2 {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 8px;
    }

    .empty-state p {
        color: var(--text-muted);
        margin: 0 0 24px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-primary-lg {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        background: var(--primary);
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
    }

    .btn-primary-lg svg {
        width: 20px;
        height: 20px;
    }

    /* Table */
    .services-table-wrapper {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .services-table {
        width: 100%;
        border-collapse: collapse;
    }

    .services-table th {
        text-align: left;
        padding: 14px 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        background: var(--bg-input);
        border-bottom: 1px solid var(--border-color);
    }

    .services-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .services-table tr:last-child td {
        border-bottom: none;
    }

    .services-table tr:hover {
        background: var(--bg-input);
    }

    /* App Info */
    .app-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .app-icon-sm {
        width: 36px;
        height: 36px;
        background: var(--bg-input);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
    }

    .app-icon-sm img {
        width: 24px;
        height: 24px;
    }

    .app-name {
        font-weight: 600;
        color: var(--text-primary);
    }

    .app-id {
        font-size: 11px;
        color: var(--text-muted);
    }

    /* Runtime Badge */
    .runtime-badge {
        font-size: 12px;
        color: var(--text-primary);
        background: var(--bg-input);
        padding: 4px 10px;
        border-radius: 4px;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 500;
        padding: 4px 10px;
        border-radius: 12px;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }

    .status-running {
        background: rgba(76, 175, 80, 0.15);
        color: #4CAF50;
    }

    .status-running .status-dot {
        background: #4CAF50;
    }

    .status-stopped {
        background: rgba(158, 158, 158, 0.15);
        color: #9E9E9E;
    }

    .status-creating {
        background: rgba(255, 152, 0, 0.15);
        color: #FF9800;
    }

    .status-creating .status-dot {
        background: #FF9800;
    }

    .status-error {
        background: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }

    .status-suspended {
        background: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.4;
        }
    }

    /* Domain Link */
    .domain-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--primary);
        text-decoration: none;
        font-size: 13px;
    }

    .domain-link:hover {
        text-decoration: underline;
    }

    .domain-link svg {
        width: 12px;
        height: 12px;
    }

    .no-domain {
        color: var(--text-muted);
        font-size: 13px;
    }

    /* Resources */
    .resource-info {
        display: flex;
        gap: 12px;
        font-size: 12px;
        color: var(--text-muted);
    }

    /* Actions */
    .action-buttons {
        display: flex;
        gap: 6px;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-action:hover {
        background: var(--bg-body);
    }

    .btn-action svg {
        width: 16px;
        height: 16px;
    }

    .btn-action.btn-danger {
        color: #f44336;
    }

    .btn-action.btn-danger:hover {
        background: rgba(244, 67, 54, 0.1);
    }

    .btn-action.btn-success {
        color: #4CAF50;
    }

    .btn-action.btn-success:hover {
        background: rgba(76, 175, 80, 0.1);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    async function serviceAction(id, action) {
        const btn = event.currentTarget;
        btn.disabled = true;
        btn.style.opacity = '0.5';

        try {
            const response = await fetch(`<?= $base_url ?>/services/${id}/${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'Action failed',
                    background: getComputedStyle(document.body).getPropertyValue('--bg-card').trim(),
                    color: getComputedStyle(document.body).getPropertyValue('--text-primary').trim()
                });
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>