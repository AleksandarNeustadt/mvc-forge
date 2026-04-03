<?php
/**
 * Quick script to create api_tokens table
 * Run: php create-api-tokens-table.php
 */

require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';

echo "📋 Creating api_tokens table...\n\n";

$driver = Database::getDriver();
$tables = DatabaseBuilder::getTables();

if (in_array('api_tokens', $tables)) {
    echo "⚠️  Table 'api_tokens' already exists. Skipping...\n";
    exit(0);
}

if ($driver === 'mysql') {
    try {
        Database::execute("
            CREATE TABLE api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                name VARCHAR(255) NULL,
                last_used_at INT NULL,
                expires_at INT NULL,
                created_at INT NOT NULL,
                updated_at INT NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_token (token),
                INDEX idx_expires_at (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✅ Table 'api_tokens' created successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} elseif ($driver === 'pgsql') {
    try {
        Database::execute("
            CREATE TABLE api_tokens (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                name VARCHAR(255) NULL,
                last_used_at INTEGER NULL,
                expires_at INTEGER NULL,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        Database::execute("CREATE INDEX idx_user_id ON api_tokens(user_id)");
        Database::execute("CREATE INDEX idx_token ON api_tokens(token)");
        Database::execute("CREATE INDEX idx_expires_at ON api_tokens(expires_at)");
        echo "✅ Table 'api_tokens' created successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "❌ Unsupported database driver: {$driver}\n";
    exit(1);
}

echo "\n✅ Migration completed!\n";

