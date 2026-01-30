<?php
$page_title = 'Server Configuration';
$current_page = 'settings';
$sidebar_type = 'master';
ob_start();
?>

<div class="card" style="max-width:800px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title">Server Configuration</div>
    </div>
    <div class="card-body">
        <form id="settingsForm">
            <div class="form-section">
                <h5 style="margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    General Information</h5>
                <div class="row">
                    <div class="col">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. LogicPanel">
                    </div>
                    <div class="col">
                        <label class="form-label">Admin Email</label>
                        <input type="email" name="contact_email" class="form-control" placeholder="admin@example.com">
                    </div>
                </div>
            </div>

            <div class="form-section mt-20">
                <h5 style="margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    Network Settings</h5>
                <div class="row">
                    <div class="col">
                        <label class="form-label">Hostname</label>
                        <input type="text" name="hostname" class="form-control" placeholder="panel.example.com">
                    </div>
                    <div class="col">
                        <label class="form-label d-flex justify-content-between">
                            Server IP
                            <a href="javascript:void(0)" onclick="detectIP(this)" class="text-primary"
                                style="font-size: 11px; text-decoration: none;">
                                <i data-lucide="refresh-cw" style="width:10px; height:10px; vertical-align:middle;"></i>
                                Auto-detect
                            </a>
                        </label>
                        <input type="text" name="server_ip" id="server_ip" class="form-control"
                            placeholder="192.168.1.100">
                    </div>
                </div>
                <div class="row mt-10">
                    <div class="col">
                        <label class="form-label">Shared Base Domain (for subdomains)</label>
                        <input type="text" name="shared_domain" class="form-control"
                            placeholder="e.g. apps.cyberit.cloud">
                    </div>
                </div>
                <div class="row mt-10">
                    <div class="col">
                        <label class="form-label">Default Nameserver 1</label>
                        <input type="text" name="ns1" class="form-control" placeholder="ns1.example.com">
                    </div>
                    <div class="col">
                        <label class="form-label">Default Nameserver 2</label>
                        <input type="text" name="ns2" class="form-control" placeholder="ns2.example.com">
                    </div>
                </div>
                <div class="row mt-10">
                    <div class="col">
                        <label class="form-label">Master Panel Port</label>
                        <input type="number" name="master_port" class="form-control" placeholder="967">
                    </div>
                    <div class="col">
                        <label class="form-label">User Panel Port</label>
                        <input type="number" name="user_port" class="form-control" placeholder="767">
                    </div>
                </div>
            </div>

            <div class="form-section mt-20">
                <h5 style="margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    SSL & Security</h5>
                <div class="row">
                    <div class="col">
                        <label class="form-label">Enable Let's Encrypt SSL</label>
                        <select name="enable_ssl" class="form-control">
                            <option value="1">Enabled (Production)</option>
                            <option value="0">Disabled (Local/Dev)</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Let's Encrypt Email</label>
                        <input type="email" name="letsencrypt_email" class="form-control"
                            placeholder="security@example.com">
                    </div>
                </div>
            </div>

            <div class="form-section mt-20">
                <h5 style="margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    Defaults & Localization</h5>
                <div class="row">
                    <div class="col">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-control">
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">New York</option>
                            <option value="Europe/London">London</option>
                            <option value="Asia/Dhaka">Dhaka</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Allow Registration</label>
                        <select name="allow_registration" class="form-control">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-20">
                <button type="button" class="btn btn-primary" onclick="saveSettings()">Save Configuration</button>
            </div>
        </form>
    </div>
</div>

<style>
    .row {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }

    .col {
        flex: 1;
    }

    @media (max-width: 768px) {
        .row {
            flex-direction: column;
        }
    }
</style>

<script>
    const API = '/public/api/master/settings';
    const token = sessionStorage.getItem('token') || document.querySelector('meta[name="api-token"]')?.content;
    const form = document.getElementById('settingsForm');

    async function loadSettings() {
        try {
            const res = await fetch(API, { headers: { 'Authorization': 'Bearer ' + token } });
            const data = await res.json();

            if (res.ok) {
                // Populate form
                for (const key in data) {
                    if (form[key]) {
                        form[key].value = data[key];
                    }
                }
            } else {
                showNotification('Failed to load settings', 'error');
            }
        } catch (e) {
            console.error(e);
            showNotification('Error loading settings', 'error');
        }
    }

    async function detectIP(link) {
        const originalHtml = link.innerHTML;
        link.innerHTML = '<i class="lucide-loader-2 spin-anim" style="width:10px; height:10px;"></i> Detecting...';
        link.style.pointerEvents = 'none';

        try {
            const res = await fetch(`${API_BASE}/master/settings/detect-ip`, {
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('server_ip').value = data.ip;
                showNotification('IP detected successfully', 'success');
            } else {
                showNotification('Detection failed. Fallback to ' + data.ip, 'warning');
            }
        } catch (e) {
            showNotification('Failed to detect IP', 'error');
        } finally {
            link.innerHTML = originalHtml;
            link.style.pointerEvents = 'auto';
        }
    }

    async function saveSettings() {
        const btn = document.querySelector('.btn-primary');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader-2" class="spin-anim"></i> Saving...`;
        if (window.lucide) lucide.createIcons();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (res.ok) {
                if (result.restart) {
                    showNotification('Port changed. Restarting container...', 'warning');

                    const newMasterPort = result.settings.master_port;
                    const countBtn = document.querySelector('.btn-primary');
                    countBtn.disabled = true;

                    let seconds = 15;
                    const interval = setInterval(() => {
                        countBtn.innerHTML = `Redirecting in ${seconds}s...`;
                        seconds--;
                        if (seconds <= 0) {
                            clearInterval(interval);
                            const host = window.location.hostname;
                            const protocol = window.location.protocol;
                            window.location.href = `${protocol}//${host}:${newMasterPort}/settings/config`;
                        }
                    }, 1000);

                    // Don't reset button
                    return;
                } else {
                    showNotification('Settings saved successfully', 'success');
                }
            } else {
                showNotification(result.error || 'Failed to save', 'error');
            }
        } catch (e) {
            console.error(e);
            showNotification('Network error', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    loadSettings();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>