<?php
$page_title = 'Domains';
$current_page = 'tools';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Domains</h1>
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>