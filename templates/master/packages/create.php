<?php
$page_title = 'Create Package';
$current_page = 'packages_list';
$sidebar_type = 'master';
ob_start();
?>

<div class="card" style="max-width:800px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title">Add a New Package</div>
    </div>
    <div class="card-body">
        <form id="createPackageForm">
            <div class="form-group">
                <label class="form-label">Package Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Gold">
            </div>

            <div class="divider">Hosting Account Limits</div>

            <div class="row" style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="col" style="flex:1;">
                    <label class="form-label">Disk Storage</label>
                    <div style="display:flex; gap:5px;">
                        <input type="number" name="storage_limit" class="form-control" placeholder="1024">
                        <select id="storage_unit" class="form-control" style="width:80px;">
                            <option value="MB">MB</option>
                            <option value="GB" selected>GB</option>
                        </select>
                    </div>
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label">Bandwidth</label>
                    <div style="display:flex; gap:5px;">
                        <input type="number" name="bandwidth_limit" class="form-control" placeholder="10">
                        <select id="bandwidth_unit" class="form-control" style="width:80px;">
                            <option value="MB">MB</option>
                            <option value="GB" selected>GB</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row" style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="col" style="flex:1;">
                    <label class="form-label text-muted">Max Email Accounts <small>(Coming Soon)</small></label>
                    <input type="number" name="email_limit" class="form-control" placeholder="Disabled" disabled
                        style="background:#f3f4f6; cursor:not-allowed;">
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label text-muted">Max FTP Accounts <small>(Coming Soon)</small></label>
                    <input type="number" name="ftp_limit" class="form-control" placeholder="Disabled" disabled
                        style="background:#f3f4f6; cursor:not-allowed;">
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label">Max Databases</label>
                    <input type="number" name="db_limit" class="form-control" placeholder="e.g. 3">
                </div>
            </div>

            <div class="row" style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="col" style="flex:1;">
                    <label class="form-label">Max Subdomains</label>
                    <input type="number" name="max_subdomains" class="form-control" placeholder="e.g. 5">
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label text-muted">Max Parked Domains <small>(Coming Soon)</small></label>
                    <input type="number" name="max_parked_domains" class="form-control" placeholder="Disabled" disabled
                        style="background:#f3f4f6; cursor:not-allowed;">
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label">Max Addon Domains</label>
                    <input type="number" name="max_addon_domains" class="form-control" placeholder="e.g. 0">
                </div>
            </div>

            <div class="divider">Application/Container Limits</div>

            <div class="row" style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="col" style="flex:1;">
                    <label class="form-label">CPU Limit (Cores)</label>
                    <input type="number" name="cpu_limit" class="form-control" step="0.1" placeholder="e.g. 0.50">
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label">Memory Limit (RAM)</label>
                    <div style="display:flex; gap:5px;">
                        <input type="number" name="memory_limit" class="form-control" placeholder="512">
                        <select id="memory_unit" class="form-control" style="width:80px;">
                            <option value="MB" selected>MB</option>
                            <option value="GB">GB</option>
                        </select>
                    </div>
                </div>
                <div class="col" style="flex:1;">
                    <label class="form-label">Max Services (Apps)</label>
                    <input type="number" name="max_services" class="form-control" placeholder="e.g. 1">
                </div>
            </div>

            <!-- Price removed as per request (WHMCS handled) -->

            <div class="mt-20" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <button type="button" class="btn btn-primary" style="justify-content:center;" onclick="createPackage()">
                    <i data-lucide="plus"></i> Create Package
                </button>
                <a href="list" class="btn btn-secondary" style="justify-content:center; text-align:center;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    async function createPackage() {
        const btn = document.querySelector('.btn-primary');
        const originalText = btn.innerHTML;
        const form = document.getElementById('createPackageForm');

        // Utility to convert back to MB
        function toMB(val, unit) {
            val = parseFloat(val) || 0;
            if (unit === 'GB') return val * 1024;
            return val;
        }

        const storageMB = toMB(form.storage_limit.value, document.getElementById('storage_unit').value);
        const bwMB = toMB(form.bandwidth_limit.value, document.getElementById('bandwidth_unit').value);
        const memMB = toMB(form.memory_limit.value, document.getElementById('memory_unit').value);

        const data = {
            name: form.name.value,
            storage_limit: storageMB,
            bandwidth_limit: bwMB,
            memory_limit: memMB,
            email_limit: 0, // Disabled
            ftp_limit: 0, // Disabled
            db_limit: parseInt(form.db_limit.value) || 0,
            max_subdomains: parseInt(form.max_subdomains.value) || 0,
            max_parked_domains: 0, // Disabled
            max_addon_domains: parseInt(form.max_addon_domains.value) || 0,
            cpu_limit: parseFloat(form.cpu_limit.value) || 0.5,
            max_services: parseInt(form.max_services.value) || 1
        };

        if (!data.name) {
            showNotification('Package Name is required', 'warning');
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = `<i class="lucide-loader-2 spin-anim"></i> Creating...`;
            if (window.lucide) lucide.createIcons();

            const token = sessionStorage.getItem('token') || document.querySelector('meta[name="api-token"]')?.content;

            const res = await fetch('/public/api/master/packages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (res.ok) {
                showNotification('Package created successfully!', 'success');
                setTimeout(() => window.location.href = 'list', 1000); // Small delay to show notification
            } else {
                showNotification(result.error || 'Unknown error', 'error');
            }
        } catch (e) {
            console.error(e);
            showNotification(e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>