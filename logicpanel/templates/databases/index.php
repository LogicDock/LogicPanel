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

                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div style="flex: 1; max-width: 300px;">
                            <select name="type" required class="form-control">
                                <option value="">Select database type...</option>
                                <option value="mariadb">MariaDB (MySQL)</option>
                                <option value="postgresql">PostgreSQL</option>
                                <option value="mongodb">MongoDB</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="plus"></i>
                            Create Database
                        </button>
                    </div>
                    <small class="text-muted" style="display: block; margin-top: 10px;">Database name, username and password
                        will be auto-generated</small>
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
                                    <button
                                        onclick="copyToClipboard('<?= htmlspecialchars($database->db_password_decrypted ?? '') ?>')"
                                        class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="db-info-item">
                                <div class="db-info-label">Host</div>
                                <div class="db-info-value">
                                    <code><?= htmlspecialchars($database->container_name ?? 'lp_' . $service->name . '_' . $database->type) ?></code>
                                    <button
                                        onclick="copyToClipboard('<?= htmlspecialchars($database->container_name ?? '') ?>')"
                                        class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
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
                            // Get DECRYPTED password - this is set by DatabaseController
                            // db_password_decrypted contains the actual password user can use
                            $password = $database->db_password_decrypted ?? '';

                            // URL-encode username and password for special characters
                            $encodedUser = rawurlencode($database->db_user);
                            $encodedPassword = rawurlencode($password);

                            // Use server's public IP/hostname for external access
                            // Container name only works inside Docker network
                            $externalHost = $_ENV['SERVER_HOSTNAME'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_ADDR'] ?? 'localhost';
                            // Remove port from HTTP_HOST if present
                            $externalHost = preg_replace('/:\d+$/', '', $externalHost);

                            if ($database->type === 'postgresql') {
                                $connStr = "postgresql://{$encodedUser}:{$encodedPassword}@{$externalHost}:5432/{$database->db_name}";
                            } elseif ($database->type === 'mongodb') {
                                // authSource is required - user is created in specific database, not admin
                                $connStr = "mongodb://{$encodedUser}:{$encodedPassword}@{$externalHost}:27017/{$database->db_name}?authSource={$database->db_name}";
                            } else {
                                $connStr = "mysql://{$encodedUser}:{$encodedPassword}@{$externalHost}:3306/{$database->db_name}";
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
            </div>

            <!-- Sidebar Actions -->
            <div class="db-sidebar">
                <div class="card mb-15">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($database->type === 'mongodb'): ?>
                            <!-- MongoDB - Copy Connection String (Mongo Express is admin-only) -->
                            <?php
                            // Use decrypted password from controller
                            $mongoPassword = $database->db_password_decrypted ?? '';
                            $mongoHost = $_ENV['SERVER_HOSTNAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
                            $mongoHost = preg_replace('/:\d+$/', '', $mongoHost);
                            // URL-encode username and password for special characters
                            $mongoEncodedUser = rawurlencode($database->db_user);
                            $mongoEncodedPassword = rawurlencode($mongoPassword);
                            $mongoConnStr = "mongodb://{$mongoEncodedUser}:{$mongoEncodedPassword}@{$mongoHost}:27017/{$database->db_name}?authSource={$database->db_name}";
                            ?>
                            <button onclick="copyToClipboard('<?= htmlspecialchars($mongoConnStr) ?>')" class="btn btn-primary"
                                style="width: 100%; margin-bottom: 10px;">
                                <i data-lucide="copy"></i>
                                Copy Connection String
                            </button>
                            <small class="text-muted" style="display: block; margin-bottom: 10px; text-align: center;">
                                Use MongoDB Compass or your preferred client
                            </small>
                        <?php else: ?>
                            <!-- MariaDB/PostgreSQL - Use Adminer with SSO -->
                            <?php
                            $adminerUrl = $_ENV['ADMINER_URL'] ?? 'https://adminer.logicdock.cloud';
                            $dbType = $database->type === 'mariadb' ? 'server' : 'pgsql';
                            $host = $database->container_name;
                            $dbName = $database->db_name;
                            $user = $database->db_user;
                            // Build Adminer auto-login URL
                            $adminerLink = "{$adminerUrl}/?{$dbType}={$host}&username={$user}&db={$dbName}";
                            ?>
                            <a href="<?= htmlspecialchars($adminerLink) ?>" target="_blank" class="btn btn-primary"
                                style="width: 100%; margin-bottom: 10px;">
                                <i data-lucide="external-link"></i>
                                Open Adminer
                            </a>
                        <?php endif; ?>
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
        flex-wrap: wrap;
    }

    .db-info-value code {
        background: var(--bg-card);
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
        word-break: break-all;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
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
    const actualPassword = '<?= htmlspecialchars($serviceDatabase->db_password_decrypted ?? '') ?>';
    let passwordVisible = false;

    function togglePassword() {
        const pwdEl = document.getElementById('dbPassword');
        if (pwdEl) {
            passwordVisible = !passwordVisible;
            pwdEl.textContent = passwordVisible ? actualPassword : '••••••••••••';
        }
    }

    // Toast notification system
    function showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        lucide.createIcons();

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Custom confirm modal
    function showConfirm(title, message, onConfirm) {
        const modal = document.createElement('div');
        modal.className = 'confirm-modal';
        modal.innerHTML = `
            <div class="confirm-backdrop"></div>
            <div class="confirm-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirm-actions">
                    <button class="btn btn-secondary" onclick="this.closest('.confirm-modal').remove()">Cancel</button>
                    <button class="btn btn-danger" id="confirmBtn">Delete</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('#confirmBtn').onclick = () => {
            modal.remove();
            onConfirm();
        };
        modal.querySelector('.confirm-backdrop').onclick = () => modal.remove();
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        }).catch(err => {
            showToast('Copy failed: ' + err.message, 'error');
        });
    }

    async function deleteDatabase(id, name) {
        showConfirm(
            'Delete Database',
            `Are you sure you want to delete "${name}"?<br><small class="text-danger">This action cannot be undone and all data will be lost!</small>`,
            async () => {
                try {
                    const response = await fetch('<?= $base_url ?>/databases/' + id + '/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const result = await response.json();
                    if (result.success) {
                        showToast('Database deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(result.error || 'Failed to delete database', 'error');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                }
            }
        );
    }

    async function resetPassword(id) {
        showConfirm(
            'Reset Password',
            'Reset database password?<br><small>You will need to update your application configuration.</small>',
            async () => {
                try {
                    const response = await fetch('<?= $base_url ?>/databases/' + id + '/reset-password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Show password in a modal that can be copied
                        const pwModal = document.createElement('div');
                        pwModal.className = 'confirm-modal';
                        pwModal.innerHTML = `
                            <div class="confirm-backdrop"></div>
                            <div class="confirm-content">
                                <h3><i data-lucide="key"></i> New Password</h3>
                                <p>Your new database password:</p>
                                <div class="conn-string" style="margin: 15px 0;">
                                    <code id="newPwCode">${result.password}</code>
                                    <button onclick="copyToClipboard('${result.password}')" class="copy-btn">
                                        <i data-lucide="copy"></i>
                                    </button>
                                </div>
                                <p class="text-warning"><small>Please save this password! You won't see it again.</small></p>
                                <div class="confirm-actions">
                                    <button class="btn btn-primary" onclick="this.closest('.confirm-modal').remove(); location.reload();">Done</button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(pwModal);
                        lucide.createIcons();
                    } else {
                        showToast(result.error || 'Failed to reset password', 'error');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                }
            }
        );
    }
