<?php
$page_title = 'Manage Users';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Manage Users</h1>
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
                                <span class="badge <?= $user->role === 'admin' ? 'badge-success' : 'badge-secondary' ?>">
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>