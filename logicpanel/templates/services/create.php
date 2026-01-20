<?php
/**
 * LogicPanel - Create Service Template (cPanel Style)
 */
$page_title = 'Create New Application';
$current_page = 'service_create';
ob_start();
?>

<div class="tools-layout">
    <div class="tools-main">
        <div class="tools-section">
            <div class="tools-section-header">
                <div class="tools-section-icon" style="background: rgba(60, 135, 58, 0.15); color: var(--primary);">
                    <i data-lucide="plus-circle"></i>
                </div>
                <span class="tools-section-title">New Application Details</span>
            </div>
            <div class="tools-section-body">
                <form id="create-service-form" class="lp-form-modern">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-4">
                                <label class="form-label">Application Name</label>
                                <input type="text" name="name" class="form-control-custom" placeholder="my-app"
                                    autocomplete="off" required>
                                <small class="form-text text-muted mt-1">Lowercase alphabets, numbers, and hyphens
                                    only.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-4">
                                <label class="form-label">Service Plan</label>
                                <select name="package_id" class="form-control-custom" id="package-select"
                                    onchange="updateResourceSummary()">
                                    <?php foreach ($packages as $pkg): ?>
                                        <option value="<?= $pkg->id ?>" data-memory="<?= $pkg->memory_limit ?>"
                                            data-cpu="<?= $pkg->cpu_limit ?>"
                                            data-storage="<?= round($pkg->disk_limit / 1024, 1) ?>">
                                            <?= htmlspecialchars($pkg->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label mb-3">Runtime Environment</label>
                        <div class="runtime-grid">
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="nodejs" checked>
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg"
                                        alt="NodeJS">
                                    <span>Node.js</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="python">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg"
                                        alt="Python">
                                    <span>Python</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="java">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg"
                                        alt="Java">
                                    <span>Java</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="go">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg"
                                        alt="Go">
                                    <span>Go</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn-deploy" id="btn-submit">
                            <i data-lucide="rocket"></i>
                            <span>Deploy Application</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Summary Sidebar -->
    <div class="tools-sidebar">
        <div class="info-card">
            <div class="info-card-header">
                <i data-lucide="bar-chart-2" style="width: 16px;"></i> Plan Resources
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Memory</div>
                    <div class="info-value" id="summary-mem">--</div>
                </div>
                <div class="info-item">
                    <div class="info-label">CPU Cores</div>
                    <div class="info-value" id="summary-cpu">--</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Storage</div>
                    <div class="info-value" id="summary-storage">--</div>
                </div>
            </div>
        </div>

        <div class="info-card mt-1" style="border-style: dashed; opacity: 0.8;">
            <div class="info-card-body p-3" style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                <i data-lucide="info" style="width:14px; margin-right:5px; vertical-align:middle;"></i>
                New services are automatically assigned a subdomain and SSL certificate.
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ==========================================
       Layout Structure
       ========================================== */
    .tools-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 24px;
        align-items: start;
        margin-top: 20px;
    }

    @media (max-width: 991px) {
        .tools-layout {
            grid-template-columns: 1fr;
        }
    }

    .tools-section {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
    }

    .tools-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: var(--bg-input);
        /* Theme aware */
        border-bottom: 1px solid var(--border-color);
    }

    .tools-section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .tools-section-body {
        padding: 24px;
    }

    /* ==========================================
       Sidebar Styling (Resource Summary)
       ========================================== */
    .tools-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .info-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .info-card-header {
        padding: 14px 20px;
        background: var(--bg-input);
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-card-body {
        padding: 0;
    }

    .info-item {
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 13px;
        color: var(--text-muted);
    }

    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
    }

    /* ==========================================
       Form & Controls Style (Theme Aware)
       ========================================== */
    .form-control-custom {
        background-color: var(--bg-input) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-primary) !important;
        border-radius: 6px;
        padding: 12px 16px;
        width: 100%;
        transition: all 0.2s;
        font-size: 14px;
    }

    .form-control-custom:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 2px rgba(96, 125, 139, 0.15);
        outline: none;
    }

    .form-label {
        color: var(--text-primary);
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
    }

    /* Runtime Cards */
    .runtime-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }

    @media (max-width: 768px) {
        .runtime-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .runtime-card input {
        display: none;
    }

    .card-content {
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
        height: 100%;
    }

    .card-content img {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .card-content span {
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 500;
    }

    .runtime-card input:checked+.card-content {
        border-color: var(--primary);
        background: rgba(96, 125, 139, 0.08);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .runtime-card input:checked+.card-content span {
        color: var(--primary);
        font-weight: 600;
    }

    .runtime-card:hover .card-content {
        border-color: var(--text-muted);
        transform: translateY(-2px);
    }

    /* Deploy Button */
    .btn-deploy {
        width: 100%;
        background: var(--primary);
        color: #fff;
        border: none;
        padding: 16px;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-deploy:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .text-muted {
        color: var(--text-muted) !important;
    }
</style>

<script>
    // Update Sidebar Summary with safety checks
    function updateResourceSummary() {
        const select = document.getElementById('package-select');
        if (!select || select.selectedIndex === -1) return;

        const opt = select.options[select.selectedIndex];

        const mem = opt.dataset.memory || '0';
        const cpu = opt.dataset.cpu || '0';
        const storage = opt.dataset.storage || '0';

        document.getElementById('summary-mem').textContent = mem + ' MB';
        document.getElementById('summary-cpu').textContent = cpu + ' Core';
        document.getElementById('summary-storage').textContent = storage + ' GB';
    }

    // Initialize summary on load
    document.addEventListener('DOMContentLoaded', updateResourceSummary);

    // Form Submission
    document.getElementById('create-service-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

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
                // Use SweetAlert for success message (Dark Mode Compatible)
                Swal.fire({
                    icon: 'success',
                    title: 'Deployed!',
                    text: 'Redirecting to service overview...',
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
            btn.innerHTML = originalHtml;
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>