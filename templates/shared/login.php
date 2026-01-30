<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LogicPanel</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: #F2F2F2;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            text-align: center;
        }

        .logo {
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 700;
            color: #FF6C2C;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
        }

        .input-wrapper {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-wrapper:focus-within {
            border-color: #66afe9;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(102, 175, 233, 0.6);
        }

        .input-wrapper svg {
            width: 16px;
            height: 16px;
            color: #888;
        }

        input {
            border: none;
            width: 100%;
            font-size: 14px;
            outline: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #0087C1;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #00608a;
        }

        .reset-pass {
            margin-top: 15px;
            font-size: 13px;
            color: #333;
            font-weight: 600;
            display: block;
            text-decoration: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="logo">LogicPanel</div>

        <?php if (isset($error) && $error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= $base_url ?? '' ?>/login" method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i data-lucide="user"></i>
                    <input type="text" name="username" placeholder="username" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i data-lucide="lock"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit">Log in</button>
        </form>

        <a href="#" class="reset-pass">Reset Password</a>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>