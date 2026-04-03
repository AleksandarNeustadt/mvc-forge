<?php

declare(strict_types=1);

const TEST_DB_SUFFIX = '_test';

$appPath = dirname(__DIR__);
$sourceEnv = loadEnvFile($appPath . '/.env');
$targetEnv = loadEnvFile($appPath . '/.env.testing');

$sourceDatabase = (string) ($sourceEnv['DB_DATABASE'] ?? '');
$targetDatabase = (string) ($targetEnv['DB_DATABASE'] ?? '');

if ($sourceDatabase === '' || $targetDatabase === '') {
    fwrite(STDERR, "Missing DB_DATABASE in .env or .env.testing.\n");
    exit(1);
}

if (str_ends_with($sourceDatabase, TEST_DB_SUFFIX)) {
    fwrite(STDERR, "Refusing to use a *_test source database: {$sourceDatabase}\n");
    exit(1);
}

if (!str_ends_with($targetDatabase, TEST_DB_SUFFIX)) {
    fwrite(STDERR, "Refusing to sync schema into non-test database: {$targetDatabase}\n");
    exit(1);
}

if ($sourceDatabase === $targetDatabase) {
    fwrite(STDERR, "Source and target databases must differ.\n");
    exit(1);
}

$sourcePdo = connectToDatabaseServer($sourceEnv, $sourceDatabase);
$targetServerPdo = connectToDatabaseServer($targetEnv);

$quotedTargetDatabase = quoteIdentifier($targetDatabase);
$targetServerPdo->exec(
    "CREATE DATABASE IF NOT EXISTS {$quotedTargetDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);

$targetPdo = connectToDatabaseServer($targetEnv, $targetDatabase);
$sourceTables = fetchSourceTables($sourcePdo);

$targetPdo->exec('SET FOREIGN_KEY_CHECKS=0');

try {
    $existingTargetTables = fetchSourceTables($targetPdo);
    foreach ($existingTargetTables as $tableName) {
        $targetPdo->exec('DROP TABLE IF EXISTS ' . quoteIdentifier($tableName));
    }

    foreach ($sourceTables as $tableName) {
        $statement = $sourcePdo->query('SHOW CREATE TABLE ' . quoteIdentifier($tableName));
        $createRow = $statement !== false ? $statement->fetch(PDO::FETCH_ASSOC) : false;
        $createSql = is_array($createRow) ? (string) ($createRow['Create Table'] ?? '') : '';

        if ($createSql === '') {
            fwrite(STDERR, "Unable to read CREATE TABLE statement for {$tableName}.\n");
            exit(1);
        }

        $targetPdo->exec($createSql);
    }
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

echo "Synced schema from {$sourceDatabase} to {$targetDatabase}.\n";
echo 'Tables cloned: ' . count($sourceTables) . "\n";

/**
 * @return array<string, string>
 */
function loadEnvFile(string $path): array
{
    if (!is_file($path)) {
        fwrite(STDERR, "Env file not found: {$path}\n");
        exit(1);
    }

    $env = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines === false ? [] : $lines as $line) {
        $trimmedLine = trim($line);

        if ($trimmedLine === '' || str_starts_with($trimmedLine, '#') || !str_contains($trimmedLine, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmedLine, 2);
        $env[trim($key)] = trim($value);
    }

    return $env;
}

/**
 * @param array<string, string> $env
 */
function connectToDatabaseServer(array $env, ?string $database = null): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $env['DB_HOST'] ?? '127.0.0.1',
        $env['DB_PORT'] ?? '3306',
        $env['DB_CHARSET'] ?? 'utf8mb4'
    );

    if ($database !== null) {
        $dsn .= ';dbname=' . $database;
    }

    return new PDO(
        $dsn,
        (string) ($env['DB_USERNAME'] ?? ''),
        (string) ($env['DB_PASSWORD'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

/**
 * @return list<string>
 */
function fetchSourceTables(PDO $pdo): array
{
    $tables = [];

    foreach ($pdo->query('SHOW FULL TABLES') ?: [] as $row) {
        $rowValues = array_values($row);
        $tableName = (string) ($rowValues[0] ?? '');
        $tableType = strtoupper((string) ($rowValues[1] ?? ''));

        if ($tableName !== '' && $tableType === 'BASE TABLE') {
            $tables[] = $tableName;
        }
    }

    return $tables;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}
