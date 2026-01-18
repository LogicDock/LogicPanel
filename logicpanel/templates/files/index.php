<?php
$page_title = 'File Manager - ' . ($service->name ?? 'Unknown');
$current_page = 'files';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title">File Manager</h1>
            <p class="page-subtitle"><?= htmlspecialchars($service->name) ?></p>
        </div>
    </div>
    <div class="page-header-actions">
        <button onclick="createFolder()" class="btn btn-secondary">
            <i data-lucide="folder-plus"></i> New Folder
        </button>
        <label class="btn btn-primary upload-btn">
            <i data-lucide="upload"></i> Upload
            <input type="file" id="fileUpload" class="hidden-input" onchange="uploadFile(event)" multiple>
        </label>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb-card">
    <div id="breadcrumb" class="breadcrumb">
        <button onclick="navigateTo('/app')" class="breadcrumb-item">
            <i data-lucide="home"></i>
            <span>app</span>
        </button>
    </div>
</div>

<!-- File List -->
<div class="card file-list-card">
    <!-- Table Header -->
    <div class="file-list-header">
        <div class="col-name">Name</div>
        <div class="col-size">Size</div>
        <div class="col-modified">Modified</div>
        <div class="col-actions">Actions</div>
    </div>

    <!-- File List Container -->
    <div id="fileList" class="file-list">
        <div class="loading-state">
            <i data-lucide="loader-2" class="spin"></i>
            <p>Loading files...</p>
        </div>
    </div>
</div>

<!-- File Editor Modal with Monaco -->
<div id="editorModal" class="modal hidden">
    <div class="modal-backdrop" onclick="closeEditor()"></div>
    <div class="modal-content editor-modal">
        <!-- Modal Header -->
        <div class="modal-header">
            <div class="modal-title">
                <i data-lucide="file-text"></i>
                <span id="editorFileName"></span>
                <span id="editorLanguage" class="editor-lang-badge"></span>
            </div>
            <div class="modal-actions">
                <button onclick="saveFile()" class="btn btn-primary" id="saveBtn">
                    <i data-lucide="save"></i> Save
                </button>
                <button onclick="closeEditor()" class="btn btn-icon">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

        <!-- Monaco Editor Container -->
        <div id="monacoContainer" style="flex: 1; min-height: 400px;"></div>
    </div>
</div>

<!-- Monaco Editor CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>

