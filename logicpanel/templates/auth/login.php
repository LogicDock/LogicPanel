<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LogicPanel</title>

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

            <div class="login-footer">
                <p class="login-footer-text">&copy; <?= date('Y') ?> LogicPanel. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>