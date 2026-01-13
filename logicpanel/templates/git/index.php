<?php
$page_title = 'Git Deploy - ' . ($service->name ?? 'Unknown');
$current_page = 'git';
ob_start();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Git Deployment</h1>
            <p class="text-sm text-[var(--text-secondary)]">
                <?= htmlspecialchars($service->name) ?>
            </p>
        </div>
    </div>

    <button onclick="deploy()" id="deployBtn"
        class="flex items-center justify-center gap-2 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition-colors <?= empty($service->github_repo) ? 'opacity-50 cursor-not-allowed' : '' ?>"
        <?= empty($service->github_repo) ? 'disabled' : '' ?>>
        <i data-lucide="rocket" class="w-5 h-5"></i>
        <span>Deploy Now</span>
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - Config -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Repository Configuration -->
        <div class="card rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <i data-lucide="git-branch" class="w-5 h-5 text-purple-500"></i>
                Repository Configuration
            </h2>

            <form id="gitConfigForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Repository URL</label>
                    <input type="url" name="github_repo" value="<?= htmlspecialchars($service->github_repo ?? '') ?>"
                        placeholder="https://github.com/username/repo.git"
                        class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                    <p class="text-xs text-[var(--text-secondary)] mt-1">Supports GitHub, GitLab, and Bitbucket</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Branch</label>
                        <input type="text" name="github_branch"
                            value="<?= htmlspecialchars($service->github_branch ?? 'main') ?>" placeholder="main"
                            class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Personal Access Token (for private repos)</label>
                        <input type="password" name="github_pat"
                            placeholder="<?= $service->github_pat ? '••••••••••' : 'ghp_xxxxxxxxxxxx' ?>"
                            class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                    </div>
                </div>

                <hr class="border-[var(--border-color)]">

                <h3 class="text-md font-medium">Build Commands</h3>

                <div>
                    <label class="block text-sm font-medium mb-2">Install Command</label>
                    <input type="text" name="install_cmd"
                        value="<?= htmlspecialchars($service->install_cmd ?? 'npm install') ?>"
                        placeholder="npm install"
                        class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg font-mono text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Build Command (optional)</label>
                    <input type="text" name="build_cmd" value="<?= htmlspecialchars($service->build_cmd ?? '') ?>"
                        placeholder="npm run build"
                        class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg font-mono text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Start Command</label>
                    <input type="text" name="start_cmd"
                        value="<?= htmlspecialchars($service->start_cmd ?? 'npm start') ?>" placeholder="npm start"
                        class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border-color)] rounded-lg font-mono text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-colors">
                </div>

                <button type="submit"
                    class="w-full sm:w-auto px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg transition-colors">
                    Save Configuration
                </button>
            </form>
        </div>

        <!-- Deployment Logs -->
        <div class="card rounded-xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i data-lucide="scroll" class="w-5 h-5 text-gray-500"></i>
                    Deployment Logs
                </h2>
                <button onclick="refreshDeploymentLogs()"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <div id="deploymentLogs"
                class="bg-gray-950 rounded-lg p-4 h-48 sm:h-64 overflow-auto font-mono text-sm text-gray-300">
                <p class="text-gray-500">No active deployment...</p>
            </div>
        </div>
    </div>

    <!-- Right Column - History -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="card rounded-xl p-5">
            <h2 class="text-lg font-semibold mb-4">Current Status</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--text-secondary)]">Repository</span>
                    <span class="text-sm font-medium truncate max-w-[150px]"
                        title="<?= htmlspecialchars($service->github_repo ?? 'Not configured') ?>">
                        <?= $service->github_repo ? basename($service->github_repo, '.git') : 'Not configured' ?>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--text-secondary)]">Branch</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                        <?= htmlspecialchars($service->github_branch ?? 'main') ?>
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[var(--text-secondary)]">Private Repo</span>
                    <span class="text-sm">
                        <?= $service->github_pat ? '✓ Token Set' : '✗ No Token' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Deployment History -->
        <div class="card rounded-xl p-5">
            <h2 class="text-lg font-semibold mb-4">Deployment History</h2>

            <?php if (empty($deployments) || count($deployments) === 0): ?>
                <p class="text-sm text-[var(--text-secondary)]">No deployments yet</p>
            <?php else: ?>
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    <?php foreach ($deployments as $deploy): ?>
                        <div class="p-3 bg-[var(--bg-secondary)] rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <?php
                                $statusColors = [
                                    'completed' => 'text-green-500',
                                    'failed' => 'text-red-500',
                                    'pending' => 'text-yellow-500',
                                    'installing' => 'text-blue-500',
                                    'building' => 'text-purple-500',
                                    'cloning' => 'text-cyan-500',
                                ];
                                $color = $statusColors[$deploy->status] ?? 'text-gray-500';
                                ?>
                                <span class="text-sm font-medium <?= $color ?> capitalize">
                                    <?= $deploy->status ?>
                                </span>
                                <span class="text-xs text-[var(--text-secondary)]">
                                    <?= date('M d, H:i', strtotime($deploy->created_at)) ?>
                                </span>
                            </div>
                            <?php if ($deploy->commit_hash): ?>
                                <div class="flex items-center gap-2 text-xs">
                                    <code
                                        class="bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded"><?= $deploy->getShortHash() ?></code>
                                    <span class="text-[var(--text-secondary)] truncate">
                                        <?= htmlspecialchars(substr($deploy->commit_message ?? '', 0, 30)) ?>
                                        <?= strlen($deploy->commit_message ?? '') > 30 ? '...' : '' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if ($deploy->getDuration()): ?>
                                <p class="text-xs text-[var(--text-secondary)] mt-1">Duration:
                                    <?= $deploy->getDuration() ?>s
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const serviceId = <?= $service->id ?>;
    let deploymentPolling = null;

    document.getElementById('gitConfigForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`<?= $base_url ?>/git/${serviceId}/config`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                alert('Configuration saved!');
                location.reload();
            } else {
                alert(result.error || 'Save failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    async function deploy() {
        const btn = document.getElementById('deployBtn');
        if (btn.disabled) return;

        if (!confirm('Start deployment from Git repository?')) return;

        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Deploying...</span>';
        lucide.createIcons();

        const logsContainer = document.getElementById('deploymentLogs');
        logsContainer.innerHTML = '<p class="text-cyan-400">Starting deployment...</p>';

        try {
            const response = await fetch(`<?= $base_url ?>/git/${serviceId}/deploy`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();

            if (result.success) {
                logsContainer.innerHTML += `<p class="text-green-400">Deployment completed successfully!</p>`;
                setTimeout(() => location.reload(), 2000);
            } else {
                logsContainer.innerHTML += `<p class="text-red-400">Error: ${result.error}</p>`;
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="rocket" class="w-5 h-5"></i><span>Deploy Now</span>';
                lucide.createIcons();
            }
        } catch (error) {
            logsContainer.innerHTML += `<p class="text-red-400">Error: ${error.message}</p>`;
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="rocket" class="w-5 h-5"></i><span>Deploy Now</span>';
            lucide.createIcons();
        }
    }

    function refreshDeploymentLogs() {
        const container = document.getElementById('deploymentLogs');
        container.innerHTML = '<p class="text-gray-500">Loading...</p>';
        // In production, this would poll for real-time updates
        setTimeout(() => {
            container.innerHTML = '<p class="text-gray-500">No active deployment...</p>';
        }, 500);
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>