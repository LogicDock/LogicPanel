<?php
$page_title = 'Settings';
$current_page = 'tools';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Account Settings</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i data-lucide="alert-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i data-lucide="check-circle"></i>
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div style="max-width: 600px;">
    <!-- Profile Settings -->
    <div class="card mb-20">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="user"></i>
                Profile Information
            </h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user->name ?? '') ?>"
                        class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>"
                        class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" value="<?= htmlspecialchars($user->username ?? '') ?>" class="form-control"
                        disabled style="background: var(--bg-input); cursor: not-allowed;">
                    <small class="text-muted">Username cannot be changed</small>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Password Change -->
    <div class="card mb-20">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="lock"></i>
                Change Password
            </h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" onsubmit="return validatePassword()">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="8"
                        required>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" id="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-secondary">
                    <i data-lucide="key"></i>
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- Theme Settings -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="palette"></i>
                Appearance
            </h2>
        </div>
        <div class="card-body">
            <div class="theme-options">
                <button onclick="setThemePreference('light')"
                    class="theme-option <?= ($user->theme ?? 'auto') === 'light' ? 'active' : '' ?>">
                    <i data-lucide="sun"></i>
                    <span>Light</span>
                </button>

                <button onclick="setThemePreference('dark')"
                    class="theme-option <?= ($user->theme ?? 'auto') === 'dark' ? 'active' : '' ?>">
                    <i data-lucide="moon"></i>
                    <span>Dark</span>
                </button>

                <button onclick="setThemePreference('auto')"
                    class="theme-option <?= ($user->theme ?? 'auto') === 'auto' ? 'active' : '' ?>">
                    <i data-lucide="laptop"></i>
                    <span>System</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="card mb-20">
        <div class="card-header">
            <h2 class="card-title">
                <i data-lucide="shield-check"></i>
                Two-Factor Authentication
            </h2>
        </div>
        <div class="card-body">
            <?php if ($user->two_factor_enabled ?? false): ?>
                <div class="twofa-status enabled">
                    <div class="twofa-icon">
                        <i data-lucide="check-circle"></i>
                    </div>
                    <div class="twofa-info">
                        <h4>2FA is Enabled</h4>
                        <p>Your account is protected with two-factor authentication.</p>
                    </div>
                    <button onclick="disable2FA()" class="btn btn-danger">
                        <i data-lucide="shield-off"></i>
                        Disable 2FA
                    </button>
                </div>
            <?php else: ?>
                <div class="twofa-status disabled">
                    <div class="twofa-icon warning">
                        <i data-lucide="shield-alert"></i>
                    </div>
                    <div class="twofa-info">
                        <h4>2FA is Not Enabled</h4>
                        <p>Add an extra layer of security to your account.</p>
                    </div>
                    <button onclick="setup2FA()" class="btn btn-primary">
                        <i data-lucide="shield-check"></i>
                        Enable 2FA
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 2FA Setup Modal -->
<div id="twofa-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Setup Two-Factor Authentication</h2>
            <button onclick="closeModal()" class="modal-close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="twofa-step1">
                <p class="mb-15">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
                <div class="qr-container" id="qr-container">
                    <div class="qr-loading">Loading...</div>
                </div>
                <div class="secret-key">
                    <label>Or enter this key manually:</label>
                    <code id="secret-key">Loading...</code>
                </div>
                <div class="form-group mt-15">
                    <label class="form-label">Enter the 6-digit code from your app</label>
                    <input type="text" id="verify-code" class="form-control" maxlength="6" placeholder="000000"
                        pattern="[0-9]{6}" required>
                </div>
                <button onclick="verify2FA()" class="btn btn-primary" style="width: 100%;">
                    <i data-lucide="check"></i>
                    Verify & Enable
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
<div id="disable-2fa-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Disable Two-Factor Authentication</h2>
            <button onclick="closeDisableModal()" class="modal-close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="mb-15">Enter your current password to disable 2FA:</p>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" id="disable-password" class="form-control" required>
            </div>
            <button onclick="confirmDisable2FA()" class="btn btn-danger" style="width: 100%;">
                Disable 2FA
            </button>
        </div>
    </div>
</div>

