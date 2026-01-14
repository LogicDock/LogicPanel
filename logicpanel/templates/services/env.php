<?php
$page_title = 'Environment Variables - ' . ($service->name ?? 'Service');
$current_page = 'tools';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title">Environment Variables</h1>
            <p class="page-subtitle">
                <?= htmlspecialchars($service->name) ?>
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <button onclick="addEnvVar()" class="btn btn-primary">
            <i data-lucide="plus"></i> Add Variable
        </button>
    </div>
</div>

<!-- Info Card -->
<div class="alert alert-info mb-20">
    <i data-lucide="info"></i>
    <div>
        <strong>Environment Variables</strong>
        <p>These variables will be available to your application at runtime. Changes require a container restart.</p>
    </div>
</div>

<!-- Env Variables Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="settings-2"></i> Variables
        </h2>
        <button onclick="saveEnvVars()" class="btn btn-success" id="saveBtn" disabled>
            <i data-lucide="save"></i> Save Changes
        </button>
    </div>
    <div class="card-body">
        <div id="envContainer">
            <?php
            $envVars = [];
            if (!empty($service->env_vars)) {
                $envVars = is_string($service->env_vars) ? json_decode($service->env_vars, true) : $service->env_vars;
            }
            if (empty($envVars)):
                ?>
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">
                        <i data-lucide="file-code"></i>
                    </div>
                    <h3 class="empty-state-title">No Environment Variables</h3>
                    <p class="empty-state-text">Add your first environment variable to get started.</p>
                    <button onclick="addEnvVar()" class="btn btn-primary">
                        <i data-lucide="plus"></i> Add Variable
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($envVars as $key => $value): ?>
                    <div class="env-row" data-key="<?= htmlspecialchars($key) ?>">
                        <div class="env-key">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($key) ?>" placeholder="KEY"
                                onchange="markChanged()">
                        </div>
                        <div class="env-value">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($value) ?>" placeholder="value"
                                onchange="markChanged()">
                            <button onclick="toggleVisibility(this)" class="btn btn-icon" title="Toggle visibility">
                                <i data-lucide="eye-off"></i>
                            </button>
                        </div>
                        <button onclick="removeEnvVar(this)" class="btn btn-danger btn-icon" title="Remove">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Common Variables -->
