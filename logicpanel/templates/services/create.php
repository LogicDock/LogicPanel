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
                    <div class="form-group mb-4">
                        <label class="form-label">Application Name</label>
                        <input type="text" name="name" class="form-control-custom" placeholder="e.g. my-app" required>
                        <small class="form-text">Alphabets, numbers and hyphens only.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Select Runtime Environment</label>
                        <div class="runtime-selection-grid">
                            <label class="runtime-btn">
                                <input type="radio" name="runtime" value="nodejs" checked>
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon nodejs"><i data-lucide="package"></i></div>
                                    <span class="runtime-name">Node.js</span>
                                </div>
                            </label>
                            <label class="runtime-btn">
                                <input type="radio" name="runtime" value="python">
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon python"><i data-lucide="terminal"></i></div>
                                    <span class="runtime-name">Python</span>
                                </div>
                            </label>
                            <label class="runtime-btn">
                                <input type="radio" name="runtime" value="php">
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon php"><i data-lucide="code-2"></i></div>
                                    <span class="runtime-name">PHP / FPM</span>
                                </div>
                            </label>
                            <label class="runtime-btn">
                                <input type="radio" name="runtime" value="static">
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon static"><i data-lucide="layout"></i></div>
                                    <span class="runtime-name">Static Web</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions-bar mt-4">
                        <button type="submit" class="btn-deploy" id="btn-submit">
                            <i data-lucide="rocket"></i> Deploy Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Summary Sidebar -->
    <div class="tools-sidebar">
        <div class="info-card">
            <div class="info-card-header">Resource Summary</div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-label">Memory Limit</div>
                    <div class="info-value">512 MB</div>
                </div>
                <div class="info-item">
                    <div class="info-label">CPU Limit</div>
                    <div class="info-value">1.0 vCore</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Storage</div>
                    <div class="info-value">10 GB Shared</div>
                </div>
                <div class="info-item p-3" style="background: rgba(76, 175, 80, 0.05);">
                    <div class="info-label">Estimated Price</div>
                    <div class="info-value text-success font-weight-bold">$0.00 / Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Modern Styles to match Dashboard */
    .form-control-custom {
        width: 100%;
        padding: 12px 15px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 14px;
        transition: border 0.3s;
    }

    .form-control-custom:focus {
        border-color: var(--primary);
        outline: none;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-primary);
    }

    .runtime-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 12px;
    }

    .runtime-btn {
        cursor: pointer;
    }

    .runtime-btn input {
        display: none;
    }

    .runtime-btn-content {
        padding: 15px 10px;
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .runtime-btn:hover .runtime-btn-content {
        border-color: var(--primary);
        background: rgba(255,255,255,0.02);
    }

    .runtime-btn input:checked + .runtime-btn-content {
        border-color: var(--primary);
        background: rgba(60, 135, 58, 0.08);
        box-shadow: 0 0 10px rgba(60, 135, 58, 0.1);
    }

    .runtime-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.05);
    }

    .runtime-name {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .runtime-btn input:checked + .runtime-btn-content .runtime-name {
        color: var(--primary);
    }

    .btn-deploy {
        width: 100%;
        padding: 14px;
        background: var(--primary);
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn-deploy:hover {
        opacity: 0.9;
    }

    .nodejs { color: #68a063; }
    .python { color: #3776ab; }
    .php { color: #777bb4; }
    .static { color: #00d8ff; }

    .form-text {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 5px;
        display: block;
    }
</style>

<script>
    document.getElementById('create-service-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-sm"></span> Provisioning...';

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
                window.location.href = '<?= $base_url ?>/services/' + result.service_id;
            } else {
                alert('Error: ' + result.error);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        } catch (error) {
            alert('Failed to connect to server');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>