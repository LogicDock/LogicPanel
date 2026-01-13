<?php
$page_title = 'File Manager - ' . ($service->name ?? 'Unknown');
$current_page = 'files';
ob_start();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">File Manager</h1>
            <p class="text-sm text-[var(--text-secondary)]">
                <?= htmlspecialchars($service->name) ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button onclick="createFolder()"
            class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <i data-lucide="folder-plus" class="w-4 h-4"></i>
            <span class="hidden sm:inline">New Folder</span>
        </button>
        <label
            class="flex items-center gap-2 px-3 py-2 text-sm bg-primary-500 hover:bg-primary-600 text-white rounded-lg cursor-pointer transition-colors">
            <i data-lucide="upload" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Upload</span>
            <input type="file" id="fileUpload" class="hidden" onchange="uploadFile(event)" multiple>
        </label>
    </div>
</div>

<!-- Breadcrumb -->
<div class="card rounded-xl p-3 mb-4 overflow-x-auto">
    <div id="breadcrumb" class="flex items-center gap-1 text-sm whitespace-nowrap">
        <button onclick="navigateTo('/app')"
            class="flex items-center gap-1 px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
            <span>app</span>
        </button>
    </div>
</div>

<!-- File List -->
<div class="card rounded-xl overflow-hidden">
    <!-- Table Header -->
    <div
        class="hidden sm:grid grid-cols-12 gap-4 px-4 py-3 bg-[var(--bg-secondary)] border-b border-[var(--border-color)] text-sm font-medium text-[var(--text-secondary)]">
        <div class="col-span-6">Name</div>
        <div class="col-span-2">Size</div>
        <div class="col-span-2">Modified</div>
        <div class="col-span-2 text-right">Actions</div>
    </div>

    <!-- File List Container -->
    <div id="fileList" class="divide-y divide-[var(--border-color)]">
        <div class="p-8 text-center text-[var(--text-secondary)]">
            <i data-lucide="loader-2" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
            <p>Loading files...</p>
        </div>
    </div>
</div>

<!-- File Editor Modal -->
<div id="editorModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEditor()"></div>
    <div class="absolute inset-4 sm:inset-8 lg:inset-16 bg-[var(--bg-primary)] rounded-2xl shadow-2xl flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-[var(--border-color)]">
            <div class="flex items-center gap-3 min-w-0">
                <i data-lucide="file-text" class="w-5 h-5 text-[var(--text-secondary)] flex-shrink-0"></i>
                <span id="editorFileName" class="font-medium truncate"></span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="saveFile()"
                    class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Save</span>
                </button>
                <button onclick="closeEditor()"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Editor -->
        <textarea id="fileEditor"
            class="flex-1 p-4 bg-gray-950 text-gray-200 font-mono text-sm resize-none outline-none"></textarea>
    </div>
</div>

