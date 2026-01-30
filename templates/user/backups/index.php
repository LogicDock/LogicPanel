<?php
// templates/backups/index.php
$title = "Backups";
ob_start();
?>

<div class="db-container">
    <div class="db-page-header">
        <h1>Application Backups</h1>
        <div class="db-actions">
            <button class="btn btn-primary" onclick="createBackup('app')">
                <i data-lucide="folder-archive"></i> New App Backup
            </button>
        </div>
    </div>

    <div class="db-card">
        <div class="table-wrapper">
            <table class="db-table" id="backupsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 20px;">Loading backups...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </script>

    <script>
        const apiUrl = (window.base_url || '') + '/public/api';

        document.addEventListener('DOMContentLoaded', loadBackups);

        function loadBackups() {
            fetch(`${apiUrl}/backups`, {
                headers: { 'Authorization': 'Bearer <?= $_SESSION['lp_session_token'] ?? '' ?>' }
            })
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#backupsTable tbody');
                    tbody.innerHTML = '';

                    // Filter only application backups
                    const backups = data.backups.filter(b => b.type === 'application' || b.status === 'creating');

                    if (backups.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 20px; color: #666;">No backups found</td></tr>';
                        return;
                    }

                    backups.forEach(backup => {
                        const isPending = backup.status === 'creating';
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                    <td>
                        <div class="file-icon">
                            <i data-lucide="archive" style="color: ${isPending ? '#f59e0b' : '#666'};"></i>
                            <span style="font-weight: 500; ${isPending ? 'color:#f59e0b;' : ''}">${backup.name}</span>
                            ${isPending ? '<span style="font-size:10px; margin-left:5px; color:#f59e0b;">(Creating...)</span>' : ''}
                        </div>
                    </td>
                    <td><span class="badge badge-success">Application</span></td>
                    <td>${backup.size}</td>
                    <td>${backup.date}</td>
                    <td>
                        <div class="action-buttons" style="justify-content: flex-end;">
                            ${isPending ?
                                `<button class="btn-icon" disabled title="Creation in progress..."><i class="spinner" style="width:16px;height:16px;border-width:2px;border-color:#f59e0b;"></i></button>`
                                :
                                `<button class="btn-icon" title="Download" onclick="downloadBackup('${backup.name}')">
                                    <i data-lucide="download"></i>
                                </button>
                                <button class="btn-icon" title="Restore" onclick="restoreBackup('${backup.name}')">
                                    <i data-lucide="rotate-ccw"></i>
                                </button>
                                <button class="btn-icon danger" title="Delete" onclick="deleteBackup('${backup.name}')">
                                    <i data-lucide="trash-2"></i>
                                </button>`
                            }
                        </div>
                    </td>
                `;
                        tbody.appendChild(tr);
                    });
                    lucide.createIcons();
                });
        }

        function createBackup(type) {
            const btn = event.currentTarget;
            const originalInfo = btn.innerHTML;
            btn.innerHTML = '<span class="spinner" style="margin-right:8px;"></span>Creating...';
            btn.disabled = true;

            // Force 'app' type since DB is removed
            let endpoint = '/backups/app';
            let payload = {};

            fetch(`${apiUrl}${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= $_SESSION['lp_session_token'] ?? '' ?>'
                },
                body: JSON.stringify(payload)
            })
                .then(async res => {
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                })
                .then(data => {
                    if (data.error) {
                        showNotification(data.error, 'error');
                    } else {
                        showNotification('Backup created successfully', 'success');
                        loadBackups();
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('Backup failed: ' + err.message, 'error');
                })
                .finally(() => {
                    btn.innerHTML = originalInfo;
                    btn.disabled = false;
                    lucide.createIcons();
                });
        }

        async function deleteBackup(filename) {
            if (!(await showCustomConfirm('Delete Backup?', `Are you sure you want to delete ${filename}?`, true))) return;

            fetch(`${apiUrl}/backups/${filename}`, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer <?= $_SESSION['lp_session_token'] ?? '' ?>' }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        showNotification(data.error, 'error');
                    } else {
                        showNotification('Backup deleted successfully', 'success');
                        loadBackups();
                    }
                });
        }

        async function restoreBackup(filename) {
            if (!(await showCustomConfirm('Restore Backup?', `WARNING: Restoring will overwrite existing files. This cannot be undone. Continue with ${filename}?`, true))) return;

            // Show loading overlay
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = 0; overlay.style.left = 0; overlay.style.width = '100%'; overlay.style.height = '100%';
            overlay.style.background = 'rgba(0,0,0,0.5)';
            overlay.style.zIndex = 9999;
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center'; overlay.style.justifyContent = 'center';
            overlay.innerHTML = '<div style="background:white; padding:20px; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display:flex; align-items:center; gap:10px;"><span class="spinner" style="border-top-color:#333; border-left-color:#333;"></span> Restoring application... please wait...</div>';
            document.body.appendChild(overlay);

            fetch(`${apiUrl}/backups/restore`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?= $_SESSION['lp_session_token'] ?? '' ?>'
                },
                body: JSON.stringify({ filename: filename })
            })
                .then(res => res.json())
                .then(data => {
                    document.body.removeChild(overlay);
                    if (data.error) {
                        showNotification('Error: ' + data.error, 'error');
                    } else {
                        showNotification('Restore completed successfully!', 'success');
                    }
                })
                .catch(err => {
                    document.body.removeChild(overlay);
                    showNotification('Network error during restore', 'error');
                });
        }

        function downloadBackup(filename) {
            window.location.href = `${apiUrl}/backups/download/${filename}?token=<?= $_SESSION['lp_session_token'] ?? '' ?>`;
        }
    </script>

    <style>
        .db-container {
            padding: 0;
            width: 100%;
            max-width: 100%;
        }

        .db-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .db-page-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .db-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            width: 100%;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .db-table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
            /* Prevent wrapping causing bad layout */
        }

        .db-table th,
        .db-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .db-table th {
            background: var(--bg-input);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .db-table tr:last-child td {
            border-bottom: none;
        }

        .file-icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-icon svg {
            width: 18px;
            height: 18px;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(60, 135, 58, 0.15);
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: 1px solid transparent;
            cursor: pointer;
            padding: 6px;
            color: var(--text-secondary);
            border-radius: 4px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon:hover {
            background: var(--bg-input);
            color: var(--primary);
            border-color: var(--border-color);
        }

        .btn-icon.danger:hover {
            color: var(--danger);
            background: rgba(244, 67, 54, 0.1);
            border-color: rgba(244, 67, 54, 0.2);
        }

        .btn-icon svg {
            width: 16px;
            height: 16px;
        }

        .text-center {
            text-align: center;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 600px) {
            .db-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .db-actions {
                width: 100%;
            }

            .db-actions .btn {
                width: 100%;
                /* Full width button on mobile */
                justify-content: center;
            }

            .db-table th,
            .db-table td {
                padding: 10px;
                font-size: 13px;
            }

            /* Optional: Hide less important columns on very small screens */
            .db-table th:nth-child(2),
            /* Type */
            .db-table td:nth-child(2),
            .db-table th:nth-child(3),
            /* Size */
            .db-table td:nth-child(3) {
                display: none;
            }
        }

        <?php
        $content = ob_get_clean();
        // The standard pattern in this app seems to be including the layout at the end, 
// and the layout echoes $content.
        include __DIR__ . '/../../shared/layouts/main.php';
        ?>