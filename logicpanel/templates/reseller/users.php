<?php
$page_title = 'My Users';
$current_page = 'reseller_users';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">My Users</h1>
    <?php if ($canCreateMore ?? true): ?>
        <a href="<?= $base_url ?>/reseller/users/create" class="btn btn-primary">
            <i data-lucide="user-plus"></i>
            Create New User
        </a>
    <?php else: ?>
        <span class="text-muted">User limit reached</span>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($users) || count($users) === 0): ?>
            <div class="empty-state">
                <i data-lucide="users"></i>
                <h3>No Users Yet</h3>
                <p>Create your first user to get started.</p>
                <a href="<?= $base_url ?>/reseller/users/create" class="btn btn-primary">
                    <i data-lucide="user-plus"></i>
                    Create User
                </a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Services</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($u->username, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong>
                                            <?= htmlspecialchars($u->username) ?>
                                        </strong>
                                        <span class="text-muted">
                                            <?= htmlspecialchars($u->name ?? '') ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars($u->email) ?>
                            </td>
                            <td>
                                <?= $u->services()->count() ?>
                            </td>
                            <td>
                                <?php if ($u->is_active): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($u->created_at)) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= $base_url ?>/reseller/users/<?= $u->id ?>/edit"
                                        class="btn btn-sm btn-secondary" title="Edit">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <button onclick="toggleUser(<?= $u->id ?>, <?= $u->is_active ? 'true' : 'false' ?>)"
                                        class="btn btn-sm <?= $u->is_active ? 'btn-warning' : 'btn-success' ?>"
                                        title="<?= $u->is_active ? 'Suspend' : 'Activate' ?>">
                                        <i data-lucide="<?= $u->is_active ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button onclick="deleteUser(<?= $u->id ?>, '<?= htmlspecialchars($u->username) ?>')"
                                        class="btn btn-sm btn-danger" title="Delete">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state [data-lucide] {
        width: 64px;
        height: 64px;
        color: var(--text-muted);
        margin-bottom: 20px;
    }

    .empty-state h3 {
        margin-bottom: 10px;
    }

    .empty-state p {
        color: var(--text-secondary);
        margin-bottom: 20px;
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
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-secondary);
        background: var(--bg-secondary);
    }

    .table tbody tr:hover {
        background: var(--bg-secondary);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .user-info span {
        display: block;
        font-size: 12px;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }
</style>

<script>
    async function toggleUser(id, isActive) {
        const action = isActive ? 'suspend' : 'activate';
        if (!confirm(`Are you sure you want to ${action} this user?`)) return;

        try {
            const response = await fetch(`<?= $base_url ?>/reseller/users/${id}/toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to update user');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function deleteUser(id, username) {
        if (!confirm(`Delete user "${username}"? This cannot be undone!`)) return;

        try {
            const response = await fetch(`<?= $base_url ?>/reseller/users/${id}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to delete user');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>