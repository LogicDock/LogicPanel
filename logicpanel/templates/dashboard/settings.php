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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>