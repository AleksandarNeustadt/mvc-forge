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
 * Migration: Add Password History
 * 
 * Migration 016
 * 
 * Creates password_history table to prevent reuse of recent passwords
 */

class Migration_016_AddPasswordHistory
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        $driver = Database::getDriver();
        $tables = DatabaseBuilder::getTables();
        
        if (in_array('password_history', $tables)) {
            return; // Table already exists
        }
        
        if ($driver === 'mysql') {
            Database::execute("
                CREATE TABLE password_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at INT NOT NULL,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } elseif ($driver === 'pgsql') {
            Database::execute("
                CREATE TABLE password_history (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at INTEGER NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            Database::execute("CREATE INDEX idx_user_id ON password_history(user_id)");
            Database::execute("CREATE INDEX idx_created_at ON password_history(created_at)");
        }
    }

    public function down(): void
    {
        $driver = Database::getDriver();
        $tables = DatabaseBuilder::getTables();
        
        if (!in_array('password_history', $tables)) {
            return; // Table doesn't exist
        }
        
        Database::execute("DROP TABLE IF EXISTS password_history");
    }
}


if (!\class_exists('Migration_016_AddPasswordHistory', false) && !\interface_exists('Migration_016_AddPasswordHistory', false) && !\trait_exists('Migration_016_AddPasswordHistory', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_016_AddPasswordHistory', 'Migration_016_AddPasswordHistory');
}
