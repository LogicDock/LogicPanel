<?php
$page_title = 'Packages';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Hosting Packages</h1>
    <button onclick="showCreateModal()" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Create Package
    </button>
</div>

<!-- Packages List -->
<div class="card">
    <?php if (empty($packages)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="package"></i>
            </div>
            <h3 class="empty-state-title">No Packages</h3>
            <p class="empty-state-text">Create hosting packages with resource limits for WHMCS integration.</p>
            <button onclick="showCreateModal()" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Create Package
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
                            <i data-lucide="cpu"></i>
                            <span>
                                <?= $package->cpu_display ?>
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="memory-stick"></i>
                            <span>
                                <?= $package->memory_display ?>
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="hard-drive"></i>
                            <span>
                                <?= $package->disk_display ?>
                            </span>
                        </div>
                        <div class="resource-item">
                            <i data-lucide="activity"></i>
                            <span>
                                <?= $package->bandwidth_display ?>/mo
                            </span>
                        </div>
                    </div>

                    <div class="package-limits">
                        <span><strong>
                                <?= $package->max_domains ?>
                            </strong> Domains</span>
                        <span><strong>
                                <?= $package->max_databases ?>
                            </strong> Database</span>
                        <span><strong>
                                <?= $package->max_backups ?>
                            </strong> Backups</span>
                    </div>

                    <div class="package-features">
                        <?php if ($package->allow_ssh): ?>
                            <span class="feature enabled"><i data-lucide="check"></i> SSH</span>
                        <?php else: ?>
                            <span class="feature disabled"><i data-lucide="x"></i> SSH</span>
                        <?php endif; ?>

                        <?php if ($package->allow_git_deploy): ?>
                            <span class="feature enabled"><i data-lucide="check"></i> Git Deploy</span>
                        <?php else: ?>
                            <span class="feature disabled"><i data-lucide="x"></i> Git Deploy</span>
                        <?php endif; ?>
                    </div>

                    <div class="package-stats">
                        <span class="text-muted">
                            <?= $package->services_count ?? 0 ?> services using this package
                        </span>
                    </div>

                    <div class="package-actions">
                        <button onclick="editPackage(<?= $package->id ?>)" class="btn btn-sm btn-secondary">
                            <i data-lucide="edit"></i>
                            Edit
                        </button>
                        <button onclick="deletePackage(<?= $package->id ?>, '<?= htmlspecialchars($package->name) ?>')"
                            class="btn btn-sm btn-danger">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- WHMCS API Info -->
<div class="card mt-20">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="link"></i>
            WHMCS Integration
        </h2>
    </div>
    <div class="card-body">
        <p class="text-muted mb-15">WHMCS will automatically fetch these packages via API. Use the following endpoint in
            your WHMCS module configuration:</p>

        <div class="form-group">
            <label class="form-label">API Endpoint</label>
            <div class="conn-string">
                <code><?= ($_SERVER['REQUEST_SCHEME'] ?? 'https') ?>://<?= $_SERVER['HTTP_HOST'] ?>/api/packages</code>
                <button
                    onclick="copyToClipboard('<?= ($_SERVER['REQUEST_SCHEME'] ?? 'https') ?>://<?= $_SERVER['HTTP_HOST'] ?>/api/packages')"
                    class="copy-btn">
                    <i data-lucide="copy"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="packageModal" class="modal" style="display: none;">
    <div class="modal-backdrop" onclick="hideModal()"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Create Package</h2>
            <button type="button" onclick="hideModal()" class="modal-close" title="Close">&times;</button>
        </div>
        <form id="packageForm" class="modal-body">
            <input type="hidden" name="id" id="packageId">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Package Name (slug)</label>
                    <input type="text" name="name" id="pkgName" placeholder="starter" class="form-control" required
                        pattern="[a-z0-9_-]+">
                    <small class="text-muted">Lowercase, no spaces. Used in API.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Name</label>
                    <input type="text" name="display_name" id="pkgDisplayName" placeholder="Starter Plan"
                        class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" id="pkgDescription" placeholder="Perfect for small projects"
                    class="form-control">
            </div>

            <h4 class="form-section-title">Resource Limits</h4>

            <div class="form-row form-row-4">
                <div class="form-group">
                    <label class="form-label">RAM (MB)</label>
                    <input type="number" name="memory_limit" id="pkgMemory" value="512" min="128" class="form-control"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">CPU (Cores)</label>
                    <input type="number" name="cpu_limit" id="pkgCpu" value="0.5" min="0.1" step="0.1"
                        class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Disk (MB)</label>
                    <input type="number" name="disk_limit" id="pkgDisk" value="5120" min="512" class="form-control"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Bandwidth (MB/mo)</label>
                    <input type="number" name="bandwidth_limit" id="pkgBandwidth" value="51200" min="0"
                        class="form-control">
                    <small class="text-muted">0 = Unlimited</small>
                </div>
            </div>

            <h4 class="form-section-title">Feature Limits</h4>

            <div class="form-row form-row-4">
                <div class="form-group">
                    <label class="form-label">Max Domains</label>
                    <input type="number" name="max_domains" id="pkgDomains" value="3" min="1" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Databases</label>
                    <input type="number" name="max_databases" id="pkgDatabases" value="1" min="0" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Backups</label>
                    <input type="number" name="max_backups" id="pkgBackups" value="3" min="0" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Deploys/Day</label>
                    <input type="number" name="max_deployments_per_day" id="pkgDeploys" value="10" min="1"
                        class="form-control">
                </div>
            </div>

            <h4 class="form-section-title">Features</h4>

            <div class="form-row">
                <label class="checkbox-item">
                    <input type="checkbox" name="allow_ssh" id="pkgSsh" checked>
                    <span>Allow SSH/Terminal Access</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="allow_git_deploy" id="pkgGit" checked>
                    <span>Allow Git Deployment</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="is_active" id="pkgActive" checked>
                    <span>Active (Available in WHMCS)</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" onclick="hideModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Package</button>
            </div>
        </form>
    </div>
</div>

<style>
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .package-card {
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 20px;
        transition: all 0.15s ease;
    }

    .package-card:hover {
        border-color: var(--primary);
    }

    .package-card.inactive {
        opacity: 0.6;
    }

    .package-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .package-name {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .package-slug {
        font-size: 11px;
        background: var(--bg-card);
        padding: 2px 6px;
        border-radius: 3px;
    }

    .package-desc {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 15px;
    }

    .package-resources {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 15px;
    }

    .package-resources .resource-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        padding: 8px 10px;
        background: var(--bg-card);
        border-radius: 4px;
    }

    .package-resources .resource-item svg {
        width: 16px;
        height: 16px;
        color: var(--primary);
    }

    .package-limits {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 10px;
    }

    .package-features {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .package-features .feature {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 3px;
    }

    .package-features .feature svg {
        width: 12px;
        height: 12px;
    }

    .package-features .feature.enabled {
        background: rgba(60, 135, 58, 0.1);
        color: var(--primary);
    }

    .package-features .feature.disabled {
        background: rgba(158, 158, 158, 0.1);
        color: var(--text-muted);
    }

    .package-stats {
        font-size: 12px;
        margin-bottom: 15px;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }

    .package-actions {
        display: flex;
        gap: 10px;
    }

    /* Form styles */
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .form-row-4 {
        grid-template-columns: repeat(4, 1fr);
    }

    .form-section-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        margin: 20px 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--border-color);
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 13px;
    }

    .checkbox-item input {
        margin: 0;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .modal-lg {
        max-width: 600px;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-secondary);
        font-size: 20px;
        cursor: pointer;
        transition: all 0.15s ease;
        line-height: 1;
    }

    .modal-close:hover {
        background: var(--danger);
        border-color: var(--danger);
        color: white;
    }

    .conn-string {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: var(--bg-input);
        border-radius: var(--border-radius);
    }

    .conn-string code {
        font-size: 12px;
        word-break: break-all;
    }

    .copy-btn {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: var(--text-muted);
    }

    .copy-btn:hover {
        color: var(--primary);
    }

    .copy-btn svg {
        width: 14px;
        height: 14px;
    }

    .mt-20 {
        margin-top: 20px;
    }

    @media (max-width: 768px) {

        .form-row,
        .form-row-4 {
            grid-template-columns: 1fr;
        }

        .packages-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    // Store packages data for editing
    const packagesData = <?= json_encode(array_map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'display_name' => $p->display_name,
            'description' => $p->description,
            'memory_limit' => $p->memory_limit,
            'cpu_limit' => $p->cpu_limit,
            'disk_limit' => $p->disk_limit,
            'bandwidth_limit' => $p->bandwidth_limit,
            'max_domains' => $p->max_domains,
            'max_databases' => $p->max_databases,
            'max_backups' => $p->max_backups,
            'max_deployments_per_day' => $p->max_deployments_per_day,
            'allow_ssh' => (bool) $p->allow_ssh,
            'allow_git_deploy' => (bool) $p->allow_git_deploy,
            'is_active' => (bool) $p->is_active
        ];
    }, $packages ? $packages->all() : [])) ?>;

    function showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Package';
        document.getElementById('packageForm').reset();
        document.getElementById('packageId').value = '';
        document.getElementById('pkgActive').checked = true;
        document.getElementById('pkgSsh').checked = true;
        document.getElementById('pkgGit').checked = true;
        document.getElementById('packageModal').style.display = 'flex';

        // Auto scroll to modal
        setTimeout(() => {
            document.getElementById('packageModal').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function editPackage(id) {
        const pkg = packagesData.find(p => p.id == id);
        if (!pkg) return;

        document.getElementById('modalTitle').textContent = 'Edit Package';
        document.getElementById('packageId').value = pkg.id;
        document.getElementById('pkgName').value = pkg.name;
        document.getElementById('pkgDisplayName').value = pkg.display_name;
        document.getElementById('pkgDescription').value = pkg.description || '';
        document.getElementById('pkgMemory').value = pkg.memory_limit;
        document.getElementById('pkgCpu').value = pkg.cpu_limit;
        document.getElementById('pkgDisk').value = pkg.disk_limit;
        document.getElementById('pkgBandwidth').value = pkg.bandwidth_limit;
        document.getElementById('pkgDomains').value = pkg.max_domains;
        document.getElementById('pkgDatabases').value = pkg.max_databases;
        document.getElementById('pkgBackups').value = pkg.max_backups;
        document.getElementById('pkgDeploys').value = pkg.max_deployments_per_day;
        document.getElementById('pkgSsh').checked = pkg.allow_ssh;
        document.getElementById('pkgGit').checked = pkg.allow_git_deploy;
        document.getElementById('pkgActive').checked = pkg.is_active;
        document.getElementById('packageModal').style.display = 'flex';

        // Auto scroll to modal
        setTimeout(() => {
            document.getElementById('packageModal').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function hideModal() {
        document.getElementById('packageModal').style.display = 'none';
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        });
    }

    async function deletePackage(id, name) {
        if (!confirm('Delete package "' + name + '"? Services using this package will not be affected.')) return;

        try {
            const response = await fetch('<?= $base_url ?>/admin/packages/' + id + '/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to delete package');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    document.getElementById('packageForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            id: formData.get('id') || null,
            name: formData.get('name'),
            display_name: formData.get('display_name'),
            description: formData.get('description'),
            memory_limit: parseInt(formData.get('memory_limit')),
            cpu_limit: parseFloat(formData.get('cpu_limit')),
            disk_limit: parseInt(formData.get('disk_limit')),
            bandwidth_limit: parseInt(formData.get('bandwidth_limit')),
            max_domains: parseInt(formData.get('max_domains')),
            max_databases: parseInt(formData.get('max_databases')),
            max_backups: parseInt(formData.get('max_backups')),
            max_deployments_per_day: parseInt(formData.get('max_deployments_per_day')),
            allow_ssh: formData.get('allow_ssh') === 'on',
            allow_git_deploy: formData.get('allow_git_deploy') === 'on',
            is_active: formData.get('is_active') === 'on'
        };

        try {
            const url = data.id
                ? '<?= $base_url ?>/admin/packages/' + data.id + '/update'
                : '<?= $base_url ?>/admin/packages/create';

            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Failed to save package');
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