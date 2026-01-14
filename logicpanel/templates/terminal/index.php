<?php
$page_title = 'Terminal - ' . ($service->name ?? 'Unknown');
$current_page = 'terminal';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <a href="<?= $base_url ?>/services/<?= $service->id ?>" class="back-btn">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-header-info">
            <h1 class="page-title">Terminal</h1>
            <p class="page-subtitle"><?= htmlspecialchars($service->name) ?></p>
        </div>
    </div>
    <div class="page-header-actions">
        <button onclick="clearTerminal()" class="btn btn-secondary">
            <i data-lucide="trash-2"></i> Clear
        </button>
    </div>
</div>

<!-- Terminal Card -->
<div class="terminal-card">
    <!-- Terminal Header -->
    <div class="terminal-header">
        <div class="terminal-dots">
            <span class="dot red"></span>
            <span class="dot yellow"></span>
            <span class="dot green"></span>
        </div>
        <div class="terminal-title">
            <?= htmlspecialchars($service->name) ?>:/app
        </div>
        <div class="terminal-info">
            Node v<?= htmlspecialchars($service->node_version ?? '20') ?>
        </div>
    </div>

    <!-- Terminal Output -->
    <div id="terminalOutput" class="terminal-output">
        <div class="line success">Welcome to LogicPanel Terminal</div>
        <div class="line muted">Connected to container:
            <?= htmlspecialchars($service->container_name ?? $service->container_id ?? 'unknown') ?></div>
        <div class="line warning">Type commands below. Working directory: /app</div>
    </div>

    <!-- Terminal Input -->
    <div class="terminal-input-area">
        <span class="prompt">$</span>
        <input type="text" id="terminalInput" class="terminal-input" placeholder="Enter command..."
            onkeypress="handleKeyPress(event)" autofocus>
        <button onclick="executeCommand()" class="btn btn-primary">Run</button>
    </div>
</div>

<!-- Quick Commands -->
<div class="quick-commands-section">
    <h3 class="section-title">Quick Commands</h3>
    <div class="quick-commands">
        <button onclick="quickCommand('ls -la')" class="quick-cmd">ls -la</button>
        <button onclick="quickCommand('npm run dev')" class="quick-cmd">npm run dev</button>
        <button onclick="quickCommand('npm install')" class="quick-cmd">npm install</button>
        <button onclick="quickCommand('cat package.json')" class="quick-cmd">cat package.json</button>
        <button onclick="quickCommand('node -v')" class="quick-cmd">node -v</button>
        <button onclick="quickCommand('npm -v')" class="quick-cmd">npm -v</button>
        <button onclick="quickCommand('pwd')" class="quick-cmd">pwd</button>
        <button onclick="quickCommand('df -h')" class="quick-cmd">df -h</button>
        <button onclick="quickCommand('pm2 list')" class="quick-cmd">pm2 list</button>
        <button onclick="quickCommand('pm2 logs')" class="quick-cmd">pm2 logs</button>
    </div>
</div>

<style>
    /* Page Header */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
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

    /* Terminal Card */
    .terminal-card {
        background: #0d1117;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #30363d;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .terminal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #161b22;
        border-bottom: 1px solid #30363d;
    }

    .terminal-dots {
        display: flex;
        gap: 8px;
    }

    .terminal-dots .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .terminal-dots .red {
        background: #ff5f56;
    }

    .terminal-dots .yellow {
        background: #ffbd2e;
    }

    .terminal-dots .green {
        background: #27c93f;
    }

    .terminal-title {
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 13px;
        color: #8b949e;
    }

    .terminal-info {
        font-size: 12px;
        color: #6e7681;
    }

    .terminal-output {
        padding: 16px;
        height: 55vh;
        overflow-y: auto;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        color: #c9d1d9;
    }

    .terminal-output .line {
        margin-bottom: 4px;
        white-space: pre-wrap;
        word-break: break-all;
    }

    .terminal-output .success {
        color: #3fb950;
    }

    .terminal-output .warning {
        color: #d29922;
    }

    .terminal-output .error {
        color: #f85149;
    }

    .terminal-output .muted {
        color: #6e7681;
    }

    .terminal-output .command {
        color: #58a6ff;
    }

    .terminal-input-area {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: #161b22;
        border-top: 1px solid #30363d;
    }

    .prompt {
        color: #3fb950;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 14px;
        font-weight: 600;
    }

    .terminal-input {
        flex: 1;
        background: transparent;
        border: none;
        outline: none;
        color: #f0f6fc;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 14px;
    }

    .terminal-input::placeholder {
        color: #484f58;
    }

    /* Quick Commands */
    .quick-commands-section {
        margin-top: 24px;
    }

    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 12px;
    }

    .quick-commands {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .quick-cmd {
        padding: 8px 14px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .quick-cmd:hover {
        border-color: var(--primary);
        background: rgba(110, 86, 207, 0.1);
        color: var(--primary);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-actions {
            align-self: flex-end;
        }

        .terminal-output {
            height: 45vh;
        }
    }
</style>

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
        appendOutput(`<span class="prompt">$</span> ${escapeHtml(command)}`, 'command');
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
                    appendOutput(escapeHtml(data.output));
                }
            } else {
                appendOutput(data.error || 'Command failed', 'error');
            }
        } catch (error) {
            appendOutput('Error: ' + error.message, 'error');
        }

        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    function quickCommand(cmd) {
        terminalInput.value = cmd;
        executeCommand();
    }

    function appendOutput(text, className = '') {
        const line = document.createElement('div');
        line.className = `line ${className}`;
        line.innerHTML = text;
        terminalOutput.appendChild(line);
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    function clearTerminal() {
        terminalOutput.innerHTML = '<div class="line muted">Terminal cleared</div>';
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