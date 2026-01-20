<?php
/**
 * LogicPanel - Create Service Template (cPanel Style)
 */
$page_title = 'Create New Application';
$current_page = 'service_create';
ob_start();
?>

<div class="cpanel-app-header">
    <div class="app-icon">
        <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="Runtime"
            id="runtime-icon">
    </div>
    <div class="app-title">
        <h1 id="runtime-title">Node.js</h1>
        <span class="app-subtitle">Application Setup Manager</span>
    </div>
</div>

<div class="cpanel-tabs">
    <div class="tab-nav">
        <a href="<?= $base_url ?>/services" class="tab-link">
            <i data-lucide="layers"></i> WEB APPLICATIONS
        </a>
        <a href="#" class="tab-link active">
            <i data-lucide="plus-circle"></i> CREATE APPLICATION
        </a>
    </div>
    <div class="tab-actions">
        <a href="<?= $base_url ?>/services" class="btn-cancel">CANCEL</a>
        <button type="submit" form="create-service-form" class="btn-create" id="btn-submit">CREATE</button>
    </div>
</div>

<div class="cpanel-form-container">
    <form id="create-service-form">

        <!-- Runtime Selection -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Runtime Environment</label>
                <span class="form-hint">Select the programming language for your application</span>
            </div>
            <div class="form-input-col">
                <div class="runtime-selector">
                    <label class="runtime-option">
                        <input type="radio" name="runtime" value="nodejs" checked>
                        <div class="runtime-box">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg"
                                alt="Node.js">
                            <span>Node.js</span>
                        </div>
                    </label>
                    <label class="runtime-option">
                        <input type="radio" name="runtime" value="python">
                        <div class="runtime-box">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg"
                                alt="Python">
                            <span>Python</span>
                        </div>
                    </label>
                    <label class="runtime-option">
                        <input type="radio" name="runtime" value="java">
                        <div class="runtime-box">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg"
                                alt="Java">
                            <span>Java</span>
                        </div>
                    </label>
                    <label class="runtime-option">
                        <input type="radio" name="runtime" value="go">
                        <div class="runtime-box">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg" alt="Go">
                            <span>Go</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Application Name -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Application name</label>
                <span class="form-hint">A unique identifier for your application. Lowercase letters, numbers, and
                    hyphens only.</span>
            </div>
            <div class="form-input-col">
                <input type="text" name="name" class="cpanel-input" placeholder="my-awesome-app" autocomplete="off"
                    required>
            </div>
        </div>

        <!-- Service Plan -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Service plan</label>
                <span class="form-hint">Resource allocation for your application</span>
            </div>
            <div class="form-input-col">
                <select name="package_id" class="cpanel-input" id="package-select" onchange="updatePlanInfo()">
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg->id ?>" data-memory="<?= $pkg->memory_limit ?>"
                            data-cpu="<?= $pkg->cpu_limit ?>" data-storage="<?= round($pkg->disk_limit / 1024, 1) ?>">
                            <?= htmlspecialchars($pkg->display_name ?? $pkg->name) ?> — <?= $pkg->memory_limit ?>MB RAM,
                            <?= $pkg->cpu_limit ?> CPU
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="plan-badge" id="plan-badge">
                    <span class="badge-item"><i data-lucide="cpu"></i> <span id="badge-cpu">0.5</span> Core</span>
                    <span class="badge-item"><i data-lucide="memory-stick"></i> <span id="badge-mem">512</span>
                        MB</span>
                    <span class="badge-item"><i data-lucide="hard-drive"></i> <span id="badge-storage">5</span>
                        GB</span>
                </div>
            </div>
        </div>

        <!-- Application URL (auto-generated info) -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Application URL</label>
                <span class="form-hint">Your app will be accessible at this URL with automatic SSL</span>
            </div>
            <div class="form-input-col">
                <div class="url-preview">
                    <span class="url-prefix">https://</span>
                    <span class="url-domain"
                        id="url-preview">your-app-name.<?= $_ENV['APP_DOMAIN'] ?? 'logicpanel.io' ?></span>
                    <span class="ssl-badge"><i data-lucide="shield-check"></i> SSL</span>
                </div>
            </div>
        </div>

        <!-- Startup Command -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Startup command</label>
                <span class="form-hint">Command to start your application (optional, auto-detected)</span>
            </div>
            <div class="form-input-col">
                <input type="text" name="start_cmd" class="cpanel-input" placeholder="npm start" id="start-cmd">
            </div>
        </div>

        <!-- Environment Variables Section -->
        <div class="env-section">
            <div class="env-header">
                <h3>Environment variables</h3>
                <button type="button" class="btn-add-var" onclick="addEnvVar()">
                    <i data-lucide="plus-circle"></i> ADD VARIABLE
                </button>
            </div>
            <div id="env-vars-list" class="env-list">
                <div class="env-empty">No environment variables configured</div>
            </div>
        </div>

    </form>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ==========================================
   cPanel Style Header
   ========================================== */
    .cpanel-app-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 24px 0;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 0;
    }

    .app-icon {
        width: 56px;
        height: 56px;
        background: var(--bg-card);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
    }

    .app-icon img {
        width: 36px;
        height: 36px;
    }

    .app-title h1 {
        font-size: 24px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .app-subtitle {
        font-size: 13px;
        color: var(--text-muted);
    }

    /* ==========================================
   Tabs Navigation (cPanel Style)
   ========================================== */
    .cpanel-tabs {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 24px;
    }

    .tab-nav {
        display: flex;
        gap: 0;
    }

    .tab-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 20px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
    }

    .tab-link:hover {
        color: var(--primary);
    }

    .tab-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .tab-link svg {
        width: 16px;
        height: 16px;
    }

    .tab-actions {
        display: flex;
        gap: 12px;
    }

    .btn-cancel {
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-primary);
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-cancel:hover {
        background: var(--bg-card);
    }

    .btn-create {
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        background: var(--primary);
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-create:hover {
        opacity: 0.9;
    }

    /* ==========================================
   Form Container (cPanel Style)
   ========================================== */
    .cpanel-form-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 4px;
    }

    .form-row {
        display: flex;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-row:last-of-type {
        border-bottom: none;
    }

    .form-label-col {
        width: 240px;
        flex-shrink: 0;
        padding-right: 24px;
    }

    .form-label-col label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .form-hint {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
    }

    .form-input-col {
        flex: 1;
    }

    .cpanel-input {
        width: 100%;
        max-width: 400px;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--text-primary);
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        transition: all 0.2s;
    }

    .cpanel-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(60, 135, 58, 0.1);
    }

    /* ==========================================
   Runtime Selector
   ========================================== */
    .runtime-selector {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .runtime-option input {
        display: none;
    }

    .runtime-box {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .runtime-box img {
        width: 24px;
        height: 24px;
    }

    .runtime-box span {
        font-size: 13px;
        font-weight: 500;
        color: var(--text-primary);
    }

    .runtime-option input:checked+.runtime-box {
        border-color: var(--primary);
        background: rgba(60, 135, 58, 0.08);
    }

    .runtime-option:hover .runtime-box {
        border-color: var(--text-muted);
    }

    /* ==========================================
   Plan Badge
   ========================================== */
    .plan-badge {
        display: flex;
        gap: 16px;
        margin-top: 12px;
    }

    .badge-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .badge-item svg {
        width: 14px;
        height: 14px;
        color: var(--primary);
    }

    /* ==========================================
   URL Preview
   ========================================== */
    .url-preview {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 10px 14px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        max-width: 400px;
    }

    .url-prefix {
        color: var(--text-muted);
        font-size: 14px;
    }

    .url-domain {
        color: var(--primary);
        font-size: 14px;
        font-weight: 500;
    }

    .ssl-badge {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #4CAF50;
        font-weight: 500;
    }

    .ssl-badge svg {
        width: 14px;
        height: 14px;
    }

    /* ==========================================
   Environment Variables Section
   ========================================== */
    .env-section {
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
    }

    .env-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .env-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .btn-add-var {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 500;
        color: var(--primary);
        background: transparent;
        border: 1px solid var(--primary);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-add-var:hover {
        background: rgba(60, 135, 58, 0.08);
    }

    .btn-add-var svg {
        width: 14px;
        height: 14px;
    }

    .env-list {
        min-height: 60px;
    }

    .env-empty {
        text-align: center;
        padding: 20px;
        color: var(--text-muted);
        font-size: 13px;
    }

    .env-row {
        display: flex;
        gap: 12px;
        margin-bottom: 8px;
        align-items: center;
    }

    .env-row input {
        flex: 1;
        padding: 8px 12px;
        font-size: 13px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        color: var(--text-primary);
    }

    .env-row input:focus {
        outline: none;
        border-color: var(--primary);
    }

    .btn-remove-var {
        padding: 8px;
        color: var(--danger);
        background: transparent;
        border: none;
        cursor: pointer;
    }

    .btn-remove-var svg {
        width: 16px;
        height: 16px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }

        .form-label-col {
            width: 100%;
            margin-bottom: 8px;
        }

        .cpanel-tabs {
            flex-direction: column;
            gap: 12px;
        }

        .tab-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>

<script>
    // Runtime icons and titles
    const runtimeInfo = {
        nodejs: { icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg', title: 'Node.js', cmd: 'npm start' },
        python: { icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg', title: 'Python', cmd: 'python app.py' },
        java: { icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg', title: 'Java', cmd: 'java -jar app.jar' },
        go: { icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg', title: 'Go', cmd: './main' }
    };

    // Update header when runtime changes
    document.querySelectorAll('input[name="runtime"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const info = runtimeInfo[this.value];
            document.getElementById('runtime-icon').src = info.icon;
            document.getElementById('runtime-title').textContent = info.title;
            document.getElementById('start-cmd').placeholder = info.cmd;
        });
    });

    // Update plan badge
    function updatePlanInfo() {
        const select = document.getElementById('package-select');
        const opt = select.options[select.selectedIndex];

        document.getElementById('badge-cpu').textContent = opt.dataset.cpu || '0.5';
        document.getElementById('badge-mem').textContent = opt.dataset.memory || '512';
        document.getElementById('badge-storage').textContent = opt.dataset.storage || '5';
    }

    // Update URL preview
    document.querySelector('input[name="name"]').addEventListener('input', function () {
        const name = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-') || 'your-app-name';
        document.getElementById('url-preview').textContent = name + '.<?= $_ENV['APP_DOMAIN'] ?? 'logicpanel.io' ?>';
    });

    // Environment variables
    let envVarCount = 0;
    function addEnvVar() {
        const list = document.getElementById('env-vars-list');
        const empty = list.querySelector('.env-empty');
        if (empty) empty.remove();

        const row = document.createElement('div');
        row.className = 'env-row';
        row.innerHTML = `
        <input type="text" name="env_key_${envVarCount}" placeholder="VARIABLE_NAME">
        <input type="text" name="env_val_${envVarCount}" placeholder="value">
        <button type="button" class="btn-remove-var" onclick="this.parentElement.remove()">
            <i data-lucide="x"></i>
        </button>
    `;
        list.appendChild(row);
        lucide.createIcons();
        envVarCount++;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function () {
        updatePlanInfo();
        lucide.createIcons();
    });

    // Form Submission
    document.getElementById('create-service-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const originalText = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'CREATING...';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('<?= $base_url ?>/services/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Application Created!',
                    text: 'Redirecting to your app...',
                    timer: 1500,
                    showConfirmButton: false,
                    background: getComputedStyle(document.body).getPropertyValue('--bg-card').trim(),
                    color: getComputedStyle(document.body).getPropertyValue('--text-primary').trim()
                }).then(() => {
                    window.location.href = '<?= $base_url ?>/services/' + result.service_id;
                });
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                background: getComputedStyle(document.body).getPropertyValue('--bg-card').trim(),
                color: getComputedStyle(document.body).getPropertyValue('--text-primary').trim()
            });
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>