<style>
    /* Page Header */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
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

    .page-header-actions {
        display: flex;
        gap: 10px;
    }

    .upload-btn {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hidden-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /* Breadcrumb */
    .breadcrumb-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 12px 16px;
        margin-bottom: 16px;
        overflow-x: auto;
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }

    .breadcrumb-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 6px;
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-size: 13px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .breadcrumb-item:hover {
        background: var(--bg-input);
    }

    .breadcrumb-item svg {
        width: 14px;
        height: 14px;
    }

    .breadcrumb-separator {
        color: var(--text-muted);
        width: 16px;
        height: 16px;
    }

    /* File List */
    .file-list-card {
        overflow: hidden;
    }

    .file-list-header {
        display: grid;
        grid-template-columns: 1fr 100px 150px 120px;
        gap: 16px;
        padding: 12px 16px;
        background: var(--bg-input);
        border-bottom: 1px solid var(--border-color);
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
    }

    .col-actions {
        text-align: right;
    }

    .file-list {
        min-height: 300px;
    }

    .file-row {
        display: grid;
        grid-template-columns: 1fr 100px 150px 120px;
        gap: 16px;
        padding: 12px 16px;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s ease;
    }

    .file-row:last-child {
        border-bottom: none;
    }

    .file-row:hover {
        background: var(--bg-input);
    }

    .file-row:hover .file-actions {
        opacity: 1;
    }

    .file-name {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .file-name svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .file-name .folder-icon {
        color: #f59e0b;
    }

    .file-name .file-icon {
        color: #3b82f6;
    }

    .file-name button,
    .file-name span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }

    .file-name button {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-primary);
        padding: 0;
        text-align: left;
    }

    .file-name button:hover {
        color: var(--primary);
    }

    .file-size,
    .file-modified {
        font-size: 13px;
        color: var(--text-secondary);
    }

    .file-actions {
        display: flex;
        justify-content: flex-end;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .file-actions .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        border-radius: 6px;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .file-actions .btn-icon:hover {
        background: var(--bg-input);
        color: var(--text-primary);
    }

    .file-actions .btn-icon.delete:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .file-actions .btn-icon svg {
        width: 16px;
        height: 16px;
    }

    /* States */
    .loading-state,
    .empty-state,
    .error-state {
        padding: 60px 20px;
        text-align: center;
        color: var(--text-secondary);
    }

    .loading-state svg,
    .empty-state svg {
        width: 40px;
        height: 40px;
        margin: 0 auto 12px;
        opacity: 0.5;
    }

    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .error-state {
        color: #ef4444;
    }

    /* Modal */
    .modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal.hidden {
        display: none;
    }

    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
    }

    .modal-content {
        position: relative;
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        max-width: 95vw;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    .editor-modal {
        width: 900px;
        height: 80vh;
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        min-width: 0;
    }

    .modal-title svg {
        width: 18px;
        height: 18px;
        color: var(--text-secondary);
        flex-shrink: 0;
    }

    .modal-title span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .modal-actions {
        display: flex;
        gap: 8px;
    }

    .editor-lang-badge {
        font-size: 11px;
        background: var(--primary);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        margin-left: 10px;
        text-transform: uppercase;
    }

    #monacoContainer {
        border-radius: 0 0 12px 12px;
        overflow: hidden;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .file-list-header {
            display: none;
        }

        .file-row {
            grid-template-columns: 1fr auto;
        }

        .file-size,
        .file-modified {
            display: none;
        }

        .file-actions {
            opacity: 1;
        }

        .editor-modal {
            width: 95vw;
            height: 85vh;
        }
    }
</style>

