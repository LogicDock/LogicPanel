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
                                <input type="text" name="name" class="form-control-custom" placeholder="my-app" autocomplete="off" required>
                                <small class="form-text text-muted mt-1">Lowercase alphabets, numbers, and hyphens only.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-4">
                                <label class="form-label">Service Plan</label>
                                <select name="package_id" class="form-control-custom" id="package-select" onchange="updateResourceSummary()">
                                    <?php foreach ($packages as $pkg): ?>
                                        <option value="<?= $pkg->id ?>" 
                                            data-memory="<?= $pkg->memory_limit ?>" 
                                            data-cpu="<?= $pkg->cpu_limit ?>" 
                                            data-storage="<?= $pkg->storage_limit ?>"
                                            data-price="<?= $pkg->price ?>">
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
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="NodeJS">
                                    <span>Node.js</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="python">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg" alt="Python">
                                    <span>Python</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="java">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg" alt="Java">
                                    <span>Java</span>
                                </div>
                            </label>
                            <label class="runtime-card">
                                <input type="radio" name="runtime" value="go">
                                <div class="card-content">
                                    <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/go/go-original.svg" alt="Go">
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
                <div class="info-item p-3 bg-soft-success">
                    <div class="info-label">Estimated Cost</div>
                    <div class="info-value price-tag" id="summary-price">--</div>
                </div>
            </div>
        </div>

        <div class="info-card mt-3" style="border-style: dashed; opacity: 0.8;">
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
    /* Force Dark Theme Overrides */
    .form-control-custom {
        background-color: #2a2e35 !important;
        border: 1px solid #3e4451 !important;
        color: #e5e7eb !important;
        border-radius: 8px;
        padding: 12px 16px;
        width: 100%;
        transition: all 0.2s;
    }
    .form-control-custom:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    .form-label {
        color: #9ca3af;
        font-weight: 500;
        font-size: 0.9rem;
    }

    /* Runtime Cards */
    .runtime-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
    }
    .runtime-card input { display: none; }
    .card-content {
        background: #2a2e35;
        border: 2px solid #3e4451;
        border-radius: 12px;
        padding: 20px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .card-content img { width: 40px; height: 40px; }
    .card-content span { color: #d1d5db; font-size: 0.9rem; font-weight: 500; }
    
    .runtime-card input:checked + .card-content {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.1);
    }
    .runtime-card:hover .card-content {
        border-color: #6b7280;
    }

    /* Deploy Button */
    .btn-deploy {
        width: 100%;
        background: var(--primary);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-deploy:hover { opacity: 0.9; }
    .btn-deploy:disabled { opacity: 0.6; cursor: not-allowed; }

    .bg-soft-success { background: rgba(16, 185, 129, 0.1) !important; }
    .price-tag { color: #34d399 !important; font-weight: bold; font-size: 1.1rem; }
    
    .text-muted { color: #6b7280 !important; }

    /* Custom scrollbar for better look */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }
</style>

<script>
    // Update Sidebar Summary
    function updateResourceSummary() {
        const select = document.getElementById('package-select');
        const opt = select.options[select.selectedIndex];
        
        document.getElementById('summary-mem').textContent = opt.dataset.memory + ' MB';
        document.getElementById('summary-cpu').textContent = opt.dataset.cpu + ' Core';
        document.getElementById('summary-storage').textContent = opt.dataset.storage + ' GB';
        document.getElementById('summary-price').textContent = '$' + opt.dataset.price + ' / mo';
    }
    
    // Initialize summary on load
    document.addEventListener('DOMContentLoaded', updateResourceSummary);

    // Form Submission
    document.getElementById('create-service-form').addEventListener('submit', async function(e) {
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
                Swal.fire({
                    icon: 'success',
                    title: 'Deployed!',
                    text: 'Redirecting to overview...',
                    timer: 1500,
                    showConfirmButton: false,
                    background: '#1e2127',
                    color: '#fff'
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
                background: '#1e2127',
                color: '#fff'
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