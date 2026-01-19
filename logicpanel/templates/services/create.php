<?php
/**
 * LogicPanel - Create Service Template
 */
?>

<div class="lp-page-header">
    <div class="header-content">
        <h1>Create New Application</h1>
        <p>Deploy your application in seconds using our simplified container system.</p>
    </div>
    <div class="header-actions">
        <a href="<?= $base_url ?>/services" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> Back to Services
        </a>
    </div>
</div>

<div class="lp-card glass-morph mt-4">
    <form id="create-service-form" class="lp-form p-4">
        <div class="row">
            <div class="col-md-7">
                <div class="form-section">
                    <h3>Basic Information</h3>
                    <div class="form-group mb-3">
                        <label for="name">Application Name</label>
                        <input type="text" id="name" name="name" class="form-input" placeholder="e.g. my-awesome-app"
                            required>
                        <small class="text-muted">Only lowercase letters, numbers, and hyphens allowed.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="runtime">Runtime Environment</label>
                        <div class="runtime-grid">
                            <label class="runtime-option">
                                <input type="radio" name="runtime" value="nodejs" checked>
                                <div class="runtime-card">
                                    <img src="<?= $base_url ?>/assets/icons/nodejs.svg" alt="Node.js"
                                        onerror="this.src='https://cdn.worldvectorlogo.com/logos/nodejs-icon.svg'">
                                    <span>Node.js</span>
                                </div>
                            </label>
                            <label class="runtime-option">
                                <input type="radio" name="runtime" value="python">
                                <div class="runtime-card">
                                    <img src="<?= $base_url ?>/assets/icons/python.svg" alt="Python"
                                        onerror="this.src='https://cdn.worldvectorlogo.com/logos/python-5.svg'">
                                    <span>Python</span>
                                </div>
                            </label>
                            <label class="runtime-option">
                                <input type="radio" name="runtime" value="php">
                                <div class="runtime-card">
                                    <img src="<?= $base_url ?>/assets/icons/php.svg" alt="PHP"
                                        onerror="this.src='https://cdn.worldvectorlogo.com/logos/php-1.svg'">
                                    <span>PHP / FPM</span>
                                </div>
                            </label>
                            <label class="runtime-option">
                                <input type="radio" name="runtime" value="static">
                                <div class="runtime-card">
                                    <i data-lucide="layout" class="mb-2"></i>
                                    <span>Static HTML</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="form-section highlight">
                    <h3>Summary & Resources</h3>
                    <div class="resource-preview card bg-dark p-3 border-0">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Memory Limit:</span>
                            <span class="text-primary font-weight-bold">512 MB</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>CPU Limit:</span>
                            <span class="text-primary font-weight-bold">1.0 Core</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Storage:</span>
                            <span class="text-primary font-weight-bold">10 GB</span>
                        </div>
                        <hr class="bg-secondary">
                        <div class="d-flex justify-content-between">
                            <span class="font-weight-bold text-white">Estimated Cost:</span>
                            <span class="text-success font-weight-bold">$0.00 / mo</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-block btn-lg" id="btn-submit">
                            <i data-lucide="rocket"></i> Deploy Application
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .runtime-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }

    .runtime-option {
        cursor: pointer;
    }

    .runtime-option input {
        display: none;
    }

    .runtime-card {
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 15px 10px;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .runtime-card img,
    .runtime-card i {
        width: 32px;
        height: 32px;
        margin-bottom: 8px;
    }

    .runtime-option input:checked+.runtime-card {
        background: rgba(6, 182, 212, 0.1);
        border-color: var(--primary);
        box-shadow: 0 0 15px rgba(6, 182, 212, 0.2);
    }

    .runtime-card span {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .runtime-option input:checked+.runtime-card span {
        color: white;
    }

    .form-section {
        padding: 10px;
    }

    .form-section h3 {
        font-size: 1.1rem;
        margin-bottom: 20px;
        color: white;
    }

    .resource-preview {
        border-radius: 10px;
        font-size: 0.9rem;
        color: #9CA3AF;
    }

    .highlight {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 12px;
        padding: 20px;
    }
</style>

<script>
    document.getElementById('create-service-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-sm"></i> Provisioning...';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('<?= $base_url ?>/services/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: json.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Your application is being deployed.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '<?= $base_url ?>/services/' + result.service_id;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Deployment Failed',
                    text: result.error
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error(error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
</script>