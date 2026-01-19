<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - LogicPanel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="text-center">
        <div
            class="w-24 h-24 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="file-x" class="w-12 h-12 text-blue-600 dark:text-blue-400"></i>
        </div>
        <h1 class="text-6xl font-bold text-gray-800 dark:text-gray-200 mb-2">404</h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-2">
            <?= htmlspecialchars($message ?? 'Page not found') ?>
        </p>
        <p class="text-gray-500 dark:text-gray-500 mb-8">The page you're looking for doesn't exist or has been moved.
        </p>
        <a href="<?= $base_url ?? '' ?>/"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            <i data-lucide="home" class="w-5 h-5"></i>
            Back to Dashboard
        </a>
    </div>
    <script>lucide.createIcons();</script>
</body>

</html>