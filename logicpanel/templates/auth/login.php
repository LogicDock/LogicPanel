<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LogicPanel</title>
    <link rel="icon" type="image/x-icon" href="<?= $base_url ?? '' ?>/favicon.ico">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #e3e6e8;
            border-radius: 6px;
            padding: 35px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-title {
            font-size: 22px;
            font-weight: 700;
            color: #333333;
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13px;
            color: #3C873A;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #555555;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            font-size: 14px;
            font-family: inherit;
            color: #333333;
            background: #f8f9fa;
            border: 1px solid #e3e6e8;
            border-radius: 4px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3C873A;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(60, 135, 58, 0.1);
        }

        .form-control::placeholder {
            color: #999999;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: #3C873A;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.15s ease;
            margin-top: 5px;
        }

        .btn-login:hover {
            background: #2D6A2E;
        }

        .alert {
            padding: 12px 14px;
            font-size: 13px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e3e6e8;
        }

        .login-footer-text {
            font-size: 12px;
            color: #999999;
        }

        /* 2FA Styles */
        .twofa-info {
            text-align: center;
            margin-bottom: 25px;
        }

        .twofa-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3C873A 0%, #2D6A2E 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }

        .twofa-icon svg {
            color: white;
        }

        .twofa-info h3 {
            font-size: 16px;
            color: #333333;
            margin-bottom: 6px;
        }

        .twofa-info p {
            font-size: 13px;
            color: #666666;
        }

        .twofa-input {
            text-align: center;
            font-size: 24px !important;
            letter-spacing: 8px;
            font-family: monospace;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #3C873A;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">LogicPanel</h1>
                <p class="login-subtitle">Node.js Container Management</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($require_2fa)): ?>
                <!-- 2FA Code Input Step -->
                <form method="POST" action="">
                    <input type="hidden" name="pending_user_id" value="<?= htmlspecialchars($pending_user_id ?? '') ?>">

                    <div class="twofa-info">
                        <div class="twofa-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </div>
                        <h3>Two-Factor Authentication</h3>
                        <p>Enter the 6-digit code from your authenticator app</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Authentication Code</label>
                        <input type="text" name="two_factor_code" class="form-control twofa-input" placeholder="000000"
                            maxlength="6" pattern="[0-9]{6}" required autofocus autocomplete="off">
                    </div>

                    <button type="submit" class="btn-login">Verify</button>

                    <a href="<?= $base_url ?? '' ?>/login" class="back-link">← Back to login</a>
                </form>
            <?php else: ?>
                <!-- Normal Login Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required
                            autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn-login">Sign In</button>
                </form>
            <?php endif; ?>

            <div class="login-footer">
                <p class="login-footer-text">&copy; <?= date('Y') ?> LogicPanel. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>