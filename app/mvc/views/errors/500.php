<!DOCTYPE html>
<html lang="<?= $lang ?? 'sr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $statusCode ?? 500 ?> - <?= $title ?? 'Internal Server Error' ?> | <?= Env::get('BRAND_NAME', 'aleksandar.pro') ?></title>
    <?php $v = '1.0.' . time(); ?>
    <link rel="stylesheet" href="/dist/app.css?v=<?= $v ?>">
</head>
<body class="bg-slate-950 text-white antialiased min-h-screen flex flex-col items-center justify-center">
    <div class="text-center px-4 max-w-2xl">
        <h1 class="text-9xl font-black text-red-500 mb-4">500</h1>
        <h2 class="text-4xl font-bold mb-4"><?= htmlspecialchars($title ?? 'Internal Server Error') ?></h2>
        <p class="text-xl text-slate-400 mb-8"><?= htmlspecialchars($message ?? 'Something went wrong on our end. Please try again later.') ?></p>
        
        <?php if (isset($exception) && $exception !== null): ?>
            <div class="mt-8 p-6 bg-slate-900/50 rounded-lg text-left">
                <h3 class="text-lg font-semibold mb-4 text-red-400">Debug Information:</h3>
                <div class="space-y-2 text-sm font-mono">
                    <p><strong class="text-slate-300">File:</strong> <span class="text-slate-400"><?= htmlspecialchars($exception['file'] ?? 'unknown') ?></span></p>
                    <p><strong class="text-slate-300">Line:</strong> <span class="text-slate-400"><?= htmlspecialchars($exception['line'] ?? 'unknown') ?></span></p>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-slate-300 hover:text-white mb-2">Stack Trace</summary>
                        <pre class="mt-2 p-4 bg-slate-950 rounded text-xs overflow-auto max-h-96 text-slate-400"><?= htmlspecialchars($exception['trace'] ?? 'No trace available') ?></pre>
                    </details>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-8">
            <a href="/<?= $lang ?? 'sr' ?>" class="inline-block px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white rounded-lg font-medium transition-colors">
                Go Home
            </a>
        </div>
    </div>
</body>
</html>