<div class="card mt-20">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="bookmark"></i> Common Variables
        </h2>
    </div>
    <div class="card-body">
        <p class="text-muted mb-15">Click to add common environment variables:</p>
        <div class="common-vars">
            <button onclick="addCommonVar('NODE_ENV', 'production')" class="common-var-btn">
                <code>NODE_ENV</code>
            </button>
            <button onclick="addCommonVar('PORT', '<?= $service->port ?? 3000 ?>')" class="common-var-btn">
                <code>PORT</code>
            </button>
            <button onclick="addCommonVar('DATABASE_URL', '')" class="common-var-btn">
                <code>DATABASE_URL</code>
            </button>
            <button onclick="addCommonVar('API_KEY', '')" class="common-var-btn">
                <code>API_KEY</code>
            </button>
            <button onclick="addCommonVar('JWT_SECRET', '')" class="common-var-btn">
                <code>JWT_SECRET</code>
            </button>
            <button onclick="addCommonVar('REDIS_URL', '')" class="common-var-btn">
                <code>REDIS_URL</code>
            </button>
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

    /* Alert */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px;
        border-radius: var(--border-radius);
    }

    .alert-info {
        background: rgba(33, 150, 243, 0.1);
        border: 1px solid rgba(33, 150, 243, 0.3);
        color: #2196F3;
    }

    .alert svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alert p {
        margin: 4px 0 0 0;
        font-size: 13px;
        opacity: 0.9;
    }

    /* Env Rows */
    #envContainer {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .env-row {
        display: grid;
        grid-template-columns: 200px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 10px;
        background: var(--bg-input);
        border-radius: var(--border-radius);
    }

    .env-key input {
        font-family: 'Consolas', 'Monaco', monospace;
        text-transform: uppercase;
        font-weight: 600;
    }

    .env-value {
        display: flex;
        gap: 8px;
    }

    .env-value input {
        flex: 1;
        font-family: 'Consolas', 'Monaco', monospace;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon svg {
        width: 16px;
        height: 16px;
    }

    /* Common Variables */
    .common-vars {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .common-var-btn {
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        padding: 8px 12px;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .common-var-btn:hover {
        border-color: var(--primary);
        background: rgba(110, 86, 207, 0.1);
    }

    .common-var-btn code {
        font-size: 12px;
        color: var(--primary);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    .empty-state-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 15px;
        background: var(--bg-input);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }

    .empty-state-icon svg {
        width: 28px;
        height: 28px;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .empty-state-text {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0 0 20px 0;
    }

    /* Utilities */
    .mb-15 {
        margin-bottom: 15px;
    }

    .mb-20 {
        margin-bottom: 20px;
    }

    .mt-20 {
        margin-top: 20px;
    }

    .text-muted {
        color: var(--text-muted);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .env-row {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<script>
    const serviceId = <?= $service->id ?>;
    let hasChanges = false;

    function markChanged() {
        hasChanges = true;
        document.getElementById('saveBtn').disabled = false;
    }

    function addEnvVar() {
        const container = document.getElementById('envContainer');
        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.remove();

        const row = document.createElement('div');
        row.className = 'env-row';
        row.innerHTML = `
        <div class="env-key">
            <input type="text" class="form-control" placeholder="KEY" onchange="markChanged()">
        </div>
        <div class="env-value">
            <input type="text" class="form-control" placeholder="value" onchange="markChanged()">
            <button onclick="toggleVisibility(this)" class="btn btn-icon btn-secondary" title="Toggle visibility">
                <i data-lucide="eye-off"></i>
            </button>
        </div>
        <button onclick="removeEnvVar(this)" class="btn btn-danger btn-icon" title="Remove">
            <i data-lucide="trash-2"></i>
        </button>
    `;
        container.appendChild(row);
        lucide.createIcons();
        markChanged();

        // Focus the key input
        row.querySelector('.env-key input').focus();
    }

    function addCommonVar(key, value) {
        const container = document.getElementById('envContainer');
        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.remove();

        // Check if key already exists
        const existing = container.querySelector(`[data-key="${key}"]`);
        if (existing) {
            alert(`Variable "${key}" already exists.`);
            return;
        }

        const row = document.createElement('div');
        row.className = 'env-row';
        row.setAttribute('data-key', key);
        row.innerHTML = `
        <div class="env-key">
            <input type="text" class="form-control" value="${key}" onchange="markChanged()">
        </div>
        <div class="env-value">
            <input type="text" class="form-control" value="${value}" placeholder="Enter value" onchange="markChanged()">
            <button onclick="toggleVisibility(this)" class="btn btn-icon btn-secondary" title="Toggle visibility">
                <i data-lucide="eye-off"></i>
            </button>
        </div>
        <button onclick="removeEnvVar(this)" class="btn btn-danger btn-icon" title="Remove">
            <i data-lucide="trash-2"></i>
        </button>
    `;
        container.appendChild(row);
        lucide.createIcons();
        markChanged();

        // Focus the value input
        row.querySelector('.env-value input').focus();
    }

    function removeEnvVar(btn) {
        if (confirm('Remove this variable?')) {
            btn.closest('.env-row').remove();
            markChanged();
        }
    }

    function toggleVisibility(btn) {
        const input = btn.previousElementSibling;
        const icon = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye-off');
        }
        lucide.createIcons();
    }

    async function saveEnvVars() {
        const rows = document.querySelectorAll('.env-row');
        const envVars = {};

        rows.forEach(row => {
            const key = row.querySelector('.env-key input').value.trim().toUpperCase();
            const value = row.querySelector('.env-value input').value;
            if (key) {
                envVars[key] = value;
            }
        });

        try {
            const response = await fetch(`<?= $base_url ?>/services/${serviceId}/env`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ env_vars: envVars })
            });

            const data = await response.json();

            if (data.success) {
                alert('Environment variables saved! Restart the container for changes to take effect.');
                hasChanges = false;
                document.getElementById('saveBtn').disabled = true;
            } else {
                alert(data.error || 'Failed to save');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>