<script>
    const serviceId = <?= $service->id ?>;
    let currentPath = '/app';
    let currentEditingFile = null;

    async function loadFiles(path = '/app') {
        currentPath = path;
        updateBreadcrumb(path);

        const container = document.getElementById('fileList');
        container.innerHTML = `
        <div class="loading-state">
            <i data-lucide="loader-2" class="spin"></i>
            <p>Loading files...</p>
        </div>
    `;
        lucide.createIcons();

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/browse?path=${encodeURIComponent(path)}`);
            const data = await response.json();

            if (data.success) {
                renderFiles(data.files, path);
            } else {
                container.innerHTML = `<div class="error-state">${data.error || 'Failed to load files'}</div>`;
            }
        } catch (error) {
            container.innerHTML = `<div class="error-state">Error: ${error.message}</div>`;
        }
    }

    function renderFiles(files, path) {
        const container = document.getElementById('fileList');

        if (files.length === 0 && path === '/app') {
            container.innerHTML = `
            <div class="empty-state">
                <i data-lucide="folder-open"></i>
                <p>This folder is empty</p>
            </div>
        `;
            lucide.createIcons();
            return;
        }

        let html = '';

        // Add parent directory link if not in root
        if (path !== '/app') {
            const parentPath = path.split('/').slice(0, -1).join('/') || '/app';
            html += `
            <div class="file-row" onclick="navigateTo('${parentPath}')" style="cursor: pointer;">
                <div class="file-name">
                    <i data-lucide="folder-up" class="folder-icon"></i>
                    <span>..</span>
                </div>
                <div class="file-size">-</div>
                <div class="file-modified">-</div>
                <div class="file-actions"></div>
            </div>
        `;
        }

        files.forEach(file => {
            const isDir = file.type === 'directory';
            const icon = isDir ? 'folder' : getFileIcon(file.name);
            const iconClass = isDir ? 'folder-icon' : 'file-icon';

            html += `
            <div class="file-row">
                <div class="file-name">
                    <i data-lucide="${icon}" class="${iconClass}"></i>
                    ${isDir
                    ? `<button onclick="navigateTo('${file.path}')">${escapeHtml(file.name)}</button>`
                    : `<span>${escapeHtml(file.name)}</span>`
                }
                </div>
                <div class="file-size">${isDir ? '-' : (file.size_human || '-')}</div>
                <div class="file-modified">${file.modified || '-'}</div>
                <div class="file-actions">
                    ${!isDir ? `
                        <button onclick="editFile('${file.path}')" class="btn-icon" title="Edit">
                            <i data-lucide="edit-2"></i>
                        </button>
                        <button onclick="downloadFile('${file.path}')" class="btn-icon" title="Download">
                            <i data-lucide="download"></i>
                        </button>
                    ` : ''}
                    <button onclick="deleteItem('${file.path}')" class="btn-icon delete" title="Delete">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        `;
        });

        container.innerHTML = html;
        lucide.createIcons();
    }

    function updateBreadcrumb(path) {
        const parts = path.split('/').filter(p => p);
        let html = `
        <button onclick="navigateTo('/app')" class="breadcrumb-item">
            <i data-lucide="home"></i>
            <span>app</span>
        </button>
    `;

        let currentBreadcrumbPath = '';
        parts.forEach((part, i) => {
            if (i === 0) return; // Skip 'app'
            currentBreadcrumbPath += '/' + part;
            html += `
            <i data-lucide="chevron-right" class="breadcrumb-separator"></i>
            <button onclick="navigateTo('/app${currentBreadcrumbPath}')" class="breadcrumb-item">
                ${escapeHtml(part)}
            </button>
        `;
        });

        document.getElementById('breadcrumb').innerHTML = html;
        lucide.createIcons();
    }

    function navigateTo(path) {
        loadFiles(path);
    }

    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'js': 'file-code',
            'ts': 'file-code',
            'json': 'file-json',
            'html': 'file-code',
            'css': 'file-code',
            'md': 'file-text',
            'txt': 'file-text',
            'png': 'image',
            'jpg': 'image',
            'jpeg': 'image',
            'gif': 'image',
            'svg': 'image',
            'zip': 'file-archive',
            'tar': 'file-archive',
            'gz': 'file-archive',
        };
        return icons[ext] || 'file';
    }

    // Monaco Editor instance
    let monacoEditor = null;
    let monacoReady = false;

    // Initialize Monaco
    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
    require(['vs/editor/editor.main'], function () {
        monacoReady = true;
        monaco.editor.defineTheme('logicpanel-dark', {
            base: 'vs-dark',
            inherit: true,
            rules: [],
            colors: {
                'editor.background': '#1e2127',
                'editor.lineHighlightBackground': '#2a2e36'
            }
        });
    });

    // Get language from file extension
    function getMonacoLanguage(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const langMap = {
            'js': 'javascript',
            'mjs': 'javascript',
            'jsx': 'javascript',
            'ts': 'typescript',
            'tsx': 'typescript',
            'json': 'json',
            'html': 'html',
            'htm': 'html',
            'css': 'css',
            'scss': 'scss',
            'less': 'less',
            'md': 'markdown',
            'py': 'python',
            'rb': 'ruby',
            'php': 'php',
            'java': 'java',
            'go': 'go',
            'rs': 'rust',
            'sql': 'sql',
            'sh': 'shell',
            'bash': 'shell',
            'yml': 'yaml',
            'yaml': 'yaml',
            'xml': 'xml',
            'env': 'plaintext',
            'txt': 'plaintext',
            'dockerfile': 'dockerfile',
        };
        return langMap[ext] || 'plaintext';
    }

    async function editFile(path) {
        if (!monacoReady) {
            alert('Editor is still loading, please wait...');
            return;
        }

        currentEditingFile = path;
        const filename = path.split('/').pop();
        const language = getMonacoLanguage(filename);

        document.getElementById('editorFileName').textContent = filename;
        document.getElementById('editorLanguage').textContent = language;
        document.getElementById('editorModal').classList.remove('hidden');

        // Initialize or update Monaco Editor
        const container = document.getElementById('monacoContainer');
        if (monacoEditor) {
            monacoEditor.dispose();
        }

        monacoEditor = monaco.editor.create(container, {
            value: 'Loading file...',
            language: language,
            theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'logicpanel-dark' : 'vs',
            automaticLayout: true,
            fontSize: 14,
            minimap: { enabled: true },
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            tabSize: 2,
            renderWhitespace: 'selection',
            bracketPairColorization: { enabled: true }
        });

        // Add Ctrl+S shortcut
        monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function () {
            saveFile();
        });

        // Load file content
        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/download?path=${encodeURIComponent(path)}`);
            if (response.ok) {
                const content = await response.text();
                monacoEditor.setValue(content);
            } else {
                monacoEditor.setValue('// Error loading file');
            }
        } catch (error) {
            monacoEditor.setValue('// Error: ' + error.message);
        }
    }

    async function saveFile() {
        if (!currentEditingFile || !monacoEditor) return;

        const saveBtn = document.getElementById('saveBtn');
        const originalHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Saving...';
        saveBtn.disabled = true;
        lucide.createIcons();

        const content = monacoEditor.getValue();

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/edit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: currentEditingFile, content })
            });

            const data = await response.json();
            if (data.success) {
                saveBtn.innerHTML = '<i data-lucide="check"></i> Saved!';
                lucide.createIcons();
                setTimeout(() => {
                    saveBtn.innerHTML = originalHtml;
                    saveBtn.disabled = false;
                    lucide.createIcons();
                }, 1500);
            } else {
                alert(data.error || 'Save failed');
                saveBtn.innerHTML = originalHtml;
                saveBtn.disabled = false;
                lucide.createIcons();
            }
        } catch (error) {
            alert('Error: ' + error.message);
            saveBtn.innerHTML = originalHtml;
            saveBtn.disabled = false;
            lucide.createIcons();
        }
    }

    function closeEditor() {
        document.getElementById('editorModal').classList.add('hidden');
        currentEditingFile = null;
        if (monacoEditor) {
            monacoEditor.dispose();
            monacoEditor = null;
        }
    }

    function downloadFile(path) {
        window.open(`<?= $base_url ?>/files/${serviceId}/download?path=${encodeURIComponent(path)}`, '_blank');
    }

    async function deleteItem(path) {
        if (!confirm('Are you sure you want to delete this item?')) return;

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path })
            });

            const data = await response.json();
            if (data.success) {
                loadFiles(currentPath);
            } else {
                alert(data.error || 'Delete failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function createFolder() {
        const name = prompt('Folder name:');
        if (!name) return;

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/mkdir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: currentPath, name })
            });

            const data = await response.json();
            if (data.success) {
                loadFiles(currentPath);
            } else {
                alert(data.error || 'Create failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function uploadFile(event) {
        const files = event.target.files;
        if (!files.length) return;

        const archiveExtensions = ['zip', 'tar', 'gz', 'tgz'];

        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', currentPath);

            // Check if it's an archive and ask user
            const ext = file.name.split('.').pop().toLowerCase();
            const isArchive = archiveExtensions.includes(ext);

            if (isArchive) {
                const extract = confirm(`${file.name} is an archive.\n\nClick OK to extract its contents.\nClick Cancel to upload as-is.`);
                formData.append('extract', extract ? 'true' : 'false');
            }

            try {
                const response = await fetch(`<?= $base_url ?>/files/${serviceId}/upload`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    if (data.extracted) {
                        alert(`${file.name} extracted successfully!`);
                    }
                } else {
                    alert(`Failed to upload ${file.name}: ${data.error}`);
                }
            } catch (error) {
                alert(`Error uploading ${file.name}: ${error.message}`);
            }
        }

        loadFiles(currentPath);
        event.target.value = '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load files on page load
    document.addEventListener('DOMContentLoaded', () => loadFiles('/app'));
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>