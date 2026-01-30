<?php
$page_title = 'Tools';
$current_page = 'dashboard';

// Load system settings
$sysConfig = [];
$configFile = __DIR__ . '/../../../config/settings.json';
if (file_exists($configFile)) {
    $sysConfig = json_decode(file_get_contents($configFile), true);
}
// Default to SERVER_ADDR if not set in config, or fallback to placeholder
$serverIp = $sysConfig['server_ip'] ?? ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
$ns1 = $sysConfig['ns1'] ?? 'ns1.cyberit.cloud';
$ns2 = $sysConfig['ns2'] ?? 'ns2.cyberit.cloud';

ob_start();
?>

<div class="tools-layout">
    <!-- Main Tools Area -->
    <div class="tools-main">

        <!-- General Section -->
        <div class="tool-section-minimal" data-section="general">
            <div class="section-header-minimal" onclick="toggleSection('general')">
                <div class="section-label">Management</div>
                <i data-lucide="chevron-up" class="section-toggle-icon"></i>
            </div>
            <div class="section-content-minimal">
                <div class="tools-grid-minimal">
                    <a href="/apps/overview" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="server"></i>
                        </div>
                        <span class="tool-name-minimal">Overview</span>
                    </a>
                    <a href="/apps/files" target="_blank" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="folder"></i>
                        </div>
                        <span class="tool-name-minimal">File Manager</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/public/adminer.php" target="_blank" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="database"></i>
                        </div>
                        <span class="tool-name-minimal">Manage DBs</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Databases Section -->
        <div class="tool-section-minimal" data-section="databases">
            <div class="section-header-minimal" onclick="toggleSection('databases')">
                <div class="section-label">DATABASES</div>
                <i data-lucide="chevron-up" class="section-toggle-icon"></i>
            </div>
            <div class="section-content-minimal">
                <div class="tools-grid-minimal">
                    <a href="<?= $base_url ?? '' ?>/databases/mysql" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="database"></i>
                        </div>
                        <span class="tool-name-minimal">MySQL</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/databases/postgresql" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="database"></i>
                        </div>
                        <span class="tool-name-minimal">PostgreSQL</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/databases/mongodb" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="database"></i>
                        </div>
                        <span class="tool-name-minimal">MongoDB</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Software Section -->
        <div class="tool-section-minimal" data-section="software">
            <div class="section-header-minimal" onclick="toggleSection('software')">
                <div class="section-label">SOFTWARE</div>
                <i data-lucide="chevron-up" class="section-toggle-icon"></i>
            </div>
            <div class="section-content-minimal">
                <div class="tools-grid-minimal">
                    <a href="<?= $base_url ?? '' ?>/apps/nodejs" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="hexagon"></i>
                        </div>
                        <span class="tool-name-minimal">Node.js App</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/apps/python" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="codepen"></i>
                        </div>
                        <span class="tool-name-minimal">Python App</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/apps/terminal" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="terminal"></i>
                        </div>
                        <span class="tool-name-minimal">Terminal</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Backup Section -->
        <div class="tool-section-minimal" data-section="backup">
            <div class="section-header-minimal" onclick="toggleSection('backup')">
                <div class="section-label">SPECIAL</div>
                <i data-lucide="chevron-up" class="section-toggle-icon"></i>
            </div>
            <div class="section-content-minimal">
                <div class="tools-grid-minimal">
                    <a href="<?= $base_url ?? '' ?>/backups" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="archive"></i>
                        </div>
                        <span class="tool-name-minimal">Backup Wizard</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/domains" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="globe"></i>
                        </div>
                        <span class="tool-name-minimal">Domains</span>
                    </a>
                    <a href="<?= $base_url ?? '' ?>/cron" class="tool-card-minimal">
                        <div class="tool-icon-minimal">
                            <i data-lucide="clock"></i>
                        </div>
                        <span class="tool-name-minimal">Cron Jobs</span>
                    </a>
                </div>
            </div>
        </div>

    </div> <!-- End tools-main -->

    <!-- Right Sidebar - General Information -->
    <div class="tools-sidebar">

        <!-- Server Info -->
        <div class="info-card">
            <div class="info-card-header">Server Information</div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Server IP Address</div>
                    <div class="info-value">
                        <?= htmlspecialchars($serverIp) ?>
                        <span style="cursor:pointer; margin-left:5px; display:inline-flex; align-items:center;"
                            onclick="copyToClipboardWithAnim('<?= htmlspecialchars($serverIp) ?>', this)">
                            <i data-lucide="copy" style="width:12px; height:12px;"></i>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nameservers</div>
                    <div class="info-value" style="font-size: 12px; line-height: 1.4;">
                        <div><?= htmlspecialchars($ns1) ?></div>
                        <div><?= htmlspecialchars($ns2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-header">General Information</div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Current User</div>
                    <div class="info-value"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Select Application Domain</div>
                    <div class="info-value">
                        <select onchange="if(this.value) window.open('http://' + this.value, '_blank')"
                            class="form-control" style="font-size: 13px; padding: 4px 8px; height: 30px;">
                            <option value="">Select & Visit...</option>
                            <?php if (!empty($services) && is_array($services)): ?>
                                <?php foreach ($services as $svc): ?>
                                    <?php if (!empty($svc['domain'])): ?>
                                        <?php
                                        // Handle multiple domains (comma separated)
                                        $domains = explode(',', $svc['domain']);
                                        foreach ($domains as $d):
                                            $d = trim($d);
                                            if (empty($d))
                                                continue;

                                            // Check if it's a full URL or just a domain
                                            $url = strpos($d, 'http') === 0 ? $d : 'http://' . $d;
                                            ?>
                                            <option value="<?= htmlspecialchars($d) ?>">
                                                <?= htmlspecialchars($svc['name']) ?> (<?= htmlspecialchars($d) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Container Status</div>
                    <div class="info-value">
                        <span class="badge badge-success">
                            <span class="badge-dot"></span>
                            <?= $runningCount ?? 0 ?> Running
                        </span>
                        <?php if (($stoppedCount ?? 0) > 0): ?>
                            <span class="badge badge-secondary" style="margin-left: 5px;">
                                <?= $stoppedCount ?> Stopped
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Total Services</div>
                    <div class="info-value"><?= $serviceCount ?? 0 ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Last Login IP</div>
                    <div class="info-value"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown') ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Server Time</div>
                    <div class="info-value"><?= date('Y-m-d H:i:s') ?></div>
                </div>
            </div>
        </div>

        <!-- Resource Usage -->
        <div class="info-card">
            <div class="info-card-header">Resource Usage</div>
            <div class="info-card-body">
                <div class="resource-item">
                    <div class="resource-header">
                        <span>CPU Usage</span>
                        <span id="cpu-val">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" id="cpu-bar" style="width: 0%"></div>
                    </div>
                </div>

                <div class="resource-item">
                    <div class="resource-header">
                        <span>Memory Usage</span>
                        <span id="mem-val">Loading...</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" id="mem-bar" style="width: 0%"></div>
                    </div>
                </div>

                <div class="resource-item">
                    <div class="resource-header">
                        <span>Disk Space</span>
                        <span id="disk-val">Loading...</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" id="disk-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateSystemStats() {
        fetch('<?= $base_url ?>/public/api/system/stats', {
            headers: { 'Authorization': 'Bearer <?= $_SESSION['lp_session_token'] ?? '' ?>' }
        })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                // Update CPU breakdown if available or just total
                let cpuVal = 0;
                if (typeof data.cpu === 'object') {
                    // if cpu_stats structure
                    cpuVal = data.cpu.total || 0;
                } else {
                    cpuVal = parseFloat(data.cpu) || 0;
                }

                document.getElementById('cpu-val').innerText = cpuVal + '%';
                document.getElementById('cpu-bar').style.width = cpuVal + '%';

                // Update Mem
                // Backend now returns pre-formatted string like "10MB / 1GB" maybe?
                // Or object? Let's assume the previous API returned 'memory' object {used, total, percent}
                // But SystemController might be different. Let's check SystemController.

                // Assuming standard Monitor/SystemStats structure:
                if (data.memory) {
                    let memText = '';
                    let memPercent = 0;

                    if (typeof data.memory === 'string') {
                        memText = data.memory;
                        // Try to extract percent if possible, otherwise 0
                    } else {
                        // Obj: { used: '1.2GB', total: '8GB', percent: 15 }
                        memText = data.memory.used + ' / ' + data.memory.total;
                        memPercent = data.memory.percent;
                    }

                    document.getElementById('mem-val').innerText = memText;
                    document.getElementById('mem-bar').style.width = memPercent + '%';
                }

                // Update Disk
                if (data.disk) {
                    let diskText = '';
                    let diskPercent = 0;

                    if (typeof data.disk === 'string') {
                        diskText = data.disk;
                    } else {
                        diskText = data.disk.used + ' / ' + data.disk.total;
                        diskPercent = data.disk.percent;
                    }

                    document.getElementById('disk-val').innerText = diskText;
                    document.getElementById('disk-bar').style.width = diskPercent + '%';
                }
            })
            .catch(console.error);
    }

    // Update every 3 seconds
    setInterval(updateSystemStats, 3000);
    // Initial call
    document.addEventListener('DOMContentLoaded', updateSystemStats);
