<?php
$page_title = 'Cron Jobs';
ob_start();
?>

<div class="db-container">
    <div class="db-page-header">
        <h1>Cron Jobs</h1>
        <p>Automate repetitive tasks directly within your application containers.</p>
    </div>

    <!-- Add New Job Section -->
    <div class="db-section">
        <div class="db-section-header">
            <h3>Add New Cron Job</h3>
        </div>
        <div class="db-section-body">
            <form id="addCronForm" onsubmit="createCronJob(event)">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Application</label>
                        <select id="serviceSelect" class="form-control" required>
                            <option value="">Loading applications...</option>
                        </select>
                        <small class="form-text text-muted">Select the container where the command will run.</small>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Common Settings</label>
                        <select id="commonSettings" class="form-control" onchange="applyCommonSetting()">
                            <option value="">-- Select Common Setting --</option>
                            <option value="* * * * *">Every Minute (* * * * *)</option>
                            <option value="*/5 * * * *">Every 5 Minutes (*/5 * * * *)</option>
                            <option value="0,30 * * * *">Twice Per Hour (0,30 * * * *)</option>
                            <option value="0 * * * *">Once Per Hour (0 * * * *)</option>
                            <option value="0 0 * * *">Once Per Day (0 0 * * *)</option>
                            <option value="0 0 * * 0">Once Per Week (0 0 * * 0)</option>
                            <option value="0 0 1 * *">Once Per Month (0 0 1 * *)</option>
                            <option value="0 0 1 1 *">Once Per Year (0 0 1 1 *)</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Cron Schedule</label>
                        <input type="text" id="cronSchedule" class="form-control" placeholder="* * * * *" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Command (Full Path)</label>
                    <input type="text" id="cronCommand" class="form-control"
                        placeholder="php /var/www/html/artisan schedule:run" required>
                    <small class="form-text text-muted">Enter the command as you would run it in the terminal.</small>
                </div>

                <div class="form-actions text-right">
                    <button type="submit" class="btn btn-primary" id="addBtn">
                        <i data-lucide="plus"></i> Add Cron Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Jobs Section -->
    <div class="db-section mt-4">
        <div class="db-section-header">
            <h3>Current Cron Jobs</h3>
        </div>
        <div class="table-responsive">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Schedule</th>
                        <th>Command</th>
                        <th>Last Run</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="cronList">
                    <tr>
                        <td colspan="5" class="text-center p-4">Loading cron jobs...</td>
                    </tr>
                </tbody>
            </table>
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
        overflow: hidden;
    }

    .db-section-header {
        padding: 15px 20px;
        background: var(--bg-input);
        border-bottom: 1px solid var(--border-color);
    }

    .db-section-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .db-section-body {
        padding: 20px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }

    .col-md-4 {
        flex: 1;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 13px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--bg-input);
        color: var(--text-primary);
    }

    .form-text {
        font-size: 12px;
        margin-top: 4px;
    }

    .mt-4 {
        margin-top: 20px;
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
        vertical-align: middle;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary {
        background: #3C873A;
        color: #fff;
    }

    .btn-primary:hover {
        background: #2d6a2e;
    }

    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }

    .btn-danger {
        background: #d32f2f;
        color: #fff;
    }

    .btn-secondary {
        background: #555;
        color: #fff;
    }
</style>

<script>
    const API_BASE = '<?= $base_url ?? '' ?>/public/api';
    const TOKEN = document.querySelector('meta[name="api-token"]')?.content;
    let services = [];

    document.addEventListener('DOMContentLoaded', () => {
        loadServices();
        loadCronJobs();
        lucide.createIcons();
    });

    function applyCommonSetting() {
        const val = document.getElementById('commonSettings').value;
        if (val) {
            document.getElementById('cronSchedule').value = val;
        }
    }

    async function loadServices() {
        try {
            const res = await fetch(`${API_BASE}/services`, {
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();
            services = data.services || [];

            const select = document.getElementById('serviceSelect');
            if (services.length === 0) {
                select.innerHTML = '<option value="">No applications found</option>';
            } else {
                select.innerHTML = '<option value="">-- Select Application --</option>' +
                    services.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function loadCronJobs() {
        try {
            const res = await fetch(`${API_BASE}/cron`, {
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();
            renderJobs(data.jobs || []);
        } catch (e) {
            console.error(e);
            document.getElementById('cronList').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading jobs.</td></tr>';
        }
    }

    function renderJobs(jobs) {
        const tbody = document.getElementById('cronList');
        if (jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No cron jobs configured.</td></tr>';
            return;
        }

        tbody.innerHTML = jobs.map(job => `
            <tr>
                <td><strong>${job.service ? job.service.name : '<span class="text-danger">Unknown</span>'}</strong></td>
                <td><code>${job.schedule}</code></td>
                <td><code>${job.command}</code></td>
                <td>
                    ${job.last_run ? new Date(job.last_run).toLocaleString() : 'Never'}
                    ${job.last_result ? `<br><small class="text-muted" title="${job.last_result}">Result logged</small>` : ''}
                </td>
                <td class="text-right">
                    <button class="btn btn-sm btn-secondary" onclick="runJob(${job.id})" title="Run Now">
                        <i data-lucide="play"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteJob(${job.id})" title="Delete">
                        <i data-lucide="trash-2"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        lucide.createIcons();
    }

    async function createCronJob(e) {
        e.preventDefault();
        const btn = document.getElementById('addBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Adding...';

        const payload = {
            service_id: document.getElementById('serviceSelect').value,
            schedule: document.getElementById('cronSchedule').value,
            command: document.getElementById('cronCommand').value
        };

        try {
            const res = await fetch(`${API_BASE}/cron`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${TOKEN}`
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok) {
                document.getElementById('addCronForm').reset();
                loadCronJobs();
                // Optional: success toast
            } else {
                alert(data.error || 'Failed to create job');
            }
        } catch (err) {
            alert('Network error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            lucide.createIcons();
        }
    }

    async function deleteJob(id) {
        if (!confirm('Are you sure you want to delete this cron job?')) return;

        try {
            const res = await fetch(`${API_BASE}/cron/${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            if (res.ok) {
                loadCronJobs();
            } else {
                alert('Failed to delete job');
            }
        } catch (e) {
            alert('Network error');
        }
    }

    async function runJob(id) {
        if (!confirm('Run this task immediately?')) return;

        try {
            const res = await fetch(`${API_BASE}/cron/${id}/run`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${TOKEN}` }
            });
            const data = await res.json();
            if (res.ok) {
                alert('Job executed successfully!\nOutput:\n' + (data.output || '(No output)'));
                loadCronJobs();
            } else {
                alert('Failed to run job: ' + (data.error || 'Unknown error'));
            }
        } catch (e) {
            alert('Network error');
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>