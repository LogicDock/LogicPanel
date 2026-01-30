<?php
$page_title = 'Addon Domains';
ob_start();
?>

<div class="db-container">
    <div class="db-page-header">
        <h1>Addon Domains</h1>
        <p>Manage custom domains for your applications. Connect your own domains to replace the default subdomains.</p>
    </div>

    <div class="db-section">
        <div class="db-toolbar" style="justify-content: flex-end;">
            <div class="search-box">
                <input type="text" id="searchApp" class="form-control" placeholder="Search applications..."
                    oninput="filterApps()">
            </div>
        </div>

        <div class="table-responsive">
            <table class="db-table" id="domainTable">
                <thead>
                    <tr>
                        <th style="width: 30%;">Application</th>
                        <th style="width: 40%;">Domains</th>
                        <th style="width: 15%;">Status</th>
                        <th class="actions-col" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="appList">
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px;">Loading applications...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manage Domains Modal -->
<div id="domainModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Manage Domains</h3>
            <button class="btn-icon" onclick="closeModal('domainModal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 20px;">
                <h4 id="modalAppName" style="margin:0 0 5px 0; color:var(--primary);">MyApp</h4>
                <p style="font-size:12px; color:var(--text-secondary); margin:0;">Update the domains list below.</p>
            </div>

            <div class="db-form-group">
                <label for="domainInput">Domains (Comma Separated)</label>
                <textarea id="domainInput" class="form-control" rows="4"
                    placeholder="example.com, www.example.com"></textarea>
                <small style="display:block; margin-top:8px; color:var(--text-secondary);">
                    Enter your custom domains separated by commas.<br>
                    <span class="text-warning"><i data-lucide="alert-triangle"
                            style="width:12px;height:12px;display:inline;"></i> Changing domains will restart the
                        application.</span>
                </small>
            </div>

            <div id="sharedDomainSection"
                style="display:none; margin-top:15px; padding:12px; background:rgba(60, 135, 58, 0.05); border:1px dashed var(--primary); border-radius:4px;">
                <label
                    style="display:block; font-size:12px; font-weight:600; color:var(--primary); margin-bottom:5px;">Quick
                    Subdomain</label>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="subdomainPrefix" class="form-control" placeholder="mysub" style="flex:1;">
                    <div
                        style="display:flex; align-items:center; font-size:13px; font-weight:500; color:var(--text-secondary);">
                        .<span id="baseDomainLabel"></span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm mt-10" style="width:100%;" onclick="applySubdomain()">Add
                    Subdomain</button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('domainModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveDomains()">Save Changes</button>
        </div>
    </div>
</div>

<style>
    .db-container {
        padding: 0 15px;
    }

    .db-page-header h1 {
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 5px 0;
    }

    .db-page-header p {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 20px;
    }

    .db-section {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 15px;
    }

    .db-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .db-table th {
        background: var(--bg-input);
        padding: 10px 15px;
        text-align: left;
        font-weight: 600;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
    }

    .db-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }

    .domain-tag {
        display: inline-block;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        margin: 2px;
        font-family: monospace;
        color: var(--text-primary);
    }

    .domain-tag.primary {
        background: rgba(60, 135, 58, 0.1);
        border-color: rgba(60, 135, 58, 0.2);
        color: var(--primary);
        font-weight: 500;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal {
        background: var(--bg-card);
        width: 500px;
        max-width: 90%;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-input);
    }

    .modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 15px 20px;
        background: var(--bg-input);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: var(--text-secondary);
        border-radius: 4px;
    }

    .btn-icon:hover {
        background: rgba(0, 0, 0, 0.05);
        color: var(--text-primary);
    }
</style>

