<?php
$page_title = 'My Services';
$current_page = 'services';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">My Services</h1>
</div>

<!-- Services Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if (empty($services) || count($services) === 0): ?>
        <div class="col-span-full card rounded-xl p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="inbox" class="w-10 h-10 text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">No Services Yet</h3>
            <p class="text-[var(--text-secondary)] mb-6 max-w-md mx-auto">
                You don't have any services yet. Services are created when you order a hosting plan from WHMCS.
            </p>
        </div>
    <?php else: ?>
        <?php foreach ($services as $service): ?>
            <div class="card rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300">
                <!-- Header with Status -->
                <div class="p-5 border-b border-[var(--border-color)]">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="box" class="w-6 h-6 text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">
                                    <?= htmlspecialchars($service->name) ?>
                                </h3>
                                <a href="https://<?= htmlspecialchars($service->primaryDomain?->domain ?? '') ?>"
                                    target="_blank"
                                    class="text-sm text-primary-500 hover:text-primary-600 flex items-center gap-1">
                                    <?= htmlspecialchars($service->primaryDomain?->domain ?? 'No domain') ?>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <?php
                        $statusConfig = [
                            'running' => ['bg' => 'bg-green-500', 'text' => 'Running', 'dot' => true],
                            'stopped' => ['bg' => 'bg-gray-500', 'text' => 'Stopped', 'dot' => false],
                            'suspended' => ['bg' => 'bg-red-500', 'text' => 'Suspended', 'dot' => false],
                            'error' => ['bg' => 'bg-red-500', 'text' => 'Error', 'dot' => false],
                            'creating' => ['bg' => 'bg-yellow-500', 'text' => 'Creating', 'dot' => true],
                        ];
                        $cfg = $statusConfig[$service->status] ?? $statusConfig['stopped'];
                        ?>
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 <?= $cfg['bg'] ?> text-white text-xs font-medium rounded-full">
                            <?php if ($cfg['dot']): ?>
                                <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                            <?php endif; ?>
                            <?= $cfg['text'] ?>
                        </span>
                    </div>
                </div>

                <!-- Stats -->
                <?php if ($service->status === 'running' && $service->live_status === 'running'): ?>
                    <div class="grid grid-cols-2 gap-px bg-[var(--border-color)]">
                        <div class="bg-[var(--bg-primary)] p-4">
                            <p class="text-xs text-[var(--text-secondary)] mb-1">Node Version</p>
                            <p class="font-semibold">v
                                <?= htmlspecialchars($service->node_version) ?>
                            </p>
                        </div>
                        <div class="bg-[var(--bg-primary)] p-4">
                            <p class="text-xs text-[var(--text-secondary)] mb-1">Port</p>
                            <p class="font-semibold">
                                <?= $service->port ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Info -->
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Domains</span>
                        <span class="font-medium">
                            <?= count($service->domains) ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Databases</span>
                        <span class="font-medium">
                            <?= count($service->databases) ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-secondary)]">Plan</span>
                        <span class="font-medium capitalize">
                            <?= htmlspecialchars($service->plan) ?>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-5 pt-0 flex gap-2">
                    <a href="<?= $base_url ?>/services/<?= $service->id ?>"
                        class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-center py-2.5 px-4 rounded-lg font-medium transition-colors">
                        Manage
                    </a>

                    <?php if ($service->status === 'running'): ?>
                        <button onclick="serviceAction(<?= $service->id ?>, 'restart')"
                            class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            title="Restart">
                            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                        </button>
                        <button onclick="serviceAction(<?= $service->id ?>, 'stop')"
                            class="p-2.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-600 rounded-lg transition-colors"
                            title="Stop">
                            <i data-lucide="square" class="w-5 h-5"></i>
                        </button>
                    <?php elseif ($service->status === 'stopped'): ?>
                        <button onclick="serviceAction(<?= $service->id ?>, 'start')"
                            class="p-2.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-600 rounded-lg transition-colors"
                            title="Start">
                            <i data-lucide="play" class="w-5 h-5"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    async function serviceAction(id, action) {
        const btn = event.currentTarget;
        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await fetch(`<?= $base_url ?>/services/${id}/${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Action failed');
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>