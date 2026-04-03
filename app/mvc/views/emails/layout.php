<!DOCTYPE html>
<html lang="<?= $lang ?? 'sr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?? 'Email' ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #0f172a;
        }
        .email-container {
            background-color: #1e293b;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #334155;
        }
        .email-header h1 {
            color: #eab308;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .email-content {
            color: #e2e8f0;
            font-size: 16px;
        }
        .email-content h2 {
            color: #fbbf24;
            margin-top: 0;
        }
        .email-content p {
            margin: 16px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #eab308;
            color: #0f172a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #fbbf24;
        }
        .email-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #334155;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }
        .email-footer a {
            color: #eab308;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1><?= Env::get('BRAND_NAME', 'aleksandar.pro') ?></h1>
        </div>
        <div class="email-content">
            <?= $content ?? '' ?>
        </div>
        <div class="email-footer">
            <p>&copy; <?= date('Y') ?> <?= Env::get('BRAND_NAME', 'aleksandar.pro') ?>. All rights reserved.</p>
            <p>
                <a href="<?= Env::get('APP_URL', 'https://aleksandar.pro') ?>">Visit our website</a>
            </p>
        </div>
    </div>
</body>
</html>

