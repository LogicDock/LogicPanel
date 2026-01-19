<?php
$page_title = 'All Services';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">All Services</h1>
    <button onclick="showCreateModal()" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Create Service
    </button>
</div>

<!-- Services Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($services) || (method_exists($services, 'isEmpty') && $services->isEmpty())): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="server"></i>
                </div>
                <h3 class="empty-state-title">No Services</h3>
                <p class="empty-state-text">Create a service to get started with standalone mode.</p>
                <button onclick="showCreateModal()" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Create Service
                </button>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>User</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Package</th>
                            <th style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($service->name ?? 'Unnamed') ?></strong>
                                    <br><small class="text-muted">ID: <?= $service->id ?></small>
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
                                    <?= htmlspecialchars($service->package->display_name ?? $service->plan ?? '-') ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="btn btn-sm btn-secondary"
                                            title="Manage">
                                            <i data-lucide="settings"></i>
                                        </a>
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

<!-- Create Service Modal -->
<div id="createModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2>Create New Service</h2>
            <button onclick="closeModal()" class="modal-close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="createForm" onsubmit="createService(event)">
            <div class="modal-body">
                <div class="form-section">
                    <h4>User Account</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">User</label>
                            <select id="user_id" class="form-control" required>
                                <option value="">-- Select User --</option>
                                <option value="new">+ Create New User</option>
                                <?php foreach ($users ?? [] as $user): ?>
                                    <option value="<?= $user->id ?>"><?= htmlspecialchars($user->name) ?>
                                        (<?= htmlspecialchars($user->email) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- New User Fields (hidden by default) -->
                    <div id="newUserFields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" id="new_user_name" class="form-control" placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" id="new_user_email" class="form-control"
                                    placeholder="john@example.com">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" id="new_user_username" class="form-control" placeholder="johndoe">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" id="new_user_password" class="form-control"
                                    placeholder="Min 8 characters">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Service Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Service Name</label>
                            <input type="text" id="service_name" class="form-control" placeholder="my-app" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Domain</label>
                            <input type="text" id="domain" class="form-control" placeholder="app.example.com" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Package</label>
                            <select id="package_id" class="form-control" required>
                                <?php foreach ($packages ?? [] as $pkg): ?>
                                    <option value="<?= $pkg->id ?>"><?= htmlspecialchars($pkg->display_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Runtime</label>
                            <select id="runtime" class="form-control" required>
                                <option value="nodejs">Node.js</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="static">Static HTML</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Git Deployment (Optional)</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Git Repository URL</label>
                            <input type="text" id="git_repo" class="form-control"
                                placeholder="https://github.com/user/repo.git">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch</label>
                            <input type="text" id="git_branch" class="form-control" placeholder="main" value="main">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="rocket"></i>
                    Create Service
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .page-title {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
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

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: var(--bg-card);
        border-radius: 12px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .modal-close {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 4px;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 20px;
        border-top: 1px solid var(--border-color);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .form-section {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .form-section h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    // Toggle new user fields
    document.getElementById('user_id').addEventListener('change', function () {
        const newUserFields = document.getElementById('newUserFields');
        if (this.value === 'new') {
            newUserFields.style.display = 'block';
            document.getElementById('new_user_name').required = true;
            document.getElementById('new_user_email').required = true;
            document.getElementById('new_user_username').required = true;
            document.getElementById('new_user_password').required = true;
        } else {
            newUserFields.style.display = 'none';
            document.getElementById('new_user_name').required = false;
            document.getElementById('new_user_email').required = false;
            document.getElementById('new_user_username').required = false;
            document.getElementById('new_user_password').required = false;
        }
    });

    function showCreateModal() {
        document.getElementById('createModal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('createModal').style.display = 'none';
    }

    async function createService(e) {
        e.preventDefault();

        const data = {
            service_name: document.getElementById('service_name').value,
            domain: document.getElementById('domain').value,
            package_id: document.getElementById('package_id').value,
            runtime: document.getElementById('runtime').value,
            git_repo: document.getElementById('git_repo').value,
            git_branch: document.getElementById('git_branch').value
        };

        // Add user info
        const userId = document.getElementById('user_id').value;
        if (userId === 'new') {
            data.new_user = {
                name: document.getElementById('new_user_name').value,
                email: document.getElementById('new_user_email').value,
                username: document.getElementById('new_user_username').value,
                password: document.getElementById('new_user_password').value
            };
        } else {
            data.user_id = userId;
        }

        try {
            const response = await fetch('<?= $base_url ?? '' ?>/admin/services/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                alert('Service created successfully!');
                location.reload();
            } else {
                alert(result.error || 'Failed to create service');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

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
        if (!confirm(`Are you sure you want to PERMANENTLY DELETE "${name}"?`)) return;
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

    // Close modal on backdrop click
    document.getElementById('createModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>