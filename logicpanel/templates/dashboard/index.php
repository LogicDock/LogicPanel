<?php
$page_title = 'Tools';
$current_page = 'dashboard';
ob_start();
?>

<div class="tools-layout">
    <!-- Main Tools Area -->
    <div class="tools-main">

        <!-- Services Section -->
        <div class="tools-section">
            <div class="tools-section-header" onclick="toggleSection(this)">
                <div class="tools-section-icon" style="background: rgba(60, 135, 58, 0.15); color: var(--primary);">
                    <i data-lucide="box"></i>
                </div>
                <span class="tools-section-title">Services</span>
                <i data-lucide="chevron-up" class="tools-section-toggle"></i>
            </div>
            <div class="tools-section-body">
                <div class="tools-grid">
                    <?php if (empty($services)): ?>
                        <a href="#" class="tool-item"
                            onclick="alert('No services available. Services will be created via WHMCS.'); return false;">
                            <div class="tool-icon">
                                <i data-lucide="plus-circle"></i>
                            </div>
                            <span class="tool-name">No Services</span>
                        </a>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="tool-item">
                                <div
                                    class="tool-icon <?= $service->status === 'running' ? 'status-running' : 'status-stopped' ?>">
                                    <i data-lucide="server"></i>
                                </div>
                                <span class="tool-name"><?= htmlspecialchars($service->name) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Domains & Databases Section -->
        <div class="tools-section">
            <div class="tools-section-header" onclick="toggleSection(this)">
                <div class="tools-section-icon" style="background: rgba(33, 150, 243, 0.15); color: #2196F3;">
                    <i data-lucide="globe"></i>
                </div>
                <span class="tools-section-title">Domains & Databases</span>
                <i data-lucide="chevron-up" class="tools-section-toggle"></i>
            </div>
            <div class="tools-section-body">
                <div class="tools-grid">
                    <a href="<?= $base_url ?>/domains" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="globe"></i>
                        </div>
                        <span class="tool-name">Domains</span>
                    </a>
                    <a href="<?= $base_url ?>/databases" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="database"></i>
                        </div>
                        <span class="tool-name">Database</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Files Section -->
        <div class="tools-section">
            <div class="tools-section-header" onclick="toggleSection(this)">
                <div class="tools-section-icon" style="background: rgba(255, 152, 0, 0.15); color: #FF9800;">
                    <i data-lucide="folder"></i>
                </div>
                <span class="tools-section-title">Files</span>
                <i data-lucide="chevron-up" class="tools-section-toggle"></i>
            </div>
            <div class="tools-section-body">
                <div class="tools-grid">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <a href="<?= $base_url ?>/files/<?= $service->id ?>" class="tool-item">
                                <div class="tool-icon">
                                    <i data-lucide="folder-open"></i>
                                </div>
                                <span class="tool-name"><?= htmlspecialchars($service->name) ?> Files</span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="#" class="tool-item" onclick="alert('Create a service first'); return false;">
                            <div class="tool-icon">
                                <i data-lucide="folder-open"></i>
                            </div>
                            <span class="tool-name">File Manager</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= $base_url ?>/backups" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="archive"></i>
                        </div>
                        <span class="tool-name">Backups</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Development Tools Section -->
        <div class="tools-section">
            <div class="tools-section-header" onclick="toggleSection(this)">
                <div class="tools-section-icon" style="background: rgba(156, 39, 176, 0.15); color: #9C27B0;">
                    <i data-lucide="code"></i>
                </div>
                <span class="tools-section-title">Development Tools</span>
                <i data-lucide="chevron-up" class="tools-section-toggle"></i>
            </div>
            <div class="tools-section-body">
                <div class="tools-grid">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <a href="<?= $base_url ?>/terminal/<?= $service->id ?>" class="tool-item">
                                <div class="tool-icon">
                                    <i data-lucide="terminal"></i>
                                </div>
                                <span class="tool-name">Terminal</span>
                            </a>
                            <a href="<?= $base_url ?>/git/<?= $service->id ?>" class="tool-item">
                                <div class="tool-icon">
                                    <i data-lucide="git-branch"></i>
                                </div>
                                <span class="tool-name">Git Deploy</span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="#" class="tool-item" onclick="alert('Create a service first'); return false;">
                            <div class="tool-icon">
                                <i data-lucide="terminal"></i>
                            </div>
                            <span class="tool-name">Terminal</span>
                        </a>
                        <a href="#" class="tool-item" onclick="alert('Create a service first'); return false;">
                            <div class="tool-icon">
                                <i data-lucide="git-branch"></i>
                            </div>
                            <span class="tool-name">Git Deploy</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="tools-section">
            <div class="tools-section-header" onclick="toggleSection(this)">
                <div class="tools-section-icon" style="background: rgba(96, 125, 139, 0.15); color: #607D8B;">
                    <i data-lucide="settings"></i>
                </div>
                <span class="tools-section-title">Preferences</span>
                <i data-lucide="chevron-up" class="tools-section-toggle"></i>
            </div>
            <div class="tools-section-body">
                <div class="tools-grid">
                    <a href="<?= $base_url ?>/settings" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="user"></i>
                        </div>
                        <span class="tool-name">Profile</span>
                    </a>
                    <a href="<?= $base_url ?>/settings" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="palette"></i>
                        </div>
                        <span class="tool-name">Theme</span>
                    </a>
                    <a href="<?= $base_url ?>/logout" class="tool-item">
                        <div class="tool-icon">
                            <i data-lucide="log-out"></i>
                        </div>
                        <span class="tool-name">Logout</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Sidebar - General Information -->
    <div class="tools-sidebar">
        <div class="info-card">
            <div class="info-card-header">General Information</div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Current User</div>
                    <div class="info-value"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                </div>

                <?php if (!empty($services) && $services->first()): ?>
                    <div class="info-item">
                        <div class="info-label">Primary Service</div>
                        <div class="info-value">
                            <a
                                href="<?= $base_url ?>/services/<?= $services->first()->id ?>"><?= htmlspecialchars($services->first()->name) ?></a>
                        </div>
                    </div>

                    <?php if ($services->first()->primaryDomain): ?>
                        <div class="info-item">
                            <div class="info-label">Primary Domain</div>
                            <div class="info-value">
                                <a href="https://<?= htmlspecialchars($services->first()->primaryDomain->domain) ?>"
                                    target="_blank">
                                    <?= htmlspecialchars($services->first()->primaryDomain->domain) ?> <i
                                        data-lucide="external-link" style="width:12px;height:12px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="info-item">
                    <div class="info-label">Container Status</div>
                    <div class="info-value">
                        <span class="badge badge-success">
                            <span class="badge-dot"></span>
                            <?= $runningCount ?? 0 ?> Running
                        </span>
                        <?php if (($stoppedCount ?? 0) > 0): ?>
                            <span class="badge badge-secondary" style="margin-left: 5px;">
                                <?= $stoppedCount ?> Stopped
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Total Services</div>
                    <div class="info-value"><?= $serviceCount ?? 0 ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Last Login IP</div>
                    <div class="info-value"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown') ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Server Time</div>
                    <div class="info-value"><?= date('Y-m-d H:i:s') ?></div>
                </div>
            </div>
        </div>

        <!-- Resource Usage -->
        <?php if (!empty($totalStats) && $totalStats['memory_limit'] > 0): ?>
            <div class="info-card">
                <div class="info-card-header">Resource Usage</div>
                <div class="info-card-body">
                    <div class="resource-item">
                        <div class="resource-header">
                            <span>CPU Usage</span>
                            <span><?= $totalStats['cpu'] ?? 0 ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= min($totalStats['cpu'] ?? 0, 100) ?>%"></div>
                        </div>
                    </div>

                    <div class="resource-item">
                        <div class="resource-header">
                            <span>Memory Usage</span>
                            <span><?= round(($totalStats['memory_used'] ?? 0) / 1024 / 1024) ?>MB</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar"
                                style="width: <?= $totalStats['memory_limit'] > 0 ? min(($totalStats['memory_used'] / $totalStats['memory_limit']) * 100, 100) : 0 ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* cPanel Style Tools Layout */
    .tools-layout {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 20px;
        align-items: start;
    }

    .tools-main {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* Tools Section - Collapsible */
    .tools-section {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .tools-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        cursor: pointer;
        transition: background 0.15s ease;
        user-select: none;
    }

    .tools-section-header:hover {
        background: var(--bg-input);
    }

    .tools-section-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tools-section-icon svg {
        width: 18px;
        height: 18px;
    }

    .tools-section-title {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .tools-section-toggle {
        width: 18px;
        height: 18px;
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .tools-section.collapsed .tools-section-toggle {
        transform: rotate(180deg);
    }

    .tools-section-body {
        border-top: 1px solid var(--border-color);
        padding: 15px;
    }

    .tools-section.collapsed .tools-section-body {
        display: none;
    }

    /* Tools Grid - cPanel Icon Style */
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }

    .tool-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 15px 10px;
        border-radius: var(--border-radius);
        text-decoration: none;
        color: var(--text-primary);
        transition: all 0.15s ease;
        border: 1px solid transparent;
    }

    .tool-item:hover {
        background: var(--bg-input);
        text-decoration: none;
    }

    .tool-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        background: rgba(60, 135, 58, 0.12);
        color: var(--primary);
        transition: all 0.15s ease;
    }

    .tool-item:hover .tool-icon {
        background: var(--primary);
        color: white;
    }

    .tool-icon svg {
        width: 24px;
        height: 24px;
    }

    .tool-icon.status-running {
        background: rgba(76, 175, 80, 0.15);
        color: #4CAF50;
    }

    .tool-icon.status-stopped {
        background: rgba(158, 158, 158, 0.15);
        color: #9E9E9E;
    }

    .tool-name {
        font-size: 12px;
        font-weight: 500;
        line-height: 1.3;
    }

    /* Right Sidebar Info Cards */
    .tools-sidebar {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .info-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .info-card-header {
        padding: 12px 15px;
        background: var(--bg-input);
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
    }

    .info-card-body {
        padding: 0;
    }

    .info-item {
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 13px;
        color: var(--text-primary);
        font-weight: 500;
    }

    .info-value a {
        color: var(--primary);
    }

    .resource-item {
        padding: 10px 15px;
    }

    .resource-header {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 6px;
    }

    /* Mobile Responsive */
    @media (max-width: 991px) {
        .tools-layout {
            grid-template-columns: 1fr;
        }

        .tools-main {
            order: 0;
        }

        .tools-sidebar {
            order: 1;
        }

        .tools-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        }
    }
</style>

<script>
    function toggleSection(header) {
        const section = header.closest('.tools-section');
        section.classList.toggle('collapsed');
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>