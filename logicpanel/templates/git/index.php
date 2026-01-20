<?php
$page_title = 'Git Deploy - ' . ($service->name ?? 'Unknown');
$current_page = 'git';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title">Git Deployment</h1>
            <p class="page-subtitle"><?= htmlspecialchars($service->name) ?></p>
        </div>
    </div>
    <div class="page-header-actions">
        <button onclick="deploy()" class="btn btn-primary" id="deployBtn" <?= empty($service->github_repo) ? 'disabled' : '' ?>>
            <i data-lucide="rocket"></i> Deploy Now
        </button>
    </div>
</div>

<div class="git-layout">
    <!-- Left Column - Config -->
    <div class="git-main">
        <!-- Repository Configuration -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="git-branch"></i> Repository Configuration
                </h2>
            </div>
            <div class="card-body">
                <form id="gitConfigForm">
                    <div class="form-group">
                        <label>Repository URL</label>
                        <input type="text" name="github_repo" class="form-control"
                            value="<?= htmlspecialchars($service->github_repo ?? '') ?>"
                            placeholder="https://github.com/username/repo">
                        <p class="form-hint">Supports GitHub, GitLab, and Bitbucket</p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Branch</label>
                            <input type="text" name="github_branch" class="form-control"
                                value="<?= htmlspecialchars($service->github_branch ?? 'main') ?>" placeholder="main">
                        </div>
                        <div class="form-group">
                            <label>Personal Access Token (for private repos)</label>
                            <input type="password" name="github_pat" class="form-control"
                                value="<?= $service->github_pat ? '••••••••••••••••' : '' ?>"
                                placeholder="ghp_xxxxxxxxxxxx">
                        </div>
                    </div>

                    <div class="form-divider"></div>

                    <h3 class="section-subtitle">Build Commands</h3>

                    <div class="form-group">
                        <label>Install Command</label>
                        <input type="text" name="install_cmd" class="form-control code-input"
                            value="<?= htmlspecialchars($service->install_cmd ?? 'npm install') ?>">
                    </div>

                    <div class="form-group">
                        <label>Build Command <span class="optional">(optional)</span></label>
                        <input type="text" name="build_cmd" class="form-control code-input"
                            value="<?= htmlspecialchars($service->build_cmd ?? '') ?>" placeholder="npm run build">
                    </div>

                    <div class="form-group">
                        <label>Start Command</label>
                        <input type="text" name="start_cmd" class="form-control code-input"
                            value="<?= htmlspecialchars($service->start_cmd ?? 'npm start') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> Save Configuration
                    </button>
                </form>
            </div>
        </div>

        <!-- Deployment Logs -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="terminal"></i> Deployment Logs
                </h2>
                <button onclick="refreshLogs()" class="btn btn-secondary btn-sm">
                    <i data-lucide="refresh-cw"></i>
                </button>
            </div>
            <div class="card-body">
                <div id="deploymentLogs" class="logs-container">
                    <p class="log-muted">No active deployment...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Status & History -->
    <div class="git-sidebar">
        <!-- Status Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Current Status</h2>
            </div>
            <div class="card-body">
                <div class="status-list">
                    <div class="status-item">
                        <span class="status-label">Repository</span>
                        <span class="status-value <?= $service->github_repo ? 'configured' : 'not-configured' ?>">
                            <?= $service->github_repo ? '✓ Configured' : '✗ Not configured' ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Branch</span>
                        <span class="status-value"><?= htmlspecialchars($service->github_branch ?? 'main') ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Private Repo</span>
                        <span class="status-value <?= $service->github_pat ? 'configured' : 'not-configured' ?>">
                            <?= $service->github_pat ? '✓ Token Set' : '✗ No Token' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deployment History -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Deployment History</h2>
            </div>
            <div class="card-body">
                <?php if (empty($deployments) || count($deployments) === 0): ?>
                    <p class="text-muted">No deployments yet</p>
                <?php else: ?>
                    <div class="deployment-list">
                        <?php foreach ($deployments as $deploy): ?>
                            <?php
                            $statusClass = match ($deploy->status) {
                                'success' => 'success',
                                'failed' => 'failed',
                                'running' => 'running',
                                default => 'pending'
                            };
                            ?>
                            <div class="deployment-item">
                                <div class="deployment-header">
                                    <span class="deployment-status <?= $statusClass ?>">
                                        <?= ucfirst($deploy->status) ?>
                                    </span>
                                    <span class="deployment-date">
                                        <?= date('M d, H:i', strtotime($deploy->created_at)) ?>
                                    </span>
                                </div>
                                <?php if ($deploy->commit_sha): ?>
                                    <div class="deployment-commit">
                                        <i data-lucide="git-commit"></i>
                                        <code><?= substr($deploy->commit_sha, 0, 7) ?></code>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
        margin-bottom: 24px;
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

    /* Layout */
    .git-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 24px;
    }

    .git-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .git-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Card Header Fix */
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .card-title svg {
        width: 18px;
        height: 18px;
        color: var(--primary);
    }

    .card-body {
        padding: 20px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--text-primary);
    }

    .form-group .optional {
        color: var(--text-muted);
        font-weight: 400;
    }

    .form-hint {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 6px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-divider {
        height: 1px;
        background: var(--border-color);
        margin: 20px 0;
    }

    .section-subtitle {
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 16px 0;
        color: var(--text-primary);
    }

    .code-input {
        font-family: 'Consolas', 'Monaco', monospace;
    }

    /* Logs */
    .logs-container {
        background: #0d1117;
        border-radius: 8px;
        padding: 16px;
        min-height: 200px;
        max-height: 300px;
        overflow-y: auto;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        line-height: 1.6;
        color: #c9d1d9;
    }

    .log-muted {
        color: #6e7681;
        margin: 0;
    }

    .log-success {
        color: #3fb950;
    }

    .log-error {
        color: #f85149;
    }

    .log-warning {
        color: #d29922;
    }

    /* Status List */
    .status-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .status-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .status-label {
        font-size: 13px;
        color: var(--text-secondary);
    }

    .status-value {
        font-size: 13px;
        font-weight: 500;
    }

    .status-value.configured {
        color: #22c55e;
    }

    .status-value.not-configured {
        color: #ef4444;
    }

    /* Deployment List */
    .deployment-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 350px;
        overflow-y: auto;
    }

    .deployment-item {
        padding: 12px;
        background: var(--bg-input);
        border-radius: 8px;
    }

    .deployment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .deployment-status {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 4px;
    }

    .deployment-status.success {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }

    .deployment-status.failed {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .deployment-status.running {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .deployment-status.pending {
        background: rgba(234, 179, 8, 0.15);
        color: #eab308;
    }

    .deployment-date {
        font-size: 12px;
        color: var(--text-muted);
    }

    .deployment-commit {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-secondary);
    }

    .deployment-commit svg {
        width: 14px;
        height: 14px;
    }

    .deployment-commit code {
        font-family: 'Consolas', 'Monaco', monospace;
        background: var(--bg-card);
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* Utilities */
    .text-muted {
        color: var(--text-muted);
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-sm svg {
        width: 14px;
        height: 14px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .git-layout {
            grid-template-columns: 1fr;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const serviceId = <?= $service->id ?>;
    let isDeploying = false;

    // SweetAlert2 theme helper
    function getSwalTheme() {
        return {
            background: getComputedStyle(document.body).getPropertyValue('--bg-card').trim(),
            color: getComputedStyle(document.body).getPropertyValue('--text-primary').trim()
        };
    }

    document.getElementById('gitConfigForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Don't send placeholder password
        if (data.github_pat === '••••••••••••••••') {
            delete data.github_pat;
        }

        try {
            const response = await fetch(`<?= $base_url ?>/git/${serviceId}/config`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Configuration saved successfully',
                    timer: 1500,
                    showConfirmButton: false,
                    ...getSwalTheme()
                }).then(() => {
                    document.getElementById('deployBtn').disabled = false;
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.error || 'Failed to save',
                    ...getSwalTheme()
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                ...getSwalTheme()
            });
        }
    });

    async function deploy() {
        if (isDeploying) return;

        // No confirmation needed - start immediately
        isDeploying = true;
        const btn = document.getElementById('deployBtn');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Deploying...';
        lucide.createIcons();

        const logs = document.getElementById('deploymentLogs');
        logs.innerHTML = '<p class="log-warning">Starting deployment...</p>';

        try {
            const response = await fetch(`<?= $base_url ?? '' ?>/git/${serviceId}/deploy`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error('Server returned non-JSON response. Status: ' + response.status);
            }

            const data = await response.json();

            if (data.success) {
                logs.innerHTML += '<p class="log-success">Deployment started!</p>';
                pollDeploymentStatus();
            } else {
                logs.innerHTML += `<p class="log-error">Error: ${data.error || 'Deployment failed'}</p>`;
                resetDeployButton();
            }
        } catch (error) {
            logs.innerHTML += `<p class="log-error">Error: ${error.message}</p>`;
            resetDeployButton();
        }
    }

    function resetDeployButton() {
        isDeploying = false;
        const btn = document.getElementById('deployBtn');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="rocket"></i> Deploy Now';
        lucide.createIcons();
    }

    async function pollDeploymentStatus() {
        const logs = document.getElementById('deploymentLogs');

        try {
            const response = await fetch(`<?= $base_url ?>/git/${serviceId}/status`);
            const data = await response.json();

            if (data.logs) {
                logs.innerHTML = data.logs.split('\n').map(line => {
                    if (line.includes('error') || line.includes('Error')) {
                        return `<p class="log-error">${escapeHtml(line)}</p>`;
                    } else if (line.includes('success') || line.includes('complete')) {
                        return `<p class="log-success">${escapeHtml(line)}</p>`;
                    }
                    return `<p>${escapeHtml(line)}</p>`;
                }).join('');
            }

            if (data.status === 'running') {
                setTimeout(pollDeploymentStatus, 2000);
            } else {
                resetDeployButton();
                if (data.status === 'success') {
                    logs.innerHTML += '<p class="log-success">✓ Deployment completed successfully!</p>';
                }
            }
        } catch (error) {
            logs.innerHTML += `<p class="log-error">Status check failed</p>`;
            resetDeployButton();
        }

        logs.scrollTop = logs.scrollHeight;
    }

    function refreshLogs() {
        pollDeploymentStatus();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Add spin animation
    const style = document.createElement('style');
    style.textContent = `
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
`;
    document.head.appendChild(style);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>