<?php
$page_title = 'Create User';
$current_page = 'reseller_users';
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Create New User</h1>
    <a href="<?= $base_url ?>/reseller/users" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i>
        Back to Users
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h2 class="card-title">
            <i data-lucide="user-plus"></i>
            User Details
        </h2>
    </div>
    <div class="card-body">
        <form id="createUserForm">
            <div class="form-group">
                <label class="form-label">Username <span class="required">*</span></label>
                <input type="text" name="username" class="form-control" required placeholder="e.g., johndoe"
                    pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores">
                <small class="text-muted">Only letters, numbers, and underscores</small>
            </div>

            <div class="form-group">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="user@example.com">
            </div>

            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" placeholder="John Doe">
            </div>

            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <div class="password-input">
                    <input type="password" name="password" id="password" class="form-control" required minlength="8"
                        placeholder="Minimum 8 characters">
                    <button type="button" onclick="generatePassword()" class="btn btn-secondary btn-sm">
                        Generate
                    </button>
                </div>
            </div>

            <?php if (!empty($packages)): ?>
                <div class="form-group">
                    <label class="form-label">Assign Package</label>
                    <select name="package_id" class="form-control">
                        <option value="">No package</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= $pkg->id ?>">
                                <?= htmlspecialchars($pkg->display_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i data-lucide="user-plus"></i>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .required {
        color: var(--danger);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .password-input {
        display: flex;
        gap: 10px;
    }

    .password-input input {
        flex: 1;
    }

    .form-actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }
</style>

<script>
    function generatePassword() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 16; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('password').value = password;
        document.getElementById('password').type = 'text';
    }

    document.getElementById('createUserForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader"></i> Creating...';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch('<?= $base_url ?>/reseller/users/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                alert('User created successfully!');
                window.location.href = '<?= $base_url ?>/reseller/users';
            } else {
                alert(result.error || 'Failed to create user');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="user-plus"></i> Create User';
                lucide.createIcons();
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="user-plus"></i> Create User';
            lucide.createIcons();
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>