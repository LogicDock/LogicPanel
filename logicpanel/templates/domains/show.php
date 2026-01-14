<?php
$page_title = 'Domain Settings';
$current_page = 'tools';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/domains" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title">Domain Settings</h1>
            <p class="page-subtitle">
                <?= htmlspecialchars($service->name ?? 'Service') ?>
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <button onclick="showAddDomainModal()" class="btn btn-primary">
            <i data-lucide="plus"></i> Add Domain
        </button>
    </div>
</div>

<!-- Service Domains List -->
<div class="card mb-20">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="globe"></i> Domains for
            <?= htmlspecialchars($service->name ?? 'Service') ?>
        </h2>
    </div>

    <?php if (empty($domains)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="globe"></i>
                </div>
                <h3 class="empty-state-title">No Domains Yet</h3>
                <p class="empty-state-text">Add a domain to access your application via a custom URL.</p>
                <button onclick="showAddDomainModal()" class="btn btn-primary">
                    <i data-lucide="plus"></i> Add Your First Domain
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>SSL Status</th>
                        <th>Type</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                        <tr>
                            <td>
                                <div class="domain-cell">
                                    <a href="https://<?= htmlspecialchars($domain->domain) ?>" target="_blank">
                                        <?= htmlspecialchars($domain->domain) ?>
                                        <i data-lucide="external-link" style="width: 12px; height: 12px;"></i>
                                    </a>
                                    <?php if ($domain->is_primary): ?>
                                        <span class="badge badge-success">Primary</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($domain->ssl_enabled): ?>
                                    <span class="ssl-status ssl-active">
                                        <i data-lucide="shield-check"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="ssl-status ssl-inactive">
                                        <i data-lucide="shield-x"></i> Not Active
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($domain->is_primary): ?>
                                    <span class="badge badge-primary">Primary</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Alias</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($domain->created_at)) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!$domain->is_primary): ?>
                                        <button onclick="setPrimary(<?= $domain->id ?>)" class="btn btn-sm btn-secondary"
                                            title="Set as Primary">
                                            <i data-lucide="star"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$domain->ssl_enabled): ?>
                                        <button onclick="enableSSL(<?= $domain->id ?>)" class="btn btn-sm btn-success"
                                            title="Enable SSL">
                                            <i data-lucide="shield-plus"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$domain->is_primary): ?>
                                        <button
                                            onclick="deleteDomain(<?= $domain->id ?>, '<?= htmlspecialchars($domain->domain) ?>')"
                                            class="btn btn-sm btn-danger" title="Delete">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- DNS Configuration Help -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="info"></i> DNS Configuration
        </h2>
    </div>
    <div class="card-body">
        <p class="mb-15">To point your domain to this service, add the following DNS records:</p>

        <div class="dns-records">
            <div class="dns-record">
                <div class="dns-record-header">
                    <span class="dns-type">A Record</span>
                    <span class="dns-label">For root domain (example.com)</span>
                </div>
                <div class="dns-record-body">
                    <div class="dns-field">
                        <label>Host</label>
                        <code>@</code>
                    </div>
                    <div class="dns-field">
                        <label>Value</label>
                        <code><?= $_ENV['SERVER_IP'] ?? '194.238.17.184' ?></code>
                        <button onclick="copyToClipboard('<?= $_ENV['SERVER_IP'] ?? '194.238.17.184' ?>')"
                            class="copy-btn">
                            <i data-lucide="copy"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="dns-record">
                <div class="dns-record-header">
                    <span class="dns-type">CNAME Record</span>
                    <span class="dns-label">For www subdomain</span>
                </div>
                <div class="dns-record-body">
                    <div class="dns-field">
                        <label>Host</label>
                        <code>www</code>
                    </div>
                    <div class="dns-field">
                        <label>Value</label>
                        <code><?= $service->primaryDomain->domain ?? 'your-domain.com' ?></code>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted mt-15">
            <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
            DNS changes may take up to 48 hours to propagate globally.
        </p>
    </div>
</div>