<script>
    const API_BASE = '<?= $base_url ?? '' ?>/public/api';
    const TOKEN = document.querySelector('meta[name="api-token"]')?.content;

    let allApps = [];
    let currentEditingApp = null;
    let sharedDomain = '';

    document.addEventListener('DOMContentLoaded', () => {
        loadApps();
        fetchSettings();
        lucide.createIcons();
    });

    async function fetchSettings() {
        try {
            const res = await fetch(`${API_BASE}/auth/settings`, {
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();
            if (data.shared_domain) {
                sharedDomain = data.shared_domain;
                document.getElementById('baseDomainLabel').innerText = sharedDomain;
                document.getElementById('sharedDomainSection').style.display = 'block';
            }
        } catch (e) {
            console.error('Failed to fetch settings:', e);
        }
    }

    function applySubdomain() {
        const prefix = document.getElementById('subdomainPrefix').value.trim();
        if (!prefix) return;
        
        const full = `${prefix}.${sharedDomain}`;
        const input = document.getElementById('domainInput');
        const current = input.value.trim();
        
        if (current) {
            if (!current.includes(full)) {
                input.value = current + ', ' + full;
            }
        } else {
            input.value = full;
        }
        
        document.getElementById('subdomainPrefix').value = '';
        showNotification('Subdomain added to list', 'success');
    }

    async function loadApps() {
        try {
            const res = await fetch(`${API_BASE}/services`, {
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();

            if (data.services) {
                allApps = data.services;
                renderApps();
            }
        } catch (e) {
            console.error(e);
            document.getElementById('appList').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading applications.</td></tr>';
        }
    }

    function renderApps() {
        const tbody = document.getElementById('appList');
        const search = document.getElementById('searchApp').value.toLowerCase();

        const filtered = allApps.filter(app => app.name.toLowerCase().includes(search));

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding:20px; color:var(--text-muted)">No applications found.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(app => {
            const domains = app.domain ? app.domain.split(',').map(d => d.trim()) : [];
            const domainTags = domains.map((d, i) =>
                `<span class="domain-tag ${i === 0 ? 'primary' : ''}">${d}</span>`
            ).join(' ');

            return `
                <tr>
                    <td>
                        <div style="font-weight:600;">${app.name}</div>
                        <div style="font-size:12px; color:var(--text-secondary); text-transform:capitalize;">${app.type} App</div>
                    </td>
                    <td>${domainTags || '<span class="text-muted">No domains</span>'}</td>
                    <td>
                        <span class="badge ${app.status === 'running' ? 'badge-success' : 'badge-secondary'}">
                            ${app.status}
                        </span>
                    </td>
                    <td class="text-right">
                        <button class="btn btn-sm btn-primary" onclick="openManageModal(${app.id})">
                            <i data-lucide="globe"></i> Manage
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
        lucide.createIcons();
    }

    function filterApps() {
        renderApps();
    }

    function openManageModal(appId) {
        const app = allApps.find(a => a.id === appId);
        if (!app) return;

        currentEditingApp = app;
        document.getElementById('modalAppName').innerText = app.name;
        document.getElementById('domainInput').value = app.domain || '';

        document.getElementById('domainModal').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        currentEditingApp = null;
    }

    async function saveDomains() {
        if (!currentEditingApp) return;

        const newDomains = document.getElementById('domainInput').value;
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;

        btn.innerHTML = '<span class="spin-anim" style="display:inline-block; border:2px solid #fff; border-top-color:transparent; border-radius:50%; width:14px; height:14px;"></span> Saving...';
        btn.disabled = true;

        try {
            const res = await fetch(`${API_BASE}/services/${currentEditingApp.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${TOKEN}`
                },
                body: JSON.stringify({
                    domain: newDomains
                })
            });

            const data = await res.json();

            if (res.ok) {
                showNotification('Domains updated successfully. Application restarting...', 'success');
                closeModal('domainModal');
                loadApps(); // Refresh list
            } else {
                showNotification(data.message || data.error || 'Failed to update domains', 'error');
            }
        } catch (e) {
            showNotification('Network error occurred', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // Fallback confirmation if needed
    if (typeof showNotification === 'undefined') {
        window.showNotification = (msg, type) => alert(msg);
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>