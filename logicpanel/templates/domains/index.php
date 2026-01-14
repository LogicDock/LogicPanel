<?php
$page_title = 'Domains';
$current_page = 'tools';
ob_start();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1 class="page-title">Domains</h1>
    <?php if (!empty($services)): ?>
        <button onclick="showAddDomainModal()" class="btn btn-primary">
            <i data-lucide="plus"></i> Add Domain
        </button>
    <?php endif; ?>
</div>

<!-- Domains List -->
<div class="card">
    <?php if (empty($domains)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="globe"></i>
            </div>
            <h3 class="empty-state-title">No Domains Yet</h3>
            <p class="empty-state-text">Domains will appear here when you add them to your services.</p>
        </div>
    <?php else: ?>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Service</th>
                        <th>SSL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                        <tr>
                            <td>
                                <a href="https://<?= htmlspecialchars($domain->domain) ?>" target="_blank">
                                    <?= htmlspecialchars($domain->domain) ?>
                                </a>
                                <?php if ($domain->is_primary): ?>
                                    <span class="badge badge-success" style="margin-left: 5px;">Primary</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($domain->service_name ?? 'Unknown') ?></td>
                            <td>
                                <?php if ($domain->ssl_enabled): ?>
                                    <span class="text-success"><i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                                        Active</span>
                                <?php else: ?>
                                    <span class="text-muted"><i data-lucide="shield-x" style="width:16px;height:16px;"></i>
                                        None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-5">
                                    <a href="<?= $base_url ?>/domains/<?= $domain->service_id ?>"
                                        class="btn btn-sm btn-secondary">
                                        <i data-lucide="settings"></i>
                                    </a>
                                    <a href="https://<?= htmlspecialchars($domain->domain) ?>" target="_blank"
                                        class="btn btn-sm btn-secondary">
                                        <i data-lucide="external-link"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Domain Modal -->
<?php if (!empty($services)): ?>
    <div id="addDomainModal" class="modal" style="display: none;">
        <div class="modal-backdrop" onclick="hideAddDomainModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Domain</h2>
                <button onclick="hideAddDomainModal()" class="modal-close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form id="addDomainForm" class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Service</label>
                    <select name="service_id" class="form-control" required>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service->id ?>"><?= htmlspecialchars($service->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Domain Name</label>
                    <input type="text" name="domain" placeholder="example.com" class="form-control" required
                        pattern="^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$">
                    <small class="text-muted">Enter domain without http:// or https://</small>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="enable_ssl" checked>
                        <span>Enable SSL (HTTPS)</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Add Domain
                </button>
            </form>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 450px;
            margin: 20px;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 20px;
        }

        .text-muted {
            color: var(--text-muted);
            font-size: 12px;
        }
    </style>

    <script>
        function showAddDomainModal() {
            document.getElementById('addDomainModal').style.display = 'flex';
            lucide.createIcons();
        }

        function hideAddDomainModal() {
            document.getElementById('addDomainModal').style.display = 'none';
        }

        document.getElementById('addDomainForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                service_id: formData.get('service_id'),
                domain: formData.get('domain'),
                enable_ssl: formData.get('enable_ssl') === 'on'
            };

            try {
                const response = await fetch('<?= $base_url ?>/domains/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert('Domain added successfully!');
                    location.reload();
                } else {
                    alert(result.error || 'Failed to add domain');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>