<!-- Add Domain Modal -->
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
            <input type="hidden" name="service_id" value="<?= $service->id ?>">

            <div class="form-group">
                <label class="form-label">Domain Name</label>
                <input type="text" name="domain" placeholder="example.com" class="form-control" required
                    pattern="^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$"
                    title="Enter a valid domain name (e.g., example.com)">
                <small class="text-muted">Enter domain without http:// or https://</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_ssl" checked>
                    <span>Enable SSL (HTTPS) - Recommended</span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="set_primary">
                    <span>Set as primary domain</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Add Domain
            </button>
        </form>
    </div>
</div>

<style>
    /* Page Header */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
    }

    .page-header-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .back-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--border-radius);
        color: var(--text-secondary);
        transition: all 0.15s ease;
    }

    .back-btn:hover {
        background: var(--bg-input);
        color: var(--text-primary);
        text-decoration: none;
    }

    .back-btn svg {
        width: 20px;
        height: 20px;
    }

    .page-header-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .page-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .page-subtitle {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0;
    }

    /* Domain Cell */
    .domain-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .domain-cell a {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* SSL Status */
    .ssl-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 500;
    }

    .ssl-status svg {
        width: 14px;
        height: 14px;
    }

    .ssl-active {
        color: var(--success);
    }

    .ssl-inactive {
        color: var(--text-muted);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 5px;
    }

    /* DNS Records */
    .dns-records {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .dns-record {
        background: var(--bg-input);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .dns-record-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
        background: rgba(0, 0, 0, 0.05);
    }

    .dns-type {
        font-size: 12px;
        font-weight: 600;
        color: var(--primary);
        text-transform: uppercase;
    }

    .dns-label {
        font-size: 12px;
        color: var(--text-muted);
    }

    .dns-record-body {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 15px;
        padding: 15px;
    }

    .dns-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .dns-field label {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
    }

    .dns-field code {
        font-size: 13px;
        font-family: monospace;
        background: var(--bg-card);
        padding: 6px 10px;
        border-radius: 4px;
    }

    .dns-field .copy-btn {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: var(--text-muted);
        margin-left: 8px;
    }

    .dns-field .copy-btn:hover {
        color: var(--primary);
    }

    .dns-field .copy-btn svg {
        width: 14px;
        height: 14px;
    }

    /* Modal */
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

    .modal-close:hover {
        color: var(--text-primary);
    }

    .modal-body {
        padding: 20px;
    }

    /* Checkbox Label */
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 13px;
    }

    .checkbox-label input {
        width: 16px;
        height: 16px;
    }

    /* Utilities */
    .mb-15 {
        margin-bottom: 15px;
    }

    .mb-20 {
        margin-bottom: 20px;
    }

    .mt-15 {
        margin-top: 15px;
    }

    .text-muted {
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    .empty-state-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 15px;
        background: var(--bg-input);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }

    .empty-state-icon svg {
        width: 28px;
        height: 28px;
    }

    .empty-state-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .empty-state-text {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0 0 20px 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dns-record-body {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
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

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Copied to clipboard!');
        });
    }

    async function setPrimary(domainId) {
        if (!confirm('Set this domain as primary?')) return;

        try {
            const response = await fetch(`<?= $base_url ?>/domains/${domainId}/set-primary`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to set primary domain');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function enableSSL(domainId) {
        if (!confirm('Enable SSL for this domain? This may take a few minutes.')) return;

        try {
            const response = await fetch(`<?= $base_url ?>/domains/${domainId}/enable-ssl`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                alert('SSL enabled successfully!');
                location.reload();
            } else {
                alert(data.error || 'Failed to enable SSL');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function deleteDomain(domainId, domainName) {
        if (!confirm(`Delete domain "${domainName}"? This cannot be undone.`)) return;

        try {
            const response = await fetch(`<?= $base_url ?>/domains/${domainId}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to delete domain');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Form submit handler
    document.getElementById('addDomainForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            service_id: formData.get('service_id'),
            domain: formData.get('domain'),
            enable_ssl: formData.get('enable_ssl') === 'on',
            set_primary: formData.get('set_primary') === 'on'
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>