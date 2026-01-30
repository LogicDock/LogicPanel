<?php
$page_title = 'API Access Keys';
$current_page = 'api_keys';
$sidebar_type = 'master';
ob_start();
?>

<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div class="card-title">API Keys</div>
        <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
            <i data-lucide="plus"></i> Create New Key
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>User</th>
                        <th>Key Prefix</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="keysTableBody">
                    <tr>
                        <td colspan="6" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="lp-modal-overlay">
    <div class="lp-modal-box" style="max-width:500px;">
        <div class="lp-modal-header">
            <h5 class="modal-title" style="margin:0; font-size:1.1rem;">Create API Key</h5>
            <button type="button" class="lp-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="lp-modal-body">
            <form id="createKeyForm">
                <div class="form-group">
                    <label class="form-label">Key Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. WHMCS Integration" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign to User (ID)</label>
                    <input type="number" name="user_id" class="form-control"
                        placeholder="Optional: e.g. 1 (Root Admin)">
                    <small class="text-muted">Leave blank to assign to yourself.</small>
                </div>
                <div class="mt-20 text-right">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createKey()">Generate Key</button>
                </div>
            </form>

            <div id="newKeyDisplay"
                style="display:none; margin-top:20px; background:#f0f9ff; padding:15px; border:1px solid #bae6fd; border-radius:6px;">
                <h6 style="color:#0369a1; margin-bottom:5px;">API Key Generated!</h6>
                <p style="font-size:0.9em; margin-bottom:10px;">Copy this key now. You won't be able to see it again.
                </p>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="generatedKey" class="form-control" readonly
                        style="font-family:monospace; background:white;">
                    <button class="btn btn-secondary btn-sm" onclick="copyKey()">Copy</button>
                </div>
                <div class="mt-10 text-right">
                    <button class="btn btn-primary btn-sm" onclick="location.reload()">Done</button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const API = '/public/api/master/api-keys';
    const token = sessionStorage.getItem('token') || document.querySelector('meta[name="api-token"]')?.content;
    const modal = document.getElementById('createModal');

    async function loadKeys() {
        try {
            const res = await fetch(API, { headers: { 'Authorization': 'Bearer ' + token } });
            const data = await res.json();

            const tbody = document.getElementById('keysTableBody');
            tbody.innerHTML = '';

            if (data.keys && data.keys.length > 0) {
                data.keys.forEach(k => {
                    // Mask key: lp_............1234
                    const shortKey = k.p_key.substring(0, 5) + '................' + k.p_key.substring(k.p_key.length - 4);

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${k.name}</td>
                        <td>${k.user ? k.user.username : 'Unknown'} <small class="text-muted">#${k.user_id}</small></td>
                        <td><code style="background:#f3f4f6; padding:2px 5px; border-radius:4px;">${shortKey}</code></td>
                        <td>${new Date(k.created_at).toLocaleDateString()}</td>
                        <td>${k.last_used_at ? new Date(k.last_used_at).toLocaleString() : '-'}</td>
                        <td class="text-right">
                            <button class="btn btn-danger btn-icon btn-sm" onclick="deleteKey(${k.id})" title="Delete">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                if (window.lucide) lucide.createIcons();
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No API Keys found.</td></tr>';
            }
        } catch (e) {
            console.error(e);
            showNotification('Failed to load API keys', 'error');
        }
    }

    function openCreateModal() {
        document.getElementById('createKeyForm').style.display = 'block';
        document.getElementById('newKeyDisplay').style.display = 'none';
        document.getElementById('createKeyForm').reset();
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    async function createKey() {
        const form = document.getElementById('createKeyForm');
        const data = {
            name: form.name.value,
            user_id: form.user_id.value
        };

        if (!data.name) {
            showNotification('Key name is required', 'warning');
            return;
        }

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (res.ok) {
                showNotification('API Key Created', 'success');
                // Show key
                document.getElementById('createKeyForm').style.display = 'none';
                document.getElementById('newKeyDisplay').style.display = 'block';
                document.getElementById('generatedKey').value = result.api_key.p_key;
            } else {
                showNotification(result.error || 'Failed', 'error');
            }
        } catch (e) {
            console.error(e);
            showNotification('Error creating key', 'error');
        }
    }

    function copyKey() {
        const key = document.getElementById('generatedKey');
        key.select();
        document.execCommand('copy');
        showNotification('Copied to clipboard', 'success');
    }

    async function deleteKey(id) {
        if (!confirm('Are you sure you want to revoke this API Key? integrations using it will stop working.')) return;

        try {
            const res = await fetch(`${API}/${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + token }
            });

            if (res.ok) {
                showNotification('Key deleted', 'success');
                loadKeys();
            } else {
                showNotification('Failed to delete', 'error');
            }
        } catch (e) {
            showNotification('Error', 'error');
        }
    }

    loadKeys();
</script>


<style>
    /* Custom Modal Styles to avoid Bootstrap Conflict */
    .lp-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .lp-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .lp-modal-box {
        background: #ffffff;
        border-radius: 12px;
        width: 90%;
        max-width: 480px;
        padding: 0;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: translateY(10px);
        transition: transform 0.2s ease;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .lp-modal-overlay.active .lp-modal-box {
        transform: translateY(0);
    }

    .lp-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fdfdfd;
        border-radius: 12px 12px 0 0;
    }

    .lp-modal-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .lp-modal-body {
        padding: 24px;
    }

    .lp-close {
        background: transparent;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #9ca3af;
        padding: 4px;
        line-height: 1;
        border-radius: 4px;
        transition: color 0.15s, background 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lp-close:hover {
        color: #111827;
        background: #f3f4f6;
    }

    /* Form Tweaks */
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        display: block;
    }

    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.6rem 0.8rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-20px) scale(0.98);
            opacity: 0;
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>