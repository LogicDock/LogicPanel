<?php
$page_title = 'Master Panel';
$current_page = 'dashboard';
$sidebar_type = 'master';
ob_start();
?>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-card-icon green">
            <i data-lucide="cpu"></i>
        </div>
        <div class="stat-card-value" id="cpu-stat">0%</div>
        <div class="stat-card-label">CPU Usage</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue">
            <i data-lucide="activity"></i>
        </div>
        <div class="stat-card-value" id="mem-stat">Loading...</div>
        <div class="stat-card-label">Memory Usage</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange">
            <i data-lucide="hard-drive"></i>
        </div>
        <div class="stat-card-value" id="disk-stat">Loading...</div>
        <div class="stat-card-label">Disk Usage</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon red">
            <i data-lucide="heart-pulse"></i>
        </div>
        <div class="stat-card-value">Healthy</div>
        <div class="stat-card-label">System Status</div>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="user-plus"></i> Account Functions
        </div>
    </div>
    <div class="card-body">
        <div class="quick-links">
            <a href="accounts/create" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="user-plus"></i>
                </div>
                <div class="quick-link-text">Create Account</div>
            </a>
            <a href="accounts/list" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="users"></i>
                </div>
                <div class="quick-link-text">List Accounts</div>
            </a>
            <a href="packages/list" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="package"></i>
                </div>
                <div class="quick-link-text">Packages</div>
            </a>
            <a href="services/list" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="server"></i>
                </div>
                <div class="quick-link-text">Services</div>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="settings"></i> Server Configuration
        </div>
    </div>
    <div class="card-body">
        <div class="quick-links">
            <a href="settings/config" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="sliders"></i>
                </div>
                <div class="quick-link-text">Basic Setup</div>
            </a>
            <a href="terminal" class="quick-link">
                <div class="quick-link-icon">
                    <i data-lucide="terminal"></i>
                </div>
                <div class="quick-link-text">Root Terminal</div>
            </a>
        </div>
    </div>
</div>

<script>
    // Fetch System Stats (Reusing Logic)
    function updateStats() {
        const token = window.apiToken || sessionStorage.getItem('token');

        if (!token) return;

        fetch('/public/api/master/system/stats', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
            .then(res => res.json())
            .then(data => {
                // CPU
                if (document.getElementById('cpu-stat')) {
                    document.getElementById('cpu-stat').innerText = data.cpu + '%';
                }

                // Memory
                const memStat = document.getElementById('mem-stat');
                if (memStat && data.memory) {
                    if (typeof data.memory === 'string') {
                        memStat.innerText = data.memory;
                    } else if (data.memory.percent) {
                        memStat.innerText = data.memory.percent + '%';
                    }
                }

                // Disk
                const diskStat = document.getElementById('disk-stat');
                if (diskStat && data.disk) {
                    if (typeof data.disk === 'string') {
                        diskStat.innerText = data.disk;
                    } else if (data.disk.percent) {
                        diskStat.innerText = data.disk.percent + '%';
                    }
                }
            })
            .catch(e => console.error('Stats Update Error:', e));
    }

    // Run
    updateStats();
    setInterval(updateStats, 5000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../shared/layouts/main.php';
?>