</script>

<style>
    /* Minimal Tool Sections Layout */
    .tools-layout {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 20px;
        align-items: start;
    }

    .tools-main {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    /* Minimal Section Style */
    .tool-section-minimal {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 0;
        margin-bottom: 16px;
        transition: box-shadow 0.2s ease;
    }

    .tool-section-minimal:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .section-header-minimal {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--border-color);
        background: #F8F9FA;
    }

    [data-theme="dark"] .section-header-minimal {
        background: transparent;
    }

    .tool-section-minimal.collapsed .section-header-minimal {
        padding-bottom: 16px;
        border-bottom: none;
    }

    .section-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .section-toggle-icon {
        width: 16px;
        height: 16px;
        color: var(--text-muted);
        transition: transform 0.2s;
    }

    .tool-section-minimal.collapsed .section-toggle-icon {
        transform: rotate(180deg);
    }

    .section-content-minimal {
        display: block;
        padding: 16px 20px;
    }

    .tool-section-minimal.collapsed .section-content-minimal {
        display: none;
    }

    /* Minimal Tools Grid */
    .tools-grid-minimal {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 8px;
    }

    .tool-card-minimal {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        text-decoration: none;
        color: var(--text-primary);
        background: var(--bg-content);
        transition: color 0.15s;
    }

    .tool-card-minimal:hover {
        text-decoration: none;
    }

    .tool-card-minimal:hover .tool-name-minimal {
        color: #3C873A;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 2px;
    }

    .tool-card-minimal:hover .tool-icon-minimal svg {
        color: #3C873A;
    }

    .tool-icon-minimal {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .tool-icon-minimal svg {
        width: 22px;
        height: 22px;
        color: var(--text-secondary);
        transition: color 0.15s;
    }

    .tool-name-minimal {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
        transition: color 0.15s;
    }

    /* Right Sidebar Info Cards */
    .tools-sidebar {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .info-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .info-card-header {
        padding: 12px 15px;
        background: var(--bg-input);
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
    }

    .info-card-body {
        padding: 0;
    }

    .info-item {
        padding: 10px 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 13px;
        color: var(--text-primary);
        font-weight: 500;
    }

    .info-value a {
        color: var(--primary);
    }

    .resource-item {
        padding: 10px 15px;
    }

    .resource-header {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 6px;
    }

    /* Mobile Responsive */
    @media (max-width: 991px) {
        .tools-layout {
            grid-template-columns: 1fr;
            padding: 0;
        }

        .tools-main {
            order: 0;
        }

        .tools-sidebar {
            order: 1;
        }

        .tools-grid-minimal {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
    }

    @media (max-width: 640px) {
        .tools-grid-minimal {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .tool-card-minimal {
            padding: 8px 10px;
        }

        .tool-icon-minimal {
            width: 36px;
            height: 36px;
        }

        .tool-icon-minimal svg {
            width: 20px;
            height: 20px;
        }

        .tool-name-minimal {
            font-size: 13px;
        }
    }
</style>

<script>
    // Toggle section and save state to localStorage
    function toggleSection(sectionName) {
        const section = document.querySelector(`[data-section="${sectionName}"]`);
        if (!section) return;

        section.classList.toggle('collapsed');

        // Save state to localStorage
        const isCollapsed = section.classList.contains('collapsed');
        localStorage.setItem(`section-${sectionName}`, isCollapsed ? 'collapsed' : 'expanded');

        // Re-render icons
        lucide.createIcons();
    }

    // Load saved section states on page load
    document.addEventListener('DOMContentLoaded', () => {
        const sections = ['general', 'databases', 'software'];

        sections.forEach(sectionName => {
            const savedState = localStorage.getItem(`section-${sectionName}`);
            const section = document.querySelector(`[data-section="${sectionName}"]`);

            if (savedState === 'collapsed' && section) {
                section.classList.add('collapsed');
            }
        });

        // Re-render icons after applying states
        lucide.createIcons();
        // Add copy animation function
        window.copyToClipboardWithAnim = function (text, wrapper) {
            if (!navigator.clipboard) return;

            // Prevent re-trigger while animating
            if (wrapper.dataset.animating) return;
            wrapper.dataset.animating = "true";

            navigator.clipboard.writeText(text).then(() => {
                // Switch to check icon
                wrapper.innerHTML = '<i data-lucide="check" style="width:12px; height:12px; color:#10b981;"></i>';
                if (window.lucide) lucide.createIcons();

                setTimeout(() => {
                    // Revert to copy icon
                    wrapper.innerHTML = '<i data-lucide="copy" style="width:12px; height:12px;"></i>';
                    delete wrapper.dataset.animating;
                    if (window.lucide) lucide.createIcons();
                }, 1500);
            }).catch(err => console.error('Failed to copy', err));
        };
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>