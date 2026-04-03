<!DOCTYPE html>
<html lang="<?= $lang ?? 'sr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $statusCode ?? 403 ?> - <?= $title ?? 'Forbidden' ?> | <?= Env::get('BRAND_NAME', 'aleksandar.pro') ?></title>
    <?php $v = '1.0.' . time(); ?>
    <link rel="stylesheet" href="/dist/app.css?v=<?= $v ?>">
</head>
<body class="bg-slate-950 text-white antialiased min-h-screen flex flex-col items-center justify-center">
    <div class="text-center px-4">
        <h1 class="text-9xl font-black text-yellow-500 mb-4">403</h1>
        <h2 class="text-4xl font-bold mb-4"><?= htmlspecialchars($title ?? 'Forbidden') ?></h2>
        <p class="text-xl text-slate-400 mb-8"><?= htmlspecialchars($message ?? 'You do not have permission to access this resource.') ?></p>
        <div class="space-x-4">
            <a href="/<?= $lang ?? 'sr' ?>" class="inline-block px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white rounded-lg font-medium transition-colors">
                Go Home
            </a>
            <a href="/<?= $lang ?? 'sr' ?>/login" class="inline-block px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors border border-slate-700">
                Login
            </a>
        </div>
    </div>
</body>
</html>

