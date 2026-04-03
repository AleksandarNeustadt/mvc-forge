<?php

declare(strict_types=1);

use App\Core\config\Env;
use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;

$appPath = dirname(__DIR__);

if (!in_array('--force', $_SERVER['argv'] ?? [], true)) {
    fwrite(STDERR, "Refusing to reset database without --force.\n");
    exit(1);
}

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

if (!getenv('APP_ENV_FILE')) {
    putenv('APP_ENV_FILE=.env.testing');
    $_ENV['APP_ENV_FILE'] = '.env.testing';
}

require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

$databaseName = (string) Env::get('DB_DATABASE', '');
if ($databaseName === '' || !str_ends_with($databaseName, '_test')) {
    fwrite(STDERR, "Refusing to reset non-test database: {$databaseName}\n");
    exit(1);
}

$tables = DatabaseBuilder::getTables();
if ($tables === []) {
    echo "TEST_DB_ALREADY_EMPTY\n";
    exit(0);
}

Database::execute('SET FOREIGN_KEY_CHECKS = 0');
foreach ($tables as $table) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        continue;
    }

    Database::execute("DROP TABLE IF EXISTS `{$table}`");
    echo "DROPPED {$table}\n";
}
Database::execute('SET FOREIGN_KEY_CHECKS = 1');

echo "TEST_DB_RESET_OK\n";
