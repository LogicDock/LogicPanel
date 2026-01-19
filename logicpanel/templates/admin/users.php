<?php
$page_title = 'Manage Users';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Manage Users</h1>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openCreateUserModal()">
            <i data-lucide="user-plus"></i>
            Create User
        </button>
        <button class="btn btn-success" onclick="openCreateResellerModal()">
            <i data-lucide="users"></i>
            Create Reseller
        </button>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="users"></i>
            </div>
            <h3 class="empty-state-title">No Users</h3>
            <p class="empty-state-text">No users found in the system.</p>
        </div>
    <?php else: ?>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Services</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-center gap-10">
                                    <div
                                        style="width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;">
                                        <?= strtoupper(substr($user->name ?? $user->username, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($user->name ?? $user->username) ?></strong>
                                        <br>
                                        <small class="text-muted">@<?= htmlspecialchars($user->username) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user->email) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-secondary';
                                if ($user->role === 'admin')
                                    $badgeClass = 'badge-danger';
                                elseif ($user->role === 'reseller')
                                    $badgeClass = 'badge-success';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucfirst($user->role) ?>
                                </span>
                            </td>
                            <td><?= $user->services_count ?? 0 ?></td>
                            <td>
                                <?php if ($user->is_active): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-5">
                                    <button class="btn btn-sm btn-secondary" title="Edit">
                                        <i data-lucide="edit"></i>
                                    </button>
                                    <?php if ($user->id !== ($_SESSION['user_id'] ?? 0)): ?>
                                        <button class="btn btn-sm btn-danger" title="Delete">
                                            <i data-lucide="trash-2"></i>
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
</div>

<style>
    /* User Management Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-content {
        background-color: var(--bg-card);
        margin: auto;
        padding: 0;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        width: 100%;
        max-width: 500px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        animation: modalFadeIn 0.2s ease-out;
    }

    @keyframes modalFadeIn {
        from {
            transform: translateY(-30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: var(--text-muted);
        line-height: 1;
    }

    .modal-close:hover {
        color: var(--danger);
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: var(--bg-body);
        border-radius: 0 0 var(--border-radius) var(--border-radius);
    }
</style>

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New User</h3>
            <button class="modal-close" onclick="closeCreateUserModal()">×</button>
        </div>
        <form id="createUserForm" onsubmit="createUser(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" required pattern="[a-zA-Z0-9_]+" class="form-control"
                        placeholder="john_doe">
                    <small class="text-muted">Only letters, numbers and underscores</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" required class="form-control" placeholder="user@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" required minlength="8" class="form-control"
                        placeholder="••••••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Reseller Modal -->
<div id="createResellerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Reseller</h3>
            <button class="modal-close" onclick="closeCreateResellerModal()">×</button>
        </div>
        <form id="createResellerForm" onsubmit="createReseller(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" required pattern="[a-zA-Z0-9_]+" class="form-control"
                        placeholder="reseller_name">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" required class="form-control" placeholder="reseller@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Business Name">
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" required minlength="8" class="form-control"
                        placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label">Reseller Package</label>
                    <select name="reseller_package_id" class="form-control">
                        <option value="">None (Custom Limits)</option>
                        <?php
                        // Fetch reseller packages for the dropdown
                        $rPackages = \LogicPanel\Models\ResellerPackage::where('is_active', 1)->get();
                        foreach ($rPackages as $rp): ?>
                            <option value="<?= $rp->id ?>"><?= htmlspecialchars($rp->display_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateResellerModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Create Reseller Account</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateUserModal() {
        document.getElementById('createUserModal').style.display = 'flex';
    }

    function closeCreateUserModal() {
        document.getElementById('createUserModal').style.display = 'none';
        document.getElementById('createUserForm').reset();
    }

    function openCreateResellerModal() {
        document.getElementById('createResellerModal').style.display = 'flex';
    }

    function closeCreateResellerModal() {
        document.getElementById('createResellerModal').style.display = 'none';
        document.getElementById('createResellerForm').reset();
    }

    function createUser(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.role = 'user';

        fetch('<?= $base_url ?>/admin/users/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    closeCreateUserModal();
                    location.reload();
                } else {
                    alert(result.error || 'Failed to create user');
                }
            });
    }

    function createReseller(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.role = 'reseller';

        fetch('<?= $base_url ?>/admin/users/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    closeCreateResellerModal();
                    location.reload();
                } else {
                    alert(result.error || 'Failed to create reseller');
                }
            });
    }

    // Close modal on outside click
    window.onclick = function (event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>