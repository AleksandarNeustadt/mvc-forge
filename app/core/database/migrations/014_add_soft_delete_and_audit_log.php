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
 * Migration: Add Soft Delete and Audit Log
 * 
 * Migration 014
 * 
 * Adds:
 * - deleted_at column to users table (soft delete)
 * - audit_logs table for tracking changes
 */

class Migration_014_AddSoftDeleteAndAuditLog
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        // Add deleted_at column to users table
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Check if column already exists
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'deleted_at'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN deleted_at INT NULL AFTER status");
            }
            
            // Check if index already exists
            $indexes = Database::select("SHOW INDEX FROM users WHERE Key_name = 'idx_deleted_at'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_deleted_at ON users(deleted_at)");
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at INTEGER NULL");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_deleted_at ON users(deleted_at)");
        }
        
        // Create audit_logs table
        if ($driver === 'mysql') {
            // Check if table exists
            $tables = DatabaseBuilder::getTables();
            if (!in_array('audit_logs', $tables)) {
                Database::execute("
                    CREATE TABLE audit_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NULL,
                        action VARCHAR(100) NOT NULL,
                        model VARCHAR(100) NOT NULL,
                        model_id INT NULL,
                        old_values JSON NULL,
                        new_values JSON NULL,
                        ip VARCHAR(45) NULL,
                        user_agent TEXT NULL,
                        created_at INT NOT NULL,
                        INDEX idx_user_id (user_id),
                        INDEX idx_action (action),
                        INDEX idx_model (model, model_id),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } elseif ($driver === 'pgsql') {
            Database::execute("
                CREATE TABLE IF NOT EXISTS audit_logs (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NULL,
                    action VARCHAR(100) NOT NULL,
                    model VARCHAR(100) NOT NULL,
                    model_id INTEGER NULL,
                    old_values JSONB NULL,
                    new_values JSONB NULL,
                    ip VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    created_at INTEGER NOT NULL
                )
            ");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_user_id ON audit_logs(user_id)");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_action ON audit_logs(action)");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_model ON audit_logs(model, model_id)");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_created_at ON audit_logs(created_at)");
        }
    }
    
    public function down(): void
    {
        $driver = Database::getDriver();
        
        // Drop audit_logs table
        Database::execute("DROP TABLE IF EXISTS audit_logs");
        
        // Remove deleted_at column from users
        if ($driver === 'mysql') {
            // Check if column exists before dropping
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'deleted_at'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN deleted_at");
            }
        } elseif ($driver === 'pgsql') {
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS deleted_at");
        }
    }
}


if (!\class_exists('Migration_014_AddSoftDeleteAndAuditLog', false) && !\interface_exists('Migration_014_AddSoftDeleteAndAuditLog', false) && !\trait_exists('Migration_014_AddSoftDeleteAndAuditLog', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_014_AddSoftDeleteAndAuditLog', 'Migration_014_AddSoftDeleteAndAuditLog');
}
