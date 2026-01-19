<?php
/**
 * LogicPanel - Advanced File Manager Template
 * TinyFM-inspired UI for Docker Containers
 */
?>

<div class="lp-fm-container">
    <div class="lp-fm-header">
        <div class="lp-fm-breadcrumb" id="fm-breadcrumb">
            <span class="crumb" onclick="browse('/app')"><i data-lucide="home"></i> root</span>
        </div>
        <div class="lp-fm-actions">
            <button class="btn btn-secondary" onclick="showNewFolderModal()">
                <i data-lucide="folder-plus"></i> New Folder
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('file-upload').click()">
                <i data-lucide="upload"></i> Upload
                <input type="file" id="file-upload" hidden onchange="uploadFile(this)">
            </button>
        </div>
    </div>

    <div class="lp-fm-toolbar">
        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" placeholder="Search files..." id="fm-search" onkeyup="filterFiles()">
        </div>
        <div class="view-stats" id="fm-info">
            Loading...
        </div>
    </div>

    <div class="lp-fm-explorer">
        <table class="lp-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="fm-list">
                <!-- Files will be loaded here -->
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <div class="spinner"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- New Folder Modal -->
<div id="new-folder-modal" class="lp-modal">
    <div class="lp-modal-content">
        <h3>Create New Folder</h3>
        <input type="text" id="new-folder-name" class="form-input" placeholder="Folder Name">
        <div class="lp-modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('new-folder-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="createFolder()">Create</button>
        </div>
    </div>
</div>

<style>
    .lp-fm-container {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        overflow: hidden;
        margin-top: 20px;
    }

    .lp-fm-header {
        padding: 15px 20px;
        background: rgba(255, 255, 255, 0.02);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .lp-fm-breadcrumb {
        display: flex;
        gap: 8px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .lp-fm-breadcrumb .crumb {
        cursor: pointer;
        transition: color 0.2s;
    }

    .lp-fm-breadcrumb .crumb:hover {
        color: var(--primary);
    }

    .lp-fm-toolbar {
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(0, 0, 0, 0.1);
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        color: var(--text-muted);
    }

    .search-box input {
        width: 100%;
        padding: 8px 10px 8px 35px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        color: white;
    }

    .lp-fm-explorer {
        max-height: 600px;
        overflow-y: auto;
    }

    .file-item {
        cursor: pointer;
        transition: background 0.2s;
    }

    .file-item:hover {
        background: rgba(255, 255, 255, 0.03);
    }

    .file-icon {
        width: 20px;
        margin-right: 10px;
        vertical-align: middle;
    }

    .folder {
        color: #f1c40f;
    }

    .file {
        color: #ecf0f1;
    }

    .btn-icon {
        padding: 5px;
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.2s;
    }

    .btn-icon:hover {
        color: var(--primary);
    }

    .btn-delete:hover {
        color: #e74c3c;
    }
</style>

<script>
    let currentPath = '/app';
    const serviceId = <?= $service->id ?>;

    async function browse(path) {
        currentPath = path;
        const list = document.getElementById('fm-list');

        // Update breadcrumb
        updateBreadcrumb(path);

        try {
            const response = await fetch(`/files/${serviceId}/browse?path=${encodeURIComponent(path)}`);
            const data = await response.json();

            if (data.success) {
                renderFiles(data.files);
                document.getElementById('fm-info').textContent = `${data.files.length} items`;
            } else {
                alert('Error: ' + data.error);
            }
        } catch (e) {
            console.error(e);
        }
    }

    function updateBreadcrumb(path) {
        const container = document.getElementById('fm-breadcrumb');
        const parts = path.split('/').filter(p => p);
        let html = '<span class="crumb" onclick="browse(\'/app\')"><i data-lucide="home" style="width:14px"></i> root</span>';

        let builtPath = '';
        parts.forEach((part, index) => {
            // Skip 'app' since we call it root
            if (part === 'app' && index === 0) return;

            builtPath += '/' + part;
            html += ` <i data-lucide="chevron-right" style="width:12px"></i> <span class="crumb" onclick="browse('/app${builtPath}')">${part}</span>`;
        });

        container.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    }

    function renderFiles(files) {
        const list = document.getElementById('fm-list');
        list.innerHTML = '';

        files.forEach(file => {
            const tr = document.createElement('tr');
            tr.className = 'file-item';

            const icon = file.type === 'directory' ? 'folder' : 'file';
            const onclick = file.type === 'directory' ? `onclick="browse('${file.path}')"` : '';

            tr.innerHTML = `
            <td ${onclick}>
                <i data-lucide="${icon}" class="file-icon ${icon}"></i>
                <span>${file.name}</span>
            </td>
            <td>${file.type === 'directory' ? '--' : file.size_human}</td>
            <td>${file.modified}</td>
            <td class="lp-actions">
                ${file.type === 'file' ? `<button class="btn-icon" title="Download" onclick="downloadFile('${file.path}')"><i data-lucide="download"></i></button>` : ''}
                <button class="btn-icon btn-delete" title="Delete" onclick="deleteFile('${file.path}')"><i data-lucide="trash-2"></i></button>
            </td>
        `;
            list.appendChild(tr);
        });

        if (window.lucide) lucide.createIcons();
    }

    async function uploadFile(input) {
        if (!input.files.length) return;

        const formData = new FormData();
        formData.append('file', input.files[0]);
        formData.append('path', currentPath);

        // Show loading
        document.getElementById('fm-info').innerHTML = '<div class="spinner-sm"></div> Uploading...';

        try {
            const response = await fetch(`/files/${serviceId}/upload`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                browse(currentPath);
            } else {
                alert('Upload failed: ' + data.error);
            }
        } catch (e) {
            alert('Upload error');
        }
    }

    async function deleteFile(path) {
        if (!confirm('Are you sure you want to delete this?')) return;

        try {
            const response = await fetch(`/files/${serviceId}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `path=${encodeURIComponent(path)}`
            });
            const data = await response.json();
            if (data.success) browse(currentPath);
            else alert('Delete failed: ' + data.error);
        } catch (e) { alert('Request failed'); }
    }

    function downloadFile(path) {
        window.location.href = `/files/${serviceId}/download?path=${encodeURIComponent(path)}`;
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', () => browse('/app'));
</script>