</script>

<style>
    /* Toast Notifications */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: #2d313a;
        /* Solid dark background */
        border: 1px solid #3e4249;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        z-index: 10000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        color: #e4e6eb;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast-success {
        border-left: 4px solid var(--success);
    }

    .toast-success svg {
        color: var(--success);
    }

    .toast-error {
        border-left: 4px solid var(--danger);
    }

    .toast-error svg {
        color: var(--danger);
    }

    .toast-info {
        border-left: 4px solid var(--primary);
    }

    .toast-info svg {
        color: var(--primary);
    }

    /* Confirm Modal */
    .confirm-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .confirm-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        /* More opaque backdrop */
    }

    .confirm-content {
        position: relative;
        background: #2d313a;
        /* Solid dark background */
        border: 1px solid #3e4249;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        animation: modalIn 0.2s ease;
        color: #e4e6eb;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .confirm-content h3 {
        margin: 0 0 15px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #e4e6eb;
    }

    .confirm-content p {
        color: #b0b3b8;
        margin: 0 0 10px 0;
    }

    .confirm-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .confirm-actions .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        border: none;
    }

    .confirm-actions .btn-secondary {
        background: #4a4f59;
        color: #e4e6eb;
    }

    .confirm-actions .btn-secondary:hover {
        background: #5a5f69;
    }

    .confirm-actions .btn-danger {
        background: #dc3545;
        color: #fff;
    }

    .confirm-actions .btn-danger:hover {
        background: #c82333;
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>