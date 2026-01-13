<?php
$page_title = 'API Keys';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">API Keys</h1>
    <button onclick="showCreateModal()" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Create API Key
    </button>
</div>

<!-- API Keys List -->
<div class="card">
    <?php if (empty($apiKeys)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="key"></i>
            </div>
            <h3 class="empty-state-title">No API Keys</h3>
            <p class="empty-state-text">Create API keys to allow WHMCS and other systems to connect to LogicPanel.</p>
            <button onclick="showCreateModal()" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Create API Key
            </button>
        </div>
    <?php else: ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>API Key</th>
                        <th>Permissions</th>
                        <th>Last Used</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($key->name) ?>
                                </strong>
                                <div class="text-muted" style="font-size: 11px;">Created:
                                    <?= date('M d, Y', strtotime($key->created_at)) ?>
                                </div>
                            </td>
                            <td>
                                <code class="api-key-display"><?= substr($key->api_key, 0, 12) ?>...</code>
                                <button onclick="copyKey('<?= htmlspecialchars($key->api_key) ?>')" class="btn-icon"
                                    title="Copy API Key">
                                    <i data-lucide="copy"></i>
                                </button>
                            </td>
                            <td>
                                <?php
                                $perms = json_decode($key->permissions ?? '{}', true);
                                $permLabels = [];
                                if (!empty($perms['create']))
                                    $permLabels[] = '<span class="badge badge-success">Create</span>';
                                if (!empty($perms['suspend']))
                                    $permLabels[] = '<span class="badge badge-warning">Suspend</span>';
                                if (!empty($perms['terminate']))
                                    $permLabels[] = '<span class="badge badge-danger">Terminate</span>';
                                if (!empty($perms['sso']))
                                    $permLabels[] = '<span class="badge badge-info">SSO</span>';
                                echo implode(' ', $permLabels) ?: '<span class="text-muted">None</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($key->last_used_at): ?>
                                    <?= date('M d, Y H:i', strtotime($key->last_used_at)) ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key->is_active): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="viewSecret(<?= $key->id ?>, '<?= htmlspecialchars($key->api_secret) ?>')"
                                        class="btn btn-sm btn-secondary" title="View Secret">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <button onclick="toggleKey(<?= $key->id ?>, <?= $key->is_active ? 'false' : 'true' ?>)"
                                        class="btn btn-sm btn-secondary" title="<?= $key->is_active ? 'Disable' : 'Enable' ?>">
                                        <i data-lucide="<?= $key->is_active ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button onclick="deleteKey(<?= $key->id ?>, '<?= htmlspecialchars($key->name) ?>')"
                                        class="btn btn-sm btn-danger" title="Delete">
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

<!-- WHMCS Setup Guide -->
<div class="card mt-20">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="book-open"></i>
            WHMCS Setup Guide
        </h2>
    </div>
    <div class="card-body">
        <div class="setup-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Create API Key</h4>
                    <p>Click "Create API Key" above with all permissions enabled for WHMCS.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Add Server in WHMCS</h4>
                    <p>Go to <strong>Setup → Products/Services → Servers → Add New Server</strong></p>
                    <ul>
                        <li><strong>Module:</strong> LogicPanel - Node.js Hosting</li>
                        <li><strong>Hostname:</strong> <code><?= $_SERVER['HTTP_HOST'] ?></code></li>
                        <li><strong>Secure:</strong>
                            <?= ($_SERVER['REQUEST_SCHEME'] ?? 'http') === 'https' ? '✅ Yes' : '❌ No' ?>
                        </li>
                        <li><strong>Access Hash:</strong> [Your API Key]</li>
                        <li><strong>Password:</strong> [Your API Secret]</li>
                    </ul>
                </div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Create Product</h4>
                    <p>Create a new product with LogicPanel module and select your package.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="hideModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create API Key</h2>
            <button type="button" onclick="hideModal()" class="modal-close">&times;</button>
        </div>
        <form id="createForm" class="modal-body">
            <div class="form-group">
                <label class="form-label">Key Name</label>
                <input type="text" name="name" placeholder="WHMCS Integration" class="form-control" required>
                <small class="text-muted">A descriptive name for this API key</small>
            </div>

            <h4 class="form-section-title">Permissions</h4>

            <div class="permissions-grid">
                <label class="checkbox-item">
                    <input type="checkbox" name="perm_create" checked>
                    <span>Create Accounts</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="perm_suspend" checked>
                    <span>Suspend Accounts</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="perm_terminate" checked>
                    <span>Terminate Accounts</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="perm_sso" checked>
                    <span>SSO Login</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" onclick="hideModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Key</button>
            </div>
        </form>
    </div>
</div>

