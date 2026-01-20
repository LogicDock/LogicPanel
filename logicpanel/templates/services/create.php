<?php
/**
 * LogicPanel - Create Service Template (cPanel Style)
 * With version selection and deployment commands
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
                <span class="form-hint">Select the programming language</span>
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

        <!-- Runtime Version -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Runtime version</label>
                <span class="form-hint">Select the version for your runtime</span>
            </div>
            <div class="form-input-col">
                <select name="runtime_version" class="cpanel-input" id="version-select">
                    <!-- Populated dynamically -->
                </select>
                <span class="version-tag" id="version-tag">LTS recommended</span>
            </div>
        </div>

        <!-- Application Name -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Application name</label>
                <span class="form-hint">Lowercase letters, numbers, hyphens only</span>
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
                <span class="form-hint">Resource allocation</span>
            </div>
            <div class="form-input-col">
                <select name="package_id" class="cpanel-input" id="package-select" onchange="updatePlanInfo()">
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= $pkg->id ?>" data-memory="<?= $pkg->memory_limit ?>"
                            data-cpu="<?= $pkg->cpu_limit ?>" data-storage="<?= round($pkg->disk_limit / 1024, 1) ?>">
                            <?= htmlspecialchars($pkg->display_name ?? $pkg->name) ?> — <?= $pkg->memory_limit ?>MB RAM
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

        <!-- Deployment Commands Section -->
        <div class="form-section-header">
            <i data-lucide="terminal"></i>
            <span>Deployment Commands</span>
            <span class="optional-tag">Optional</span>
        </div>

        <!-- Install Command -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Install command</label>
                <span class="form-hint">Command to install dependencies</span>
            </div>
            <div class="form-input-col">
                <input type="text" name="install_cmd" class="cpanel-input" id="install-cmd" placeholder="npm install">
                <span class="cmd-default">Default: <code id="default-install">npm install</code></span>
            </div>
        </div>

        <!-- Build Command -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Build command</label>
                <span class="form-hint">Command to build your app (if needed)</span>
            </div>
            <div class="form-input-col">
                <input type="text" name="build_cmd" class="cpanel-input" id="build-cmd" placeholder="npm run build">
                <span class="cmd-default">Default: <code id="default-build">npm run build</code></span>
            </div>
        </div>

        <!-- Start Command -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Start command</label>
                <span class="form-hint">Command to start your application</span>
            </div>
            <div class="form-input-col">
                <input type="text" name="start_cmd" class="cpanel-input" id="start-cmd" placeholder="npm start">
                <span class="cmd-default">Default: <code id="default-start">npm start</code></span>
            </div>
        </div>

        <!-- Application URL -->
        <div class="form-row">
            <div class="form-label-col">
                <label>Application URL</label>
                <span class="form-hint">Auto-generated with SSL</span>
            </div>
            <div class="form-input-col">
                <div class="url-preview">
                    <span class="url-prefix">https://</span>
                    <span class="url-domain"
                        id="url-preview">your-app.<?= $_ENV['APP_DOMAIN'] ?? 'logicpanel.io' ?></span>
                    <span class="ssl-badge"><i data-lucide="shield-check"></i> SSL</span>
                </div>
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

        <!-- Deployment Method Info -->
        <div class="info-box">
            <i data-lucide="info"></i>
            <div>
                <strong>Deployment Options:</strong>
                After creating your app, you can deploy code via:
                <ul>
                    <li><strong>File Manager</strong> — Upload files directly, then use Terminal</li>
                    <li><strong>Git Deploy</strong> — Connect your GitHub/GitLab repository</li>
                </ul>
            </div>
        </div>

    </form>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ==========================================
   cPanel Style Header & Tabs
   ========================================== */
    .cpanel-app-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 24px 0;
        border-bottom: 1px solid var(--border-color);
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

    .cpanel-tabs {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 24px;
    }

    .tab-nav {
        display: flex;
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
    }

    /* ==========================================
   Form Styles
   ========================================== */
    .cpanel-form-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 4px;
    }

    .form-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 24px;
        background: var(--bg-input);
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-section-header svg {
        width: 18px;
        height: 18px;
        color: var(--primary);
    }

    .optional-tag {
        font-size: 11px;
        font-weight: 500;
        color: var(--text-muted);
        background: var(--bg-body);
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: auto;
    }

    .form-row {
        display: flex;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-label-col {
        width: 200px;
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
    }

    .cpanel-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(60, 135, 58, 0.1);
    }

    .version-tag {
        display: inline-block;
        margin-left: 12px;
        font-size: 12px;
        color: var(--primary);
        font-weight: 500;
    }

    .cmd-default {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .cmd-default code {
        background: var(--bg-input);
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
    }

    /* Runtime Selector */
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

    /* Plan Badge */
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

    /* URL Preview */
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

    /* Environment Variables */
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
    }

    .btn-add-var svg {
        width: 14px;
        height: 14px;
    }

    .env-list {
        min-height: 40px;
    }

    .env-empty {
        text-align: center;
        padding: 16px;
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

    .btn-remove-var {
        padding: 8px;
        color: var(--danger);
        background: transparent;
        border: none;
        cursor: pointer;
    }

    /* Info Box */
    .info-box {
        display: flex;
        gap: 12px;
        padding: 16px 24px;
        background: rgba(33, 150, 243, 0.08);
        border-top: 1px solid var(--border-color);
        font-size: 13px;
        color: var(--text-primary);
    }

    .info-box svg {
        width: 20px;
        height: 20px;
        color: #2196F3;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .info-box ul {
        margin: 8px 0 0 16px;
        padding: 0;
    }

    .info-box li {
        margin-bottom: 4px;
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
    }
</style>

<script>
    // Runtime versions
    const runtimeVersions = {
        nodejs: [
            { value: '22', label: 'Node.js 22 (Latest)', tag: 'Latest' },
            { value: '20', label: 'Node.js 20 LTS', tag: 'LTS Recommended', default: true },
            { value: '18', label: 'Node.js 18 LTS', tag: 'LTS' }
        ],
        python: [
            { value: '3.12', label: 'Python 3.12 (Latest)', tag: 'Latest' },
            { value: '3.11', label: 'Python 3.11', tag: 'Recommended', default: true },
            { value: '3.10', label: 'Python 3.10', tag: '' }
        ],
        java: [
            { value: '21', label: 'Java 21 LTS', tag: 'Latest LTS', default: true },
            { value: '17', label: 'Java 17 LTS', tag: 'LTS' },
            { value: '11', label: 'Java 11 LTS', tag: '' }
        ],
        go: [
            { value: '1.22', label: 'Go 1.22 (Latest)', tag: 'Latest', default: true },
            { value: '1.21', label: 'Go 1.21', tag: '' },
            { value: '1.20', label: 'Go 1.20', tag: '' }
        ]
    };

    // Default commands per runtime
    const runtimeDefaults = {
        nodejs: { install: 'npm install', build: 'npm run build', start: 'npm start', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg', title: 'Node.js' },
        python: { install: 'pip install -r requirements.txt', build: '', start: 'python app.py', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg', title: 'Python' },
        java: { install: 'mvn install', build: 'mvn package', start: 'java -jar target/*.jar', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg', title: 'Java' },
        go: { install: 'go mod download', build: 'go build -o main', start: './main', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg', title: 'Go' }
    };

    // Update version dropdown when runtime changes
    function updateVersions(runtime) {
        const select = document.getElementById('version-select');
        const tagSpan = document.getElementById('version-tag');
        const versions = runtimeVersions[runtime] || [];

        select.innerHTML = '';
        versions.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.value;
            opt.textContent = v.label;
            opt.selected = v.default;
            select.appendChild(opt);
        });

        const defaultVer = versions.find(v => v.default);
        tagSpan.textContent = defaultVer ? defaultVer.tag : '';

        // Update defaults
        const defaults = runtimeDefaults[runtime];
        document.getElementById('default-install').textContent = defaults.install || '(none)';
        document.getElementById('default-build').textContent = defaults.build || '(skip)';
        document.getElementById('default-start').textContent = defaults.start;

        document.getElementById('install-cmd').placeholder = defaults.install || '';
        document.getElementById('build-cmd').placeholder = defaults.build || '';
        document.getElementById('start-cmd').placeholder = defaults.start;

        // Update header
        document.getElementById('runtime-icon').src = defaults.icon;
        document.getElementById('runtime-title').textContent = defaults.title;
    }

    // Update version tag on select change
    document.getElementById('version-select').addEventListener('change', function () {
        const runtime = document.querySelector('input[name="runtime"]:checked').value;
        const versions = runtimeVersions[runtime] || [];
        const selected = versions.find(v => v.value === this.value);
        document.getElementById('version-tag').textContent = selected ? selected.tag : '';
    });

    // Runtime change handler
    document.querySelectorAll('input[name="runtime"]').forEach(radio => {
        radio.addEventListener('change', function () {
            updateVersions(this.value);
        });
    });

    // Plan info update
    function updatePlanInfo() {
        const select = document.getElementById('package-select');
        const opt = select.options[select.selectedIndex];
        document.getElementById('badge-cpu').textContent = opt.dataset.cpu || '0.5';
        document.getElementById('badge-mem').textContent = opt.dataset.memory || '512';
        document.getElementById('badge-storage').textContent = opt.dataset.storage || '5';
    }

    // URL preview
    document.querySelector('input[name="name"]').addEventListener('input', function () {
        const name = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-') || 'your-app';
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
        updateVersions('nodejs');
        updatePlanInfo();
        lucide.createIcons();
    });

    // Form Submission
    document.getElementById('create-service-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
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
                    text: 'Redirecting...',
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
            btn.textContent = 'CREATE';
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>