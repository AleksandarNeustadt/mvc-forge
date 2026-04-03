<?php
/**
 * Quick deploy check — open https://your-domain/ping.php then delete this file.
 * Does not print secrets.
 */
require_once __DIR__ . '/bootstrap_paths.php';
header('Content-Type: text/plain; charset=utf-8');

$here = __DIR__;
$defaultApp = dirname($here) . '/app';
$app = ap_resolve_app_path();
$override = trim((string) (
    $_SERVER['APP_ROOT_OVERRIDE'] ?? $_ENV['APP_ROOT_OVERRIDE'] ?? (getenv('APP_ROOT_OVERRIDE') ?: '')
));

echo "PHP " . PHP_VERSION . "\n";
echo "public_html: {$here}\n";
echo "default app path (../app): {$defaultApp}\n";
echo "APP_ROOT_OVERRIDE: " . ($override !== '' ? $override : '(not set)') . "\n";
echo "resolved app path: {$app}\n";
echo "app exists: " . (is_dir($app) ? "yes" : "NO") . "\n";
echo "Env.php: " . (is_file($app . '/core/config/Env.php') ? "ok" : "MISSING") . "\n";
echo ".env readable: " . (is_readable($app . '/.env') ? "yes" : "NO") . "\n";
$logs = $app . '/storage/logs';
echo "storage/logs writable: " . (is_dir($logs) && is_writable($logs) ? "yes" : (is_dir($logs) ? "no" : "dir missing")) . "\n";

