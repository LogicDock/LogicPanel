<?php
$page_title = 'Create Account';
$current_page = 'accounts_create';
$sidebar_type = 'master';
ob_start();
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Create a New Account</div>
    </div>
    <div class="card-body">
        <form id="createAccountForm">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="user">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control">
                </div>
            </div>
            <style>
                .form-row {
                    display: flex;
                    gap: 10px;
                }

                .form-row .form-group {
                    flex: 1;
                }

                @media (max-width: 768px) {
                    .form-row {
                        flex-direction: column;
                        gap: 0;
                    }
                }
            </style>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="admin@example.com">
            </div>

            <div class="form-group"
                style="border-top:1px solid var(--border-color); padding-top:20px; margin-top:20px;">
                <h4 class="form-label" style="font-size:11px; text-transform:uppercase; color:var(--text-secondary);">
                    Package Selection</h4>
                <label class="form-label">Choose a Package</label>
                <select name="package" class="form-control" id="packageSelect">
                    <option value="" disabled selected>Loading packages...</option>
                </select>
            </div>

            <div class="mt-20">
                <button type="button" class="btn btn-primary" onclick="createAccount()">
                    <i data-lucide="check"></i> Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    async function createAccount() {
        const btn = document.querySelector('.btn-primary');
        const originalText = btn.innerHTML;

        // Gather data
        const form = document.getElementById('createAccountForm');
        const data = {
            username: form.username.value,
            password: form.password.value,
            email: form.email.value,
            package_name: form.package.value
        };

        if (!data.username || !data.password || !data.email) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner" style="vertical-align:middle;margin-right:8px"></span> Creating...`;
            if (window.lucide) lucide.createIcons();

            // Use token from global scope
            const token = window.apiToken || sessionStorage.getItem('token');
            if (!token) {
                showNotification('Authentication Error: Please login again', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            const res = await fetch('/public/api/master/accounts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (res.ok) {
                showNotification('Account created successfully!', 'success');
                setTimeout(() => window.location.href = 'list', 1500);
            } else {
                showNotification('Error: ' + (result.message || result.error || 'Unknown error'), 'error');
            }
        } catch (e) {
            console.error(e);
            showNotification('System Error: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    }

    // Fetch Packages on Load
    (async function fetchPackages() {
        const select = document.getElementById('packageSelect');
        try {
            const token = window.apiToken || sessionStorage.getItem('token');
            const res = await fetch('/public/api/master/packages', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const data = await res.json();

            select.innerHTML = '';

            if (data.packages && data.packages.length > 0) {
                data.packages.forEach(pkg => {
                    const option = document.createElement('option');
                    option.value = pkg.name; // sending name/id as value? backend expects?
                    // Assuming backend creates account by 'package' name or id. 
                    // create.php usually sent 'default', 'starter'. 
                    // Let's check AccountController::create logic later, but likely expects ID or Name.
                    // For now, let's assume 'name' is the key unless we find otherwise.
                    option.textContent = `${pkg.name} (${pkg.storage_limit === '0' || pkg.storage_limit === 0 ? 'Unlimited' : pkg.storage_limit + 'MB'} Disk)`;
                    option.value = pkg.name; // Keep consistency with previous hardcoded values
                    select.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.text = "No packages found";
                option.disabled = true;
                option.selected = true;
                select.appendChild(option);
            }
        } catch (e) {
            console.error(e);
            select.innerHTML = '<option disabled>Failed to load packages</option>';
        }
    })();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../shared/layouts/main.php';
?>