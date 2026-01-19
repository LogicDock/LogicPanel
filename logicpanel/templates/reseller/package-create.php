<?php
/**
 * Reseller - Create Package
 */
$base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
ob_start();
?>

<div class="container">
    <div class="page-header">
        <h1>Create Package</h1>
        <a href="<?= $base_url ?>/reseller/packages" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            Back to Packages
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="createPackageForm" onsubmit="createPackage(event)">
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Package Name *</label>
                            <input type="text" name="name" required class="form-control" placeholder="starter">
                            <small>Lowercase, no spaces (e.g., starter, pro, business)</small>
                        </div>
                        <div class="form-group">
                            <label>Display Name *</label>
                            <input type="text" name="display_name" required class="form-control"
                                placeholder="Starter Plan">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Package description..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Resource Limits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CPU Limit (cores) *</label>
                            <input type="number" name="cpu_limit" step="0.1" min="0.1" max="8" value="0.5" required
                                class="form-control">
                        </div>
                        <div class="form-group">
                            <label>RAM Limit (MB) *</label>
                            <input type="number" name="memory_limit" min="128" max="16384" value="512" required
                                class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Disk Limit (MB) *</label>
                            <input type="number" name="disk_limit" min="512" max="102400" value="5120" required
                                class="form-control">
                            <small>
                                <?= round(5120 / 1024, 1) ?> GB
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Bandwidth Limit (MB/month)</label>
                            <input type="number" name="bandwidth_limit" min="0" value="51200" class="form-control">
                            <small>0 = Unlimited</small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Limits</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Domains *</label>
                            <input type="number" name="max_domains" min="1" value="3" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Max Databases *</label>
                            <input type="number" name="max_databases" min="0" value="1" required class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Backups *</label>
                            <input type="number" name="max_backups" min="0" value="3" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Max Deployments/Day</label>
                            <input type="number" name="max_deployments_per_day" min="0" value="10" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Features</h3>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="allow_ssh" checked>
                            Allow SSH Access
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="allow_git_deploy" checked>
                            Allow Git Deployment
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" checked>
                            Active (visible to users)
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= $base_url ?>/reseller/packages" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-section h3 {
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: 600;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 14px;
    }

    .form-group small {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: var(--text-secondary);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
</style>

<script>
    function createPackage(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        // Convert checkboxes
        data.allow_ssh = formData.has('allow_ssh');
        data.allow_git_deploy = formData.has('allow_git_deploy');
        data.is_active = formData.has('is_active');

        // Convert numbers
        data.cpu_limit = parseFloat(data.cpu_limit);
        data.memory_limit = parseInt(data.memory_limit);
        data.disk_limit = parseInt(data.disk_limit);
        data.bandwidth_limit = parseInt(data.bandwidth_limit);
        data.max_domains = parseInt(data.max_domains);
        data.max_databases = parseInt(data.max_databases);
        data.max_backups = parseInt(data.max_backups);
        data.max_deployments_per_day = parseInt(data.max_deployments_per_day);

        fetch('<?= $base_url ?>/reseller/packages/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    window.location.href = '<?= $base_url ?>/reseller/packages';
                } else {
                    alert(result.error || 'Failed to create package');
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>