<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - LogicPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="w-24 h-24 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="alert-triangle" class="w-12 h-12 text-red-600 dark:text-red-400"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-2">Something went wrong</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            <?= htmlspecialchars($message ?? 'An unexpected error occurred. Please try again.') ?>
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= $base_url ?? '/logicpanel/public' ?>/"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <i data-lucide="home" class="w-5 h-5"></i>
                Back to Dashboard
            </a>
            <button onclick="location.reload()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-colors">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                Try Again
            </button>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>

</html>