<script>
    const serviceId = <?= $service->id ?>;
    let currentPath = '/app';
    let currentEditingFile = null;

    async function loadFiles(path = '/app') {
        currentPath = path;
        updateBreadcrumb(path);

        const container = document.getElementById('fileList');
        container.innerHTML = `
        <div class="p-8 text-center text-[var(--text-secondary)]">
            <i data-lucide="loader-2" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
            <p>Loading...</p>
        </div>
    `;
        lucide.createIcons();

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/browse?path=${encodeURIComponent(path)}`);
            const data = await response.json();

            if (data.success) {
                renderFiles(data.files, path);
            } else {
                container.innerHTML = `<div class="p-8 text-center text-red-500">${data.error || 'Failed to load files'}</div>`;
            }
        } catch (error) {
            container.innerHTML = `<div class="p-8 text-center text-red-500">Error: ${error.message}</div>`;
        }
    }

    function renderFiles(files, path) {
        const container = document.getElementById('fileList');

        if (files.length === 0) {
            container.innerHTML = `
            <div class="p-8 text-center text-[var(--text-secondary)]">
                <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                <p>This folder is empty</p>
            </div>
        `;
            lucide.createIcons();
            return;
        }

        // Add parent directory link if not in root
        let html = '';
        if (path !== '/app') {
            const parentPath = path.split('/').slice(0, -1).join('/') || '/app';
            html += `
            <div class="grid grid-cols-12 gap-4 px-4 py-3 hover:bg-[var(--bg-secondary)] cursor-pointer transition-colors" onclick="navigateTo('${parentPath}')">
                <div class="col-span-12 sm:col-span-6 flex items-center gap-3">
                    <i data-lucide="folder-up" class="w-5 h-5 text-yellow-500"></i>
                    <span class="font-medium">..</span>
                </div>
            </div>
        `;
        }

        files.forEach(file => {
            const isDir = file.type === 'directory';
            const icon = isDir ? 'folder' : getFileIcon(file.name);
            const iconColor = isDir ? 'text-yellow-500' : 'text-blue-500';

            html += `
            <div class="grid grid-cols-12 gap-4 px-4 py-3 hover:bg-[var(--bg-secondary)] transition-colors group">
                <div class="col-span-12 sm:col-span-6 flex items-center gap-3 min-w-0">
                    <i data-lucide="${icon}" class="w-5 h-5 ${iconColor} flex-shrink-0"></i>
                    ${isDir
                    ? `<button onclick="navigateTo('${file.path}')" class="font-medium truncate text-left hover:text-primary-500">${escapeHtml(file.name)}</button>`
                    : `<span class="font-medium truncate">${escapeHtml(file.name)}</span>`
                }
                </div>
                <div class="hidden sm:block col-span-2 text-sm text-[var(--text-secondary)]">
                    ${isDir ? '-' : file.size_human}
                </div>
                <div class="hidden sm:block col-span-2 text-sm text-[var(--text-secondary)]">
                    ${file.modified}
                </div>
                <div class="col-span-12 sm:col-span-2 flex items-center justify-end gap-1 mt-2 sm:mt-0 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                    ${!isDir ? `
                        <button onclick="editFile('${file.path}')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" title="Edit">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="downloadFile('${file.path}')" class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" title="Download">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                    <button onclick="deleteItem('${file.path}')" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 rounded" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
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
        <button onclick="navigateTo('/app')" class="flex items-center gap-1 px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
            <span>app</span>
        </button>
    `;

        let currentBreadcrumbPath = '';
        parts.forEach((part, i) => {
            if (i === 0) return; // Skip 'app'
            currentBreadcrumbPath += '/' + part;
            html += `
            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
            <button onclick="navigateTo('/app${currentBreadcrumbPath}')" class="px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors">
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

    async function editFile(path) {
        currentEditingFile = path;
        document.getElementById('editorFileName').textContent = path.split('/').pop();
        document.getElementById('editorModal').classList.remove('hidden');

        const editor = document.getElementById('fileEditor');
        editor.value = 'Loading...';

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/download?path=${encodeURIComponent(path)}`);
            if (response.ok) {
                editor.value = await response.text();
            } else {
                editor.value = 'Error loading file';
            }
        } catch (error) {
            editor.value = 'Error: ' + error.message;
        }
    }

    async function saveFile() {
        if (!currentEditingFile) return;

        const content = document.getElementById('fileEditor').value;

        try {
            const response = await fetch(`<?= $base_url ?>/files/${serviceId}/edit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: currentEditingFile, content })
            });

            const data = await response.json();
            if (data.success) {
                alert('File saved!');
                closeEditor();
            } else {
                alert(data.error || 'Save failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    function closeEditor() {
        document.getElementById('editorModal').classList.add('hidden');
        currentEditingFile = null;
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

        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('path', currentPath);

            try {
                const response = await fetch(`<?= $base_url ?>/files/${serviceId}/upload`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (!data.success) {
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