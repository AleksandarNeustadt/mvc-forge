<?php

namespace App\Core\database\migrations;


use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Migration: Create API Tokens Table
 * 
 * Migration 029
 * 
 * Creates api_tokens table for API authentication
 */

class Migration_029_CreateApiTokensTable
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        $driver = Database::getDriver();
        
        // Check if table already exists
        $tables = DatabaseBuilder::getTables();
        if (in_array('api_tokens', $tables)) {
            echo "⚠️  Table 'api_tokens' already exists. Skipping...\n";
            return;
        }
        
        if ($driver === 'mysql') {
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
        } elseif ($driver === 'pgsql') {
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
        }
        
        echo "✅ Table 'api_tokens' created successfully!\n";
    }
    
    public function down(): void
    {
        Database::execute("DROP TABLE IF EXISTS api_tokens");
        echo "✅ Table 'api_tokens' dropped successfully!\n";
    }
}

// If run directly, execute migration
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    if (!function_exists('ap_bootstrap_cli_application')) {
        require_once __DIR__ . '/../../../bootstrap/app.php';
        \ap_bootstrap_cli_application(dirname(__DIR__, 3));
    }
    
    // Skip routing, just load database
    if (!class_exists('DatabaseBuilder')) {
        require_once __DIR__ . '/../Database.php';
        require_once __DIR__ . '/../DatabaseBuilder.php';
    }
    
    echo "📋 Creating api_tokens table...\n\n";
    
    $migration = new Migration_029_CreateApiTokensTable();
    $migration->up();
    
    echo "\n✅ Migration completed!\n";
}


if (!\class_exists('Migration_029_CreateApiTokensTable', false) && !\interface_exists('Migration_029_CreateApiTokensTable', false) && !\trait_exists('Migration_029_CreateApiTokensTable', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_029_CreateApiTokensTable', 'Migration_029_CreateApiTokensTable');
}
