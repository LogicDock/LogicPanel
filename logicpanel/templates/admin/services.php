<?php
$page_title = 'All Services (Admin)';
$current_page = 'admin';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h1 class="text-xl sm:text-2xl font-bold">All Services</h1>
</div>

<!-- Services Table -->
<div class="card rounded-xl overflow-hidden">
    <div
        class="hidden sm:grid grid-cols-12 gap-4 px-4 py-3 bg-[var(--bg-secondary)] border-b border-[var(--border-color)] text-sm font-medium text-[var(--text-secondary)]">
        <div class="col-span-3">Service</div>
        <div class="col-span-2">User</div>
        <div class="col-span-2">Domain</div>
        <div class="col-span-2">Status</div>
        <div class="col-span-1">Plan</div>
        <div class="col-span-2 text-right">Actions</div>
    </div>

    <div class="divide-y divide-[var(--border-color)]">
        <?php if (empty($services)): ?>
            <div class="p-8 text-center text-[var(--text-secondary)]">No services found</div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div
                    class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-4 p-4 hover:bg-[var(--bg-secondary)] transition-colors">
                    <div class="sm:col-span-3 flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <i data-lucide="box" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium">
                                <?= htmlspecialchars($service->name) ?>
                            </p>
                            <p class="text-xs text-[var(--text-secondary)]">ID:
                                <?= $service->id ?>
                            </p>
                        </div>
                    </div>

                    <div class="sm:col-span-2 flex items-center text-sm">
                        <?= htmlspecialchars($service->user->name ?? 'Unknown') ?>
                    </div>

                    <div class="sm:col-span-2 flex items-center text-sm truncate">
                        <?= htmlspecialchars($service->primaryDomain->domain ?? '-') ?>
                    </div>

                    <div class="sm:col-span-2 flex items-center">
                        <?php
                        $statusColors = [
                            'running' => 'bg-green-500',
                            'stopped' => 'bg-gray-500',
                            'suspended' => 'bg-red-500'
                        ];
                        $color = $statusColors[$service->status] ?? 'bg-gray-500';
                        ?>
                        <span
                            class="inline-flex items-center gap-1.5 px-2 py-1 <?= $color ?> text-white text-xs font-medium rounded-full">
                            <?php if ($service->status === 'running'): ?>
                                <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                            <?php endif; ?>
                            <?= ucfirst($service->status) ?>
                        </span>
                    </div>

                    <div class="sm:col-span-1 flex items-center text-sm capitalize">
                        <?= htmlspecialchars($service->plan) ?>
                    </div>

                    <div class="sm:col-span-2 flex items-center justify-end gap-2">
                        <a href="<?= $base_url ?>/services/<?= $service->id ?>"
                            class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors" title="Manage">
                            <i data-lucide="settings" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>