<!-- View Secret Modal -->
<div id="secretModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="hideSecretModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">API Credentials</h2>
            <button type="button" onclick="hideSecretModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-warning">
                <i data-lucide="alert-triangle"></i>
                Keep these credentials secure! The secret is only shown once.
            </div>

            <div class="form-group">
                <label class="form-label">API Key</label>
                <div class="credential-box">
                    <code id="displayApiKey"></code>
                    <button onclick="copyKey(document.getElementById('displayApiKey').textContent)" class="btn-icon">
                        <i data-lucide="copy"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">API Secret</label>
                <div class="credential-box">
                    <code id="displayApiSecret"></code>
                    <button onclick="copyKey(document.getElementById('displayApiSecret').textContent)" class="btn-icon">
                        <i data-lucide="copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
    }

    .api-key-display {
        background: var(--bg-input);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .btn-icon {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: var(--text-muted);
        vertical-align: middle;
    }

    .btn-icon:hover {
        color: var(--primary);
    }

    .btn-icon svg {
        width: 14px;
        height: 14px;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
    }

    .badge-info {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .setup-steps {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .step {
        display: flex;
        gap: 15px;
    }

    .step-number {
        width: 32px;
        height: 32px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        flex-shrink: 0;
    }

    .step-content h4 {
        margin: 0 0 5px;
        font-size: 14px;
    }

    .step-content p {
        margin: 0;
        font-size: 13px;
        color: var(--text-secondary);
    }

    .step-content ul {
        margin: 10px 0 0;
        padding-left: 20px;
        font-size: 13px;
    }

    .step-content li {
        margin: 5px 0;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 13px;
        padding: 8px 12px;
        background: var(--bg-input);
        border-radius: var(--border-radius);
    }

    .checkbox-item input {
        margin: 0;
    }

    .credential-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--bg-input);
        padding: 12px;
        border-radius: var(--border-radius);
        font-family: monospace;
    }

    .credential-box code {
        flex: 1;
        word-break: break-all;
        font-size: 12px;
    }

    .alert {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        font-size: 13px;
    }

    .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .form-section-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        margin: 20px 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .modal-close {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-secondary);
        font-size: 20px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .modal-close:hover {
        background: var(--danger);
        border-color: var(--danger);
        color: white;
    }

    .mt-20 {
        margin-top: 20px;
    }
</style>

<script>
    function showCreateModal() {
        document.getElementById('createModal').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('createModal').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function hideModal() {
        document.getElementById('createModal').style.display = 'none';
    }

    function hideSecretModal() {
        document.getElementById('secretModal').style.display = 'none';
    }

    function viewSecret(id, secret) {
        // Find the API key from the table
        const rows = document.querySelectorAll('tbody tr');
        let apiKey = '';
        rows.forEach(row => {
            if (row.innerHTML.includes('viewSecret(' + id)) {
                const codeEl = row.querySelector('.api-key-display');
                if (codeEl) {
                    // Get full key from onclick
                    const copyBtn = row.querySelector('[onclick*="copyKey"]');
                    if (copyBtn) {
                        const match = copyBtn.getAttribute('onclick').match(/copyKey\('([^']+)'\)/);
                        if (match) apiKey = match[1];
                    }
                }
            }
        });

        document.getElementById('displayApiKey').textContent = apiKey;
        document.getElementById('displayApiSecret').textContent = secret;
        document.getElementById('secretModal').style.display = 'flex';
    }

    function copyKey(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        });
    }

    async function toggleKey(id, enable) {
        try {
            const response = await fetch('<?= $base_url ?>/admin/api-keys/' + id + '/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_active: enable })
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to update key');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function deleteKey(id, name) {
        if (!confirm('Delete API key "' + name + '"? This action cannot be undone.')) return;

        try {
            const response = await fetch('<?= $base_url ?>/admin/api-keys/' + id + '/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to delete key');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    document.getElementById('createForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            name: formData.get('name'),
            permissions: {
                create: formData.get('perm_create') === 'on',
                suspend: formData.get('perm_suspend') === 'on',
                terminate: formData.get('perm_terminate') === 'on',
                sso: formData.get('perm_sso') === 'on'
            }
        };

        try {
            const response = await fetch('<?= $base_url ?>/admin/api-keys/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                // Show the credentials
                document.getElementById('displayApiKey').textContent = result.api_key;
                document.getElementById('displayApiSecret').textContent = result.api_secret;
                hideModal();
                document.getElementById('secretModal').style.display = 'flex';

                // Reload after user closes modal
                setTimeout(() => {
                    if (confirm('Credentials saved? Click OK to refresh the page.')) {
                        location.reload();
                    }
                }, 500);
            } else {
                alert(result.error || 'Failed to create key');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>