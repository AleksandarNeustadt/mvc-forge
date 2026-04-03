<?php

$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_ENV_FILE'] = '.env.testing';
putenv('APP_ENV=' . $_ENV['APP_ENV']);
putenv('APP_ENV_FILE=' . $_ENV['APP_ENV_FILE']);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/sr';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

require_once __DIR__ . '/../bootstrap/app.php';

$appPath = dirname(__DIR__);
ap_require_composer_autoload($appPath);
ap_register_legacy_namespace_aliases($appPath);
ap_load_environment($appPath);
