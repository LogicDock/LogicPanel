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
                                <input type="radio" name="runtime" value="java">
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon java"><i data-lucide="coffee"></i></div>
                                    <span class="runtime-name">Java (JDK 17)</span>
                                </div>
                            </label>
                            <label class="runtime-btn">
                                <input type="radio" name="runtime" value="go">
                                <div class="runtime-btn-content">
                                    <div class="runtime-icon go"><i data-lucide="zap"></i></div>
                                    <span class="runtime-name">Go Lang</span>
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
                <div class="info-item p-3"
                    style="background: rgba(76, 175, 80, 0.05); border-top: 1px solid var(--border-color);">
                    <div class="info-label">Estimated Price</div>
                    <div class="info-value text-success font-weight-bold" style="font-size: 16px;">$0.00 / Month</div>
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
        transition: border 0.3s, box-shadow 0.3s;
    }

    .form-control-custom:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(60, 135, 58, 0.1);
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
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
    }

    .runtime-btn {
        cursor: pointer;
        margin: 0;
    }

    .runtime-btn input {
        display: none;
    }

    .runtime-btn-content {
        padding: 20px 10px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        transition: all 0.2s ease;
    }

    .runtime-btn:hover .runtime-btn-content {
        border-color: var(--primary);
        background: var(--bg-input);
        transform: translateY(-2px);
    }

    .runtime-btn input:checked+.runtime-btn-content {
        border-color: var(--primary);
        background: rgba(60, 135, 58, 0.08);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .runtime-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.03);
        transition: all 0.2s;
    }

    .runtime-icon svg {
        width: 22px;
        height: 22px;
    }

    .runtime-name {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .runtime-btn input:checked+.runtime-btn-content .runtime-name {
        color: var(--text-primary);
    }

    .runtime-btn input:checked+.runtime-btn-content .runtime-icon {
        background: var(--primary);
        color: white;
    }

    .btn-deploy {
        width: 100%;
        padding: 16px;
        background: var(--primary);
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(60, 135, 58, 0.2);
    }

    .btn-deploy:hover {
        opacity: 0.95;
        transform: scale(1.01);
    }

    .btn-deploy:active {
        transform: scale(0.99);
    }

    .nodejs {
        color: #68a063;
    }

    .python {
        color: #3776ab;
    }

    .java {
        color: #f89820;
    }

    .go {
        color: #00add8;
    }

    .form-text {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 5px;
        display: block;
    }

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
    document.getElementById('create-service-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-sm"></span> Deploying...';

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
                // Smooth transition
                btn.innerHTML = '<i data-lucide="check-circle"></i> Success!';
                if (window.lucide) lucide.createIcons();

                setTimeout(() => {
                    window.location.href = '<?= $base_url ?>/services/' + result.service_id;
                }, 1000);
            } else {
                alert('Deployment Error: ' + result.error);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        } catch (error) {
            alert('Server connection failed. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>