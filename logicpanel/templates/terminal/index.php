<?php
$page_title = 'Terminal - ' . ($service->name ?? 'Unknown');
$current_page = 'terminal';
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
            <h1 class="text-xl sm:text-2xl font-bold">Terminal</h1>
            <p class="text-sm text-[var(--text-secondary)]">
                <?= htmlspecialchars($service->name) ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button onclick="clearTerminal()"
            class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
            <span class="hidden sm:inline">Clear</span>
        </button>
    </div>
</div>

<!-- Terminal -->
<div class="card rounded-xl overflow-hidden">
    <!-- Terminal Header -->
    <div class="flex items-center justify-between px-4 py-3 bg-gray-900 border-b border-gray-700">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
        </div>
        <div class="text-gray-400 text-sm font-mono">
            <?= htmlspecialchars($service->name) ?>:/app
        </div>
        <div class="text-gray-500 text-xs">
            <span class="hidden sm:inline">Node v
                <?= htmlspecialchars($service->node_version) ?>
            </span>
        </div>
    </div>

    <!-- Terminal Output -->
    <div id="terminalOutput" class="bg-gray-950 p-4 h-[50vh] sm:h-[60vh] overflow-auto font-mono text-sm text-gray-200">
        <div class="text-green-400 mb-2">Welcome to LogicPanel Terminal</div>
        <div class="text-gray-500 mb-4">Connected to container:
            <?= htmlspecialchars($service->container_name ?? $service->container_id) ?>
        </div>
        <div class="text-yellow-400 mb-4">Type commands below. Working directory: /app</div>
    </div>

    <!-- Terminal Input -->
    <div class="flex items-center gap-2 p-3 bg-gray-900 border-t border-gray-700">
        <span class="text-green-400 font-mono text-sm">$</span>
        <input type="text" id="terminalInput"
            class="flex-1 bg-transparent border-none outline-none text-white font-mono text-sm placeholder-gray-600"
            placeholder="Enter command..." onkeypress="handleKeyPress(event)" autofocus>
        <button onclick="executeCommand()"
            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
            Run
        </button>
    </div>
</div>

<!-- Quick Commands (Mobile Friendly) -->
<div class="mt-6">
    <h3 class="text-sm font-semibold text-[var(--text-secondary)] mb-3">Quick Commands</h3>
    <div class="flex flex-wrap gap-2">
        <button onclick="quickCommand('ls -la')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">ls
            -la</button>
        <button onclick="quickCommand('npm run dev')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">npm
            run dev</button>
        <button onclick="quickCommand('npm install')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">npm
            install</button>
        <button onclick="quickCommand('cat package.json')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">cat
            package.json</button>
        <button onclick="quickCommand('node -v')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">node
            -v</button>
        <button onclick="quickCommand('npm -v')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">npm
            -v</button>
        <button onclick="quickCommand('pwd')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">pwd</button>
        <button onclick="quickCommand('df -h')"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-sm font-mono transition-colors">df
            -h</button>
    </div>
</div>

<script>
    const serviceId = <?= $service->id ?>;
    const terminalOutput = document.getElementById('terminalOutput');
    const terminalInput = document.getElementById('terminalInput');
    let commandHistory = [];
    let historyIndex = -1;

    function handleKeyPress(event) {
        if (event.key === 'Enter') {
            executeCommand();
        }
    }

    // Arrow keys for command history
    terminalInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++;
                terminalInput.value = commandHistory[commandHistory.length - 1 - historyIndex];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIndex > 0) {
                historyIndex--;
                terminalInput.value = commandHistory[commandHistory.length - 1 - historyIndex];
            } else {
                historyIndex = -1;
                terminalInput.value = '';
            }
        }
    });

    async function executeCommand() {
        const command = terminalInput.value.trim();
        if (!command) return;

        // Add to history
        commandHistory.push(command);
        historyIndex = -1;

        // Display command
        appendOutput(`<span class="text-green-400">$</span> ${escapeHtml(command)}`, 'text-white');
        terminalInput.value = '';

        try {
            const response = await fetch(`<?= $base_url ?>/terminal/${serviceId}/exec`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ command })
            });

            const data = await response.json();

            if (data.success) {
                if (data.output) {
                    appendOutput(escapeHtml(data.output), 'text-gray-300');
                }
            } else {
                appendOutput(data.error || 'Command failed', 'text-red-400');
            }
        } catch (error) {
            appendOutput('Error: ' + error.message, 'text-red-400');
        }

        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    function quickCommand(cmd) {
        terminalInput.value = cmd;
        executeCommand();
    }

    function appendOutput(text, colorClass = '') {
        const line = document.createElement('div');
        line.className = `mb-1 ${colorClass}`;
        line.innerHTML = text;
        terminalOutput.appendChild(line);
    }

    function clearTerminal() {
        terminalOutput.innerHTML = '<div class="text-gray-500">Terminal cleared</div>';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Focus input on load
    document.addEventListener('DOMContentLoaded', () => {
        terminalInput.focus();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>