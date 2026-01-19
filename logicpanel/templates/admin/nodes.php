<?php
/**
 * Admin - Nodes/Servers Management
 */
$page_title = 'Nodes / Servers';
$current_page = 'nodes';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Nodes & Servers</h1>
    <button class="btn btn-primary">
        <i data-lucide="plus"></i>
        Add New Node
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="server"></i>
            </div>
            <h3 class="empty-state-title">No Nodes Defined</h3>
            <p class="empty-state-text">Nodes are the physical or virtual servers that run your customer containers.</p>
            <p class="text-muted">Node management is currently being implemented. By default, LogicPanel uses the local
                Docker engine.</p>
        </div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-card-icon green">
            <i data-lucide="activity"></i>
        </div>
        <div class="stat-card-value">Localhost</div>
        <div class="stat-card-label">Default Node</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue">
            <i data-lucide="cpu"></i>
        </div>
        <div class="stat-card-value">Online</div>
        <div class="stat-card-label">Docker Status</div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>