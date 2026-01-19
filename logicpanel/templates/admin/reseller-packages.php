<?php
$page_title = 'Reseller Plans';
$current_page = 'reseller_packages_admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Reseller Plans</h1>
    <button onclick="showCreateModal()" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Create Plan
    </button>
</div>

<!-- Packages List -->
<div class="card">
    <?php if (empty($packages) || $packages->isEmpty()): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="layers"></i>
            </div>
            <h3 class="empty-state-title">No Reseller Plans</h3>
            <p class="empty-state-text">Create reseller plans to allow users to become resellers with their own customers.
            </p>
            <button onclick="showCreateModal()" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Create Plan
            </button>
        </div>
    <?php else: ?>

        <div class="packages-grid">
            <?php foreach ($packages as $package): ?>
                <div class="package-card <?= !$package->is_active ? 'inactive' : '' ?>">
                    <div class="package-header">
                        <h3 class="package-name">
                            <?= htmlspecialchars($package->display_name) ?>
                        </h3>
                        <code class="package-slug"><?= htmlspecialchars($package->name) ?></code>
                        <?php if (!$package->is_active): ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>

                    <p class="package-desc">
                        <?= htmlspecialchars($package->description ?? 'No description') ?>
                    </p>

                    <div class="package-resources">
                        <div class="resource-item">
                            <i data-lucide="users"></i>
                            <span><strong>
                                    <?= $package->max_users ?>
                                </strong> Users</span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="server"></i>
                            <span><strong>
                                    <?= $package->max_services ?>
                                </strong> Services</span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="hard-drive"></i>
                            <span><strong>
                                    <?= $package->max_disk_gb ?>
                                </strong> GB Disk</span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="activity"></i>
                            <span><strong>
                                    <?= $package->max_bandwidth_gb ?>
                                </strong> GB/mo</span>
                        </div>
                    </div>

                    <div class="package-features">
                        <?php if ($package->can_create_packages): ?>
                            <span class="feature enabled"><i data-lucide="check"></i> Create Packages</span>
                        <?php else: ?>
                            <span class="feature disabled"><i data-lucide="x"></i> Create Packages</span>
                        <?php endif; ?>
                    </div>

                    <div class="package-footer">
                        <span class="services-count">
                            <strong>
                                <?= $package->users_count ?? 0 ?>
                            </strong> resellers
                        </span>
                        <div class="package-actions">
                            <button onclick="editPackage(<?= htmlspecialchars(json_encode($package)) ?>)"
                                class="btn btn-secondary btn-sm">
                                <i data-lucide="edit-2"></i>
                            </button>
                            <button
                                onclick="deletePackage(<?= $package->id ?>, '<?= htmlspecialchars($package->display_name) ?>')"
                                class="btn btn-danger btn-sm">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="packageModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create Reseller Plan</h2>
            <button onclick="closeModal()" class="modal-close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="packageForm" onsubmit="savePackage(event)">
            <input type="hidden" id="packageId" value="">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Plan Name (slug)</label>
                    <input type="text" id="name" class="form-control" placeholder="reseller_basic" required
                        pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                </div>
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" id="display_name" class="form-control" placeholder="Reseller Basic" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="description" class="form-control" rows="2" placeholder="Plan description..."></textarea>
            </div>

            <div class="form-section">
                <h4>Resource Limits</h4>
                <div class="form-row-4">
                    <div class="form-group">
                        <label class="form-label">Max Users</label>
                        <input type="number" id="max_users" class="form-control" value="10" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Services</label>
                        <input type="number" id="max_services" class="form-control" value="50" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disk (GB)</label>
                        <input type="number" id="max_disk_gb" class="form-control" value="100" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bandwidth (GB/mo)</label>
                        <input type="number" id="max_bandwidth_gb" class="form-control" value="1000" min="0">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>Permissions</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="can_create_packages" checked>
                            <span>Can Create Own Packages</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" checked>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span id="saveButtonText">Create Plan</span>
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

    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .package-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
        transition: all 0.2s ease;
    }

    .package-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .package-card.inactive {
        opacity: 0.6;
    }

    .package-header {
        margin-bottom: 12px;
    }

    .package-name {
        margin: 0 0 6px 0;
        font-size: 18px;
        font-weight: 600;
    }

    .package-slug {
        font-size: 11px;
        background: var(--bg-input);
        padding: 2px 8px;
        border-radius: 3px;
    }

    .package-desc {
        color: var(--text-secondary);
        font-size: 13px;
        margin-bottom: 16px;
    }

    .package-resources {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }

    .resource-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }

    .resource-item svg {
        width: 16px;
        height: 16px;
        color: var(--primary);
    }

    .package-features {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .feature svg {
        width: 14px;
        height: 14px;
    }

    .feature.enabled {
        background: rgba(60, 135, 58, 0.15);
        color: var(--success);
    }

    .feature.disabled {
        background: rgba(244, 67, 54, 0.15);
        color: var(--danger);
    }

    .package-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
    }

    .services-count {
        font-size: 13px;
        color: var(--text-secondary);
    }

    .package-actions {
        display: flex;
        gap: 8px;
    }

    /* Modal Styles */
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
        max-width: 600px;
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
    }

    #packageForm {
        padding: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .form-row-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }

    .form-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .form-section h4 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: var(--text-secondary);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 14px;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
        margin-top: 20px;
    }

    @media (max-width: 768px) {

        .form-row,
        .form-row-4 {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    let isEditing = false;

    function showCreateModal() {
        isEditing = false;
        document.getElementById('modalTitle').textContent = 'Create Reseller Plan';
        document.getElementById('saveButtonText').textContent = 'Create Plan';
        document.getElementById('packageForm').reset();
        document.getElementById('packageId').value = '';
        document.getElementById('packageModal').style.display = 'flex';
        lucide.createIcons();
    }

    function editPackage(pkg) {
        isEditing = true;
        document.getElementById('modalTitle').textContent = 'Edit Reseller Plan';
        document.getElementById('saveButtonText').textContent = 'Save Changes';
        document.getElementById('packageId').value = pkg.id;
        document.getElementById('name').value = pkg.name;
        document.getElementById('display_name').value = pkg.display_name;
        document.getElementById('description').value = pkg.description || '';
        document.getElementById('max_users').value = pkg.max_users;
        document.getElementById('max_services').value = pkg.max_services;
        document.getElementById('max_disk_gb').value = pkg.max_disk_gb;
        document.getElementById('max_bandwidth_gb').value = pkg.max_bandwidth_gb;
        document.getElementById('can_create_packages').checked = pkg.can_create_packages;
        document.getElementById('is_active').checked = pkg.is_active;
        document.getElementById('packageModal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeModal() {
        document.getElementById('packageModal').style.display = 'none';
    }

    async function savePackage(e) {
        e.preventDefault();

        const data = {
            name: document.getElementById('name').value,
            display_name: document.getElementById('display_name').value,
            description: document.getElementById('description').value,
            max_users: parseInt(document.getElementById('max_users').value),
            max_services: parseInt(document.getElementById('max_services').value),
            max_disk_gb: parseInt(document.getElementById('max_disk_gb').value),
            max_bandwidth_gb: parseInt(document.getElementById('max_bandwidth_gb').value),
            can_create_packages: document.getElementById('can_create_packages').checked,
            is_active: document.getElementById('is_active').checked
        };

        const packageId = document.getElementById('packageId').value;
        const url = packageId
            ? `<?= $base_url ?? '' ?>/admin/reseller-packages/${packageId}/update`
            : '<?= $base_url ?? '' ?>/admin/reseller-packages/create';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to save plan');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function deletePackage(id, name) {
        if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

        try {
            const response = await fetch(`<?= $base_url ?? '' ?>/admin/reseller-packages/${id}/delete`, {
                method: 'POST'
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to delete plan');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Close modal on backdrop click
    document.getElementById('packageModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>