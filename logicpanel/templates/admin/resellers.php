<?php
/**
 * Admin - Resellers Management
 */
$page_title = 'Resellers List';
$current_page = 'resellers';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Manage Resellers</h1>
    <button class="btn btn-primary" onclick="window.location.href='/admin/users?role=reseller'">
        <i data-lucide="plus"></i>
        Add Reseller
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Reseller</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resellers)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No resellers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resellers as $reseller): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($reseller->name) ?>
                                </strong>
                                <br><small class="text-muted">
                                    <?= htmlspecialchars($reseller->username) ?>
                                </small>
                            </td>
                            <td>
                                <?= htmlspecialchars($reseller->email) ?>
                            </td>
                            <td>
                                <?php if ($reseller->reseller_package_id): ?>
                                    <span class="badge badge-info">Plan ID:
                                        <?= $reseller->reseller_package_id ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Plan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $reseller->children()->count() ?> Users
                            </td>
                            <td>
                                <span class="badge badge-<?= $reseller->is_active ? 'success' : 'danger' ?>">
                                    <?= $reseller->is_active ? 'Active' : 'Suspended' ?>
                                </span>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($reseller->created_at)) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-5">
                                    <button class="btn btn-sm btn-secondary" onclick="editUser(<?= $reseller->id ?>)">
                                        <i data-lucide="edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-<?= $reseller->is_active ? 'warning' : 'success' ?>"
                                        onclick="toggleStatus(<?= $reseller->id ?>)">
                                        <i data-lucide="<?= $reseller->is_active ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>