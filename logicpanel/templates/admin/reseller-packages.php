<?php
/**
 * Admin - Reseller Plans Management
 */
$base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
ob_start();
?>

<div class="page-header">
    <h1>Reseller Plans</h1>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i data-lucide="plus"></i>
        Create Reseller Plan
    </button>
</div>

<div class="card">
    <?php if (empty($packages)): ?>
        <div class="empty-state">
            <i data-lucide="package"></i>
            <h3>No Reseller Plans</h3>
            <p>Create your first reseller plan to get started</p>
            <button class="btn btn-primary" onclick="openCreateModal()">
                Create Reseller Plan
            </button>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Max Users</th>
                        <th>Max Services</th>
                        <th>Resources</th>
                        <th>Resellers</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($pkg->display_name) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($pkg->name) ?></small>
                            </td>
                            <td><?= $pkg->max_users ?></td>
                            <td><?= $pkg->max_services ?></td>
                            <td>
                                <?= $pkg->max_disk_gb ?> GB Disk<br>
                                <small class="text-muted"><?= $pkg->max_bandwidth_gb ?> GB Bandwidth</small>
                            </td>
                            <td><?= $pkg->users_count ?? 0 ?></td>
                            <td>
                                <span class="badge badge-<?= $pkg->is_active ? 'success' : 'secondary' ?>">
                                    <?= $pkg->is_active ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-5">
                                    <button class="btn btn-sm btn-secondary" onclick="editPackage(<?= $pkg->id ?>)">
                                        <i data-lucide="edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePackage(<?= $pkg->id ?>)">
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

<!--Create Modal -->
<div id="createModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Create Reseller Plan</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="createForm" onsubmit="createPlan(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label>Plan Name *</label>
                    <input type="text" name="name" required class="form-control" placeholder="starter-reseller">
                    <small>Lowercase, no spaces</small>
                </div>
                <div class="form-group">
                    <label>Display Name *</label>
                    <input type="text" name="display_name" required class="form-control" placeholder="Starter Reseller">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Users *</label>
                        <input type="number" name="max_users" min="1" value="10" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Services *</label>
                        <input type="number" name="max_services" min="1" value="50" required class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Disk (GB) *</label>
                        <input type="number" name="max_disk_gb" min="1" value="100" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Bandwidth (GB) *</label>
                        <input type="number" name="max_bandwidth_gb" min="0" value="1000" required class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="can_create_packages" checked>
                        Allow creating custom packages
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
</style>

<script>
    function openCreateModal() {
        document.getElementById('createModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('createModal').style.display = 'none';
        document.getElementById('createForm').reset();
    }

    function createPlan(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.can_create_packages = formData.has('can_create_packages');
        data.is_active = true;

        fetch('<?= $base_url ?>/admin/reseller-packages/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.error || 'Failed to create plan');
                }
            });
    }

    function editPackage(id) {
        // TODO: Implement edit
        alert('Edit feature coming soon');
    }

    function deletePackage(id) {
        if (!confirm('Delete this reseller plan?')) return;

        fetch(`<?= $base_url ?>/admin/reseller-packages/${id}/delete`, {
            method: 'POST'
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.error || 'Failed to delete plan');
                }
            });
    }

    window.onclick = function (e) {
        if (e.target.className === 'modal') {
            closeModal();
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>