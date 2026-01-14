<?php
$page_title = 'Database';
$current_page = 'tools';
ob_start();

// Get service ID from query or use first service
$selectedServiceId = $_GET['service'] ?? null;
$selectedService = null;
$serviceDatabase = null;

if ($selectedServiceId) {
    foreach ($services ?? [] as $s) {
        if ($s->id == $selectedServiceId) {
            $selectedService = $s;
            break;
        }
    }
}

// If no service selected and services exist, use first one
if (!$selectedService && !empty($services) && count($services) > 0) {
    $selectedService = $services[0];
}

// Find database for selected service
if ($selectedService) {
    foreach ($databases ?? [] as $db) {
        if ($db->service_id == $selectedService->id) {
            $serviceDatabase = $db;
            break;
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Database Management</h1>
</div>

<?php if (empty($services) || count($services) == 0): ?>
    <!-- No Services -->
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="database"></i>
            </div>
            <h3 class="empty-state-title">No Services Available</h3>
            <p class="empty-state-text">You need at least one service to create a database. Services are created when you
                order a hosting package.</p>
        </div>
    </div>

<?php else: ?>

    <!-- Service Selector -->
    <div class="card mb-20">
        <div class="card-body">
            <div class="d-flex align-center gap-15">
                <label class="form-label" style="margin: 0; white-space: nowrap;">Select Service:</label>
                <select id="serviceSelector" class="form-control" style="max-width: 300px;"
                    onchange="window.location.href='?service=' + this.value">
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service->id ?>" <?= $selectedService && $selectedService->id == $service->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($service->name) ?>
                            <?php
                            $hasDb = false;
                            foreach ($databases ?? [] as $db) {
                                if ($db->service_id == $service->id) {
                                    $hasDb = true;
                                    break;
                                }
                            }
                            if ($hasDb)
                                echo ' (Database: Active)';
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <span class="text-muted" style="font-size: 12px;">
                    <i data-lucide="info" style="width: 14px; height: 14px;"></i>
                    Each service can have 1 database
                </span>
            </div>
        </div>
    </div>

    <?php if (!$serviceDatabase): ?>
        <!-- Create Database for this Service -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i data-lucide="database"></i>
                    Create Database for "<?= htmlspecialchars($selectedService->name) ?>"
                </h2>
            </div>
            <div class="card-body">
                <p class="text-muted mb-20">This service doesn't have a database yet. Create one to store your application data.
                </p>

                <form id="createDbForm" method="POST" action="<?= $base_url ?>/databases/create">
                    <input type="hidden" name="service_id" value="<?= $selectedService->id ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Database Type</label>
                            <select name="type" required class="form-control">
                                <option value="">Select database type...</option>
                                <option value="mariadb">MariaDB (MySQL)</option>
                                <option value="postgresql">PostgreSQL</option>
                                <option value="mongodb">MongoDB</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Database Name</label>
                            <input type="text" name="db_name"
                                placeholder="<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $selectedService->name)) ?>_db"
                                class="form-control" pattern="[a-zA-Z0-9_]+" title="Only letters, numbers and underscore">
                            <small class="text-muted">Leave empty for auto-generated name</small>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Database User</label>
                            <input type="text" name="db_user"
                                placeholder="<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $selectedService->name)) ?>_user"
                                class="form-control" pattern="[a-zA-Z0-9_]+" title="Only letters, numbers and underscore">
                            <small class="text-muted">Leave empty for auto-generated username</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i data-lucide="plus"></i>
                                Create Database
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Database Info Section -->
        <?php $database = $serviceDatabase; ?>
        <div class="db-layout">
            <!-- Main Info -->
            <div class="db-main">
                <div class="card mb-20">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="database"></i>
                            <?= htmlspecialchars($selectedService->name) ?> - Database
                        </h2>
                        <span class="badge badge-success">
                            <span class="badge-dot"></span>
                            Running
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="db-info-grid">
                            <div class="db-info-item">
                                <div class="db-info-label">Type</div>
                                <div class="db-info-value"><?= strtoupper($database->type) ?></div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Database Name</div>
                                <div class="db-info-value">
                                    <code><?= htmlspecialchars($database->db_name) ?></code>
                                    <button onclick="copyToClipboard('<?= htmlspecialchars($database->db_name) ?>')"
                                        class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Username</div>
                                <div class="db-info-value">
                                    <code><?= htmlspecialchars($database->db_user) ?></code>
                                    <button onclick="copyToClipboard('<?= htmlspecialchars($database->db_user) ?>')"
                                        class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Password</div>
                                <div class="db-info-value">
                                    <code id="dbPassword">••••••••••••</code>
                                    <button onclick="togglePassword()" class="copy-btn" id="togglePwdBtn">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <button onclick="copyToClipboard('<?= htmlspecialchars($database->db_password ?? '') ?>')"
                                        class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Host</div>
                                <div class="db-info-value">
                                    <code><?= htmlspecialchars($database->container_id ?? 'lp_' . $database->service_id . '_' . $database->type) ?></code>
                                </div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Port</div>
                                <div class="db-info-value">
                                    <code><?= $database->type === 'postgresql' ? '5432' : ($database->type === 'mongodb' ? '27017' : '3306') ?></code>
                                </div>
                            </div>
                        </div>

                        <!-- Connection String -->
                        <div class="form-group mt-20">
                            <label class="form-label">Connection String</label>
                            <?php
                            $host = $database->container_id ?? 'lp_' . $database->service_id . '_' . $database->type;
                            if ($database->type === 'postgresql') {
                                $connStr = "postgresql://{$database->db_user}:[PASSWORD]@{$host}:5432/{$database->db_name}";
                            } elseif ($database->type === 'mongodb') {
                                $connStr = "mongodb://{$database->db_user}:[PASSWORD]@{$host}:27017/{$database->db_name}";
                            } else {
                                $connStr = "mysql://{$database->db_user}:[PASSWORD]@{$host}:3306/{$database->db_name}";
                            }
                            ?>
                            <div class="conn-string">
                                <code><?= htmlspecialchars($connStr) ?></code>
                                <button onclick="copyToClipboard('<?= htmlspecialchars($connStr) ?>')" class="copy-btn">
                                    <i data-lucide="copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database Users -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i data-lucide="users"></i>
                            Database Users
                        </h2>
                        <button onclick="showAddUserModal()" class="btn btn-sm btn-primary">
                            <i data-lucide="plus"></i>
                            Add User
                        </button>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Privileges</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($database->db_user) ?></strong>
                                        <span class="badge badge-success" style="margin-left: 5px;">Owner</span>
                                    </td>
                                    <td>ALL PRIVILEGES</td>
                                    <td>-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar Actions -->
            <div class="db-sidebar">
                <div class="card mb-15">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="<?= $base_url ?>/databases/<?= $database->id ?>/adminer" target="_blank"
                            class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                            <i data-lucide="external-link"></i>
                            Open Adminer
                        </a>
                        <button onclick="resetPassword(<?= $database->id ?>)" class="btn btn-secondary"
                            style="width: 100%; margin-bottom: 10px;">
                            <i data-lucide="key"></i>
                            Reset Password
                        </button>
                        <button onclick="deleteDatabase(<?= $database->id ?>, '<?= htmlspecialchars($database->db_name) ?>')"
                            class="btn btn-danger" style="width: 100%;">
                            <i data-lucide="trash-2"></i>
                            Delete Database
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Database Stats</h3>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label">Size</span>
                            <span class="stat-value"><?= $database->size ?? '0 MB' ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Tables</span>
                            <span class="stat-value"><?= $database->tables_count ?? '0' ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Created</span>
                            <span class="stat-value"><?= date('M d, Y', strtotime($database->created_at)) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addUserModal" class="modal" style="display: none;">
            <div class="modal-backdrop" onclick="hideAddUserModal()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Add Database User</h2>
                    <button onclick="hideAddUserModal()" class="modal-close">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <form id="addUserForm" class="modal-body">
                    <input type="hidden" name="database_id" value="<?= $database->id ?>">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" placeholder="new_user" class="form-control" required
                            pattern="[a-zA-Z0-9_]+">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" placeholder="Strong password" class="form-control" required
                            minlength="8">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Privileges</label>
                        <select name="privileges" class="form-control">
                            <option value="ALL">All Privileges</option>
                            <option value="SELECT,INSERT,UPDATE">Read/Write</option>
                            <option value="SELECT">Read Only</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Create User
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
    .db-layout {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 20px;
        align-items: start;
    }

    .db-main {
        min-width: 0;
    }

    .db-sidebar {
        position: sticky;
        top: 75px;
    }

    .db-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .db-info-item {
        padding: 12px;
        background: var(--bg-input);
        border-radius: var(--border-radius);
    }

    .db-info-label {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .db-info-value {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
    }

    .db-info-value code {
        background: var(--bg-card);
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }

    .copy-btn {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: var(--text-muted);
        transition: color 0.15s ease;
    }

    .copy-btn:hover {
        color: var(--primary);
    }

    .copy-btn svg {
        width: 14px;
        height: 14px;
    }

    .conn-string {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: var(--bg-input);
        border-radius: var(--border-radius);
        overflow-x: auto;
    }

    .conn-string code {
        font-size: 12px;
        word-break: break-all;
    }

    .mt-20 {
        margin-top: 20px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 13px;
    }

    .stat-item:last-child {
        border-bottom: none;
    }

    .stat-label {
        color: var(--text-secondary);
    }

    .stat-value {
        font-weight: 600;
    }

    /* Modal */
    .modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        position: relative;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        width: 100%;
        max-width: 400px;
        margin: 20px;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        color: var(--text-muted);
    }

    .modal-body {
        padding: 20px;
    }

    @media (max-width: 991px) {
        .db-layout {
            grid-template-columns: 1fr;
        }

        .db-sidebar {
            position: static;
        }

        .db-info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    const actualPassword = '<?= htmlspecialchars($serviceDatabase->db_password ?? '') ?>';
    let passwordVisible = false;

    function togglePassword() {
        const pwdEl = document.getElementById('dbPassword');
        if (pwdEl) {
            passwordVisible = !passwordVisible;
            pwdEl.textContent = passwordVisible ? actualPassword : '••••••••••••';
        }
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        }).catch(err => {
            console.error('Copy failed:', err);
        });
    }

    function showAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
    }

    function hideAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
    }

    async function deleteDatabase(id, name) {
        if (!confirm('Delete database "' + name + '"? This action cannot be undone and all data will be lost!')) return;

        try {
            const response = await fetch('<?= $base_url ?>/databases/' + id + '/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to delete database');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function resetPassword(id) {
        if (!confirm('Reset database password? You will need to update your application configuration.')) return;

        try {
            const response = await fetch('<?= $base_url ?>/databases/' + id + '/reset-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                alert('New password: ' + result.password + '\n\nPlease save this password!');
                location.reload();
            } else {
                alert(result.error || 'Failed to reset password');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Add user form submit
    document.getElementById('addUserForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch('<?= $base_url ?>/databases/add-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                alert('User created successfully!');
                location.reload();
            } else {
                alert(result.error || 'Failed to create user');
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