<style>
    .alert {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        font-size: 13px;
    }

    .alert svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .alert-success {
        background: rgba(60, 135, 58, 0.1);
        color: var(--primary);
        border: 1px solid rgba(60, 135, 58, 0.2);
    }

    .alert-danger {
        background: rgba(244, 67, 54, 0.1);
        color: var(--danger);
        border: 1px solid rgba(244, 67, 54, 0.2);
    }

    .theme-options {
        display: flex;
        gap: 10px;
    }

    .theme-option {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 20px 15px;
        background: var(--bg-input);
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .theme-option:hover {
        border-color: var(--text-muted);
    }

    .theme-option.active {
        border-color: var(--primary);
        background: rgba(60, 135, 58, 0.1);
    }

    .theme-option svg {
        width: 24px;
        height: 24px;
    }

    .theme-option span {
        font-size: 13px;
        font-weight: 500;
    }
/* 2FA Styles */
    .twofa-status {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: 8px;
    }

    .twofa-status.enabled {
        background: rgba(60, 135, 58, 0.1);
    }

    .twofa-status.disabled {
        background: rgba(255, 152, 0, 0.1);
    }

    .twofa-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .twofa-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .twofa-icon.warning {
        background: var(--warning);
    }

    .twofa-info {
        flex: 1;
    }

    .twofa-info h4 {
        margin: 0 0 4px 0;
        font-size: 15px;
    }

    .twofa-info p {
        margin: 0;
        font-size: 13px;
        color: var(--text-secondary);
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: var(--bg-card);
        border-radius: 12px;
        width: 90%;
        max-width: 450px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .modal-close {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 4px;
    }

    .modal-body {
        padding: 20px;
    }

    .qr-container {
        text-align: center;
        padding: 20px;
        background: white;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .qr-container img {
        max-width: 200px;
    }

    .secret-key {
        text-align: center;
        margin-bottom: 15px;
    }

    .secret-key label {
        display: block;
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 5px;
    }

    .secret-key code {
        font-size: 14px;
        padding: 8px 12px;
        background: var(--bg-input);
        border-radius: 4px;
        font-family: monospace;
        letter-spacing: 2px;
    }

    #verify-code {
        text-align: center;
        font-size: 24px;
        letter-spacing: 8px;
        font-family: monospace;
    }
</style>

<script>
    function validatePassword() {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;

        if (newPass !== confirmPass) {
            alert('New password and confirm password do not match!');
            return false;
        }

        if (newPass.length < 8) {
            alert('Password must be at least 8 characters!');
            return false;
        }

        return true;
    }

    async function setThemePreference(theme) {
        setTheme(theme);

        try {
            await fetch('<?= $base_url ?>/settings/theme', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme })
            });
            location.reload();
        } catch (error) {
            console.error('Failed to save theme:', error);
        }
    }

    // 2FA Functions
    let currentSecret = null;

    async function setup2FA() {
        document.getElementById('twofa-modal').style.display = 'flex';
        lucide.createIcons();

        try {
            const response = await fetch('<?= $base_url ?>/settings/2fa/setup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            const result = await response.json();
            if (result.success) {
                currentSecret = result.secret;
                document.getElementById('qr-container').innerHTML = `<img src="${result.qr_url}" alt="QR Code">`;
                document.getElementById('secret-key').textContent = result.secret;
            } else {
                alert(result.error || 'Failed to setup 2FA');
                closeModal();
            }
        } catch (error) {
            alert('Error: ' + error.message);
            closeModal();
        }
    }

    async function verify2FA() {
        const code = document.getElementById('verify-code').value;
        
        if (!code || code.length !== 6) {
            alert('Please enter a valid 6-digit code');
            return;
        }

        try {
            const response = await fetch('<?= $base_url ?>/settings/2fa/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code, secret: currentSecret })
            });

            const result = await response.json();
            if (result.success) {
                alert('Two-factor authentication has been enabled!');
                location.reload();
            } else {
                alert(result.error || 'Invalid code. Please try again.');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    function closeModal() {
        document.getElementById('twofa-modal').style.display = 'none';
        currentSecret = null;
    }

    function disable2FA() {
        document.getElementById('disable-2fa-modal').style.display = 'flex';
        lucide.createIcons();
    }

    function closeDisableModal() {
        document.getElementById('disable-2fa-modal').style.display = 'none';
        document.getElementById('disable-password').value = '';
    }

    async function confirmDisable2FA() {
        const password = document.getElementById('disable-password').value;
        
        if (!password) {
            alert('Please enter your password');
            return;
        }

        try {
            const response = await fetch('<?= $base_url ?>/settings/2fa/disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password })
            });

            const result = await response.json();
            if (result.success) {
                alert('Two-factor authentication has been disabled.');
                location.reload();
            } else {
                alert(result.error || 'Failed to disable 2FA');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    // Close modals on backdrop click
    document.getElementById('twofa-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.getElementById('disable-2fa-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDisableModal();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>