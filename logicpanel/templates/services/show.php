<?php
$page_title = 'Service Details - ' . ($service->name ?? 'Unknown');
$current_page = 'services';
ob_start();
?>

<!-- Back Button & Actions -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $base_url ?>/services"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">
                <?= htmlspecialchars($service->name) ?>
            </h1>
            <p class="text-sm text-[var(--text-secondary)]">
                <?= htmlspecialchars($service->primaryDomain?->domain ?? 'No domain') ?>
            </p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex items-center gap-2 flex-wrap">
        <?php if ($service->status === 'running'): ?>
            <button onclick="serviceAction('stop')"
                class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="square" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Stop</span>
            </button>
            <button onclick="serviceAction('restart')"
                class="flex items-center gap-2 px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Restart</span>
            </button>
        <?php elseif ($service->status === 'stopped'): ?>
            <button onclick="serviceAction('start')"
                class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors">
                <i data-lucide="play" class="w-4 h-4"></i>
                <span class="hidden sm:inline">Start</span>
            </button>
        <?php endif; ?>

        <button onclick="serviceAction('rebuild')"
            class="flex items-center gap-2 px-4 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-600 rounded-lg text-sm font-medium transition-colors">
            <i data-lucide="wrench" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Rebuild</span>
        </button>
    </div>
</div>

<!-- Status Banner -->
<?php if ($service->isSuspended()): ?>
    <div
        class="mb-6 p-4 bg-red-100 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 text-red-700 dark:text-red-400">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <div>
            <p class="font-medium">This service is suspended</p>
            <p class="text-sm opacity-80">Please contact support or renew your subscription.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Cards (Mobile: 2 cols, Desktop: 4 cols) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="card stat-card rounded-xl p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <i data-lucide="cpu" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
            </div>
            <span class="text-xl sm:text-2xl font-bold text-blue-600">
                <?= number_format($containerStats['cpu'] ?? 0, 1) ?>%
            </span>
        </div>
        <p class="text-xs sm:text-sm text-[var(--text-secondary)]">CPU Usage</p>
    </div>

    <div class="card stat-card rounded-xl p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <i data-lucide="memory-stick" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
            </div>
            <span class="text-xl sm:text-2xl font-bold text-purple-600">
                <?= number_format($containerStats['memory_percent'] ?? 0, 1) ?>%
            </span>
        </div>
        <p class="text-xs sm:text-sm text-[var(--text-secondary)]">Memory</p>
    </div>

    <div class="card stat-card rounded-xl p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <i data-lucide="download" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
            </div>
            <span class="text-lg sm:text-xl font-bold text-green-600">
                <?= $containerStats['network_rx_human'] ?? '0 B' ?>
            </span>
        </div>
        <p class="text-xs sm:text-sm text-[var(--text-secondary)]">Network In</p>
    </div>

    <div class="card stat-card rounded-xl p-4 sm:p-5">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                <i data-lucide="upload" class="w-5 h-5 text-orange-600 dark:text-orange-400"></i>
            </div>
            <span class="text-lg sm:text-xl font-bold text-orange-600">
                <?= $containerStats['network_tx_human'] ?? '0 B' ?>
            </span>
        </div>
        <p class="text-xs sm:text-sm text-[var(--text-secondary)]">Network Out</p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Quick Actions Grid -->
        <div class="card rounded-xl p-5">
            <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="<?= $base_url ?>/terminal/<?= $service->id ?>"
                    class="flex flex-col items-center gap-2 p-4 bg-[var(--bg-secondary)] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <i data-lucide="terminal" class="w-6 h-6 text-blue-500"></i>
                    <span class="text-sm font-medium">Terminal</span>
                </a>
                <a href="<?= $base_url ?>/files/<?= $service->id ?>"
                    class="flex flex-col items-center gap-2 p-4 bg-[var(--bg-secondary)] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <i data-lucide="folder" class="w-6 h-6 text-yellow-500"></i>
                    <span class="text-sm font-medium">Files</span>
                </a>
                <a href="<?= $base_url ?>/git/<?= $service->id ?>"
                    class="flex flex-col items-center gap-2 p-4 bg-[var(--bg-secondary)] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <i data-lucide="git-branch" class="w-6 h-6 text-purple-500"></i>
                    <span class="text-sm font-medium">Git Deploy</span>
                </a>
                <a href="<?= $base_url ?>/services/<?= $service->id ?>/env"
                    class="flex flex-col items-center gap-2 p-4 bg-[var(--bg-secondary)] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    <i data-lucide="settings" class="w-6 h-6 text-gray-500"></i>
                    <span class="text-sm font-medium">Env Vars</span>
                </a>
            </div>
        </div>

        <!-- Logs Section -->
        <div class="card rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Container Logs</h2>
                <button onclick="refreshLogs()"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <div id="logsContainer" class="terminal rounded-lg p-4 h-64 sm:h-80 overflow-auto text-sm font-mono">
                <p class="text-gray-500">Loading logs...</p>
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="card rounded-xl p-5">
            <h2 class="text-lg font-semibold mb-4">Recent Deployments</h2>
            <?php if ($service->deployments->isEmpty()): ?>
                <p class="text-[var(--text-secondary)] text-sm">No deployments yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($service->deployments->take(5) as $deploy): ?>
                        <div class="flex items-center gap-3 p-3 bg-[var(--bg-secondary)] rounded-lg">
                            <?php
                            $statusColors = [
                                'completed' => 'bg-green-500',
                                'failed' => 'bg-red-500',
                                'pending' => 'bg-yellow-500',
                                'installing' => 'bg-blue-500',
                            ];
                            $color = $statusColors[$deploy->status] ?? 'bg-gray-500';
                            ?>
                            <div class="w-2 h-2 rounded-full <?= $color ?>"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">
                                    <?= htmlspecialchars($deploy->commit_message ?? 'No message') ?>
                                </p>
                                <p class="text-xs text-[var(--text-secondary)]">
                                    <code
                                        class="bg-gray-200 dark:bg-gray-700 px-1 rounded"><?= $deploy->getShortHash() ?: 'N/A' ?></code>
                                    &middot;
                                    <?= date('M d, H:i', strtotime($deploy->created_at)) ?>
                                </p>
                            </div>
                            <span
                                class="text-xs font-medium capitalize <?= $deploy->status === 'completed' ? 'text-green-600' : ($deploy->status === 'failed' ? 'text-red-600' : '') ?>">
                                <?= $deploy->status ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column (1/3) -->
    <div class="space-y-6">
        <!-- Service Info -->
        <div class="card rounded-xl p-5">
            <h2 class="text-lg font-semibold mb-4">Service Info</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-xs text-[var(--text-secondary)] mb-1">Status</p>
                    <span
                        class="inline-flex items-center gap-1.5 px-2 py-1 <?= $service->status === 'running' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' ?> rounded-lg text-sm font-medium">
                        <?php if ($service->status === 'running'): ?>
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <?php endif; ?>
                        <?= ucfirst($service->status) ?>
                    </span>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-secondary)] mb-1">Node Version</p>
                    <p class="font-medium">v
                        <?= htmlspecialchars($service->node_version) ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-secondary)] mb-1">Port</p>
                    <p class="font-medium">
                        <?= $service->port ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-secondary)] mb-1">Plan</p>
                    <p class="font-medium capitalize">
                        <?= htmlspecialchars($service->plan) ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[var(--text-secondary)] mb-1">Created</p>
                    <p class="font-medium">
                        <?= date('M d, Y', strtotime($service->created_at)) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Domains -->
        <div class="card rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Domains</h2>
                <a href="<?= $base_url ?>/domains/<?= $service->id ?>"
                    class="text-primary-500 hover:text-primary-600 text-sm">Manage</a>
            </div>
            <div class="space-y-2">
                <?php foreach ($service->domains as $domain): ?>
                    <div class="flex items-center gap-2 p-2 bg-[var(--bg-secondary)] rounded-lg">
                        <i data-lucide="globe" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <span class="text-sm truncate flex-1">
                            <?= htmlspecialchars($domain->domain) ?>
                        </span>
                        <?php if ($domain->is_primary): ?>
                            <span
                                class="text-xs bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400 px-2 py-0.5 rounded">Primary</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Databases -->
        <div class="card rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Databases</h2>
                <a href="<?= $base_url ?>/databases/<?= $service->id ?>"
                    class="text-primary-500 hover:text-primary-600 text-sm">Manage</a>
            </div>
            <?php if ($service->databases->isEmpty()): ?>
                <p class="text-sm text-[var(--text-secondary)]">No databases created.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($service->databases as $db): ?>
                        <div class="flex items-center gap-2 p-2 bg-[var(--bg-secondary)] rounded-lg">
                            <i data-lucide="database" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                            <span class="text-sm truncate flex-1">
                                <?= htmlspecialchars($db->db_name) ?>
                            </span>
                            <span class="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded uppercase">
                                <?= $db->type ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Backups -->
        <div class="card rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Backups</h2>
                <a href="<?= $base_url ?>/backups/<?= $service->id ?>"
                    class="text-primary-500 hover:text-primary-600 text-sm">View All</a>
            </div>
            <button onclick="createBackup()"
                class="w-full flex items-center justify-center gap-2 p-3 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-[var(--text-secondary)] hover:border-primary-500 hover:text-primary-500 transition-colors">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>Create Backup</span>
            </button>
        </div>
    </div>
</div>

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
        container.innerHTML = '<p class="text-gray-500">Loading...</p>';

        try {
            const response = await fetch(`<?= $base_url ?>/services/${serviceId}/logs?tail=100`);
            const data = await response.json();

            if (data.success) {
                container.innerHTML = data.logs ? `<pre class="whitespace-pre-wrap break-words">${escapeHtml(data.logs)}</pre>` : '<p class="text-gray-500">No logs available</p>';
                container.scrollTop = container.scrollHeight;
            } else {
                container.innerHTML = `<p class="text-red-500">${data.error || 'Failed to load logs'}</p>`;
            }
        } catch (error) {
            container.innerHTML = `<p class="text-red-500">Error: ${error.message}</p>`;
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