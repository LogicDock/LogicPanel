<?php
/**
 * Reseller - Packages List
 */
$base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
ob_start();
?>

<div class="container">
    <div class="page-header">
        <h1>My Packages</h1>
        <div class="header-actions">
            <a href="<?= $base_url ?>/reseller/packages/create" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Create Package
            </a>
        </div>
    </div>

    <?php if (empty($packages)): ?>
        <div class="empty-state">
            <i data-lucide="package"></i>
            <h3>No Packages Yet</h3>
            <p>Create your first package to get started</p>
            <a href="<?= $base_url ?>/reseller/packages/create" class="btn btn-primary">
                Create Package
            </a>
        </div>
    <?php else: ?>
        <div class="packages-grid">
            <?php foreach ($packages as $package): ?>
                <div class="package-card">
                    <div class="package-header">
                        <h3>
                            <?= htmlspecialchars($package->display_name) ?>
                        </h3>
                        <span class="badge <?= $package->is_active ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $package->is_active ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>

                    <div class="package-resources">
                        <div class="resource-item">
                            <i data-lucide="cpu"></i>
                            <span>
                                <?= $package->cpu_limit ?> CPU
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="hard-drive"></i>
                            <span>
                                <?= $package->memory_limit ?> MB RAM
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="database"></i>
                            <span>
                                <?= round($package->disk_limit / 1024, 1) ?> GB Disk
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="globe"></i>
                            <span>
                                <?= $package->max_domains ?> Domains
                            </span>
                        </div>
                    </div>

                    <?php if ($package->description): ?>
                        <p class="package-description">
                            <?= htmlspecialchars($package->description) ?>
                        </p>
                    <?php endif; ?>

                    <div class="package-actions">
                        <button class="btn btn-sm btn-outline" onclick="editPackage(<?= $package->id ?>)">
                            <i data-lucide="edit"></i>
                            Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deletePackage(<?= $package->id ?>)">
                            <i data-lucide="trash-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .package-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
    }

    .package-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .package-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .package-resources {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }

    .resource-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    .resource-item svg {
        width: 16px;
        height: 16px;
    }

    .package-description {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 15px;
    }

    .package-actions {
        display: flex;
        gap: 10px;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 20px;
        color: var(--text-secondary);
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 10px;
    }

    .empty-state p {
        color: var(--text-secondary);
        margin-bottom: 20px;
    }
</style>

<script>
    function editPackage(id) {
        window.location.href = '<?= $base_url ?>/reseller/packages/' + id + '/edit';
    }

    function deletePackage(id) {
        if (!confirm('Are you sure you want to delete this package?')) {
            return;
        }

        fetch(`<?= $base_url ?>/reseller/packages/${id}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete package');
                }
            });
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>