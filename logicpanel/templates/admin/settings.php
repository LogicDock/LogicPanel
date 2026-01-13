<?php
$page_title = 'Admin Settings';
$current_page = 'admin';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">System Settings</h1>
</div>

<div style="max-width: 700px;">
    <!-- Docker Settings -->
    <div class="card mb-20">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="container"></i>
                Docker Configuration
            </h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="section" value="docker">

                <div class="form-group">
                    <label class="form-label">Docker Host</label>
                    <input type="text" name="docker_host"
                        value="<?= htmlspecialchars($settings['docker_host']->value ?? 'unix:///var/run/docker.sock') ?>"
                        class="form-control">
                    <small class="text-muted">e.g., unix:///var/run/docker.sock or tcp://127.0.0.1:2375</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Node.js Version</label>
                    <select name="default_node_version" class="form-control">
                        <option value="18" <?= ($settings['default_node_version']->value ?? '18') === '18' ? 'selected' : '' ?>>Node 18 LTS</option>
                        <option value="20" <?= ($settings['default_node_version']->value ?? '') === '20' ? 'selected' : '' ?>>Node 20 LTS</option>
                        <option value="22" <?= ($settings['default_node_version']->value ?? '') === '22' ? 'selected' : '' ?>>Node 22</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Docker Settings</button>
            </form>
        </div>
    </div>

    <!-- WHMCS Integration -->
    <div class="card mb-20">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="link"></i>
                WHMCS Integration
            </h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="section" value="whmcs">

                <div class="form-group">
                    <label class="form-label">WHMCS URL</label>
                    <input type="url" name="whmcs_url"
                        value="<?= htmlspecialchars($settings['whmcs_url']->value ?? '') ?>"
                        placeholder="https://billing.example.com" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">Save WHMCS Settings</button>
            </form>
        </div>
    </div>

    <!-- API Keys -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="key"></i>
                API Keys
            </h2>
            <button onclick="generateApiKey()" class="btn btn-sm btn-primary">
                <i data-lucide="plus"></i>
                Generate
            </button>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($apiKeys)): ?>
                <p class="text-muted" style="padding: 15px;">No API keys configured.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($key->name) ?></strong></td>
                                    <td><code style="font-size: 11px;"><?= substr($key->api_key, 0, 20) ?>...</code></td>
                                    <td>
                                        <span class="badge <?= $key->is_active ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $key->is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function generateApiKey() {
        alert('API Key generation coming soon!');
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>