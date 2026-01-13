<?php
$page_title = 'Backups';
$current_page = 'tools';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Backups</h1>
</div>

<!-- Backups List -->
<div class="card">
    <?php if (empty($backups)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="archive"></i>
            </div>
            <h3 class="empty-state-title">No Backups Yet</h3>
            <p class="empty-state-text">Create backups from your service management page.</p>
        </div>
    <?php else: ?>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Backup</th>
                        <th>Service</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($backup->filename) ?></strong>
                                <br>
                                <span
                                    class="badge badge-<?= $backup->status === 'completed' ? 'success' : ($backup->status === 'failed' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($backup->status) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($backup->service_name ?? 'Unknown') ?></td>
                            <td><?= $backup->getHumanSize() ?></td>
                            <td><?= date('M d, Y H:i', strtotime($backup->created_at)) ?></td>
                            <td>
                                <div class="d-flex gap-5">
                                    <?php if ($backup->status === 'completed'): ?>
                                        <button onclick="restoreBackup(<?= $backup->id ?>)" class="btn btn-sm btn-secondary"
                                            title="Restore">
                                            <i data-lucide="rotate-ccw"></i>
                                        </button>
                                        <a href="<?= $base_url ?>/backups/<?= $backup->id ?>/download"
                                            class="btn btn-sm btn-primary" title="Download">
                                            <i data-lucide="download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="deleteBackup(<?= $backup->id ?>)" class="btn btn-sm btn-danger"
                                        title="Delete">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    async function restoreBackup(id) {
        if (!confirm('Restore from this backup? Current data will be replaced.')) return;

        try {
            const response = await fetch('<?= $base_url ?>/backups/' + id + '/restore', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();

            if (result.success) {
                alert('Backup restored successfully!');
                location.reload();
            } else {
                alert(result.error || 'Restore failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function deleteBackup(id) {
        if (!confirm('Delete this backup?')) return;

        try {
            const response = await fetch('<?= $base_url ?>/backups/' + id + '/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Delete failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>