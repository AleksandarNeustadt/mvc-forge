<?php

namespace App\Core\database\migrations;


use App\Core\database\Database;use BadMethodCallException;
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
 * Migration: Add Password Reset and Account Lockout
 * 
 * Migration 015
 * 
 * Adds:
 * - password_reset_token column to users table
 * - password_reset_expires_at column to users table
 * - failed_login_attempts column to users table
 * - locked_until column to users table
 */

class Migration_015_AddPasswordResetAndAccountLockout
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Add password_reset_token column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL AFTER password_hash");
            }
            
            // Add password_reset_expires_at column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'password_reset_expires_at'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN password_reset_expires_at INT NULL AFTER password_reset_token");
            }
            
            // Add failed_login_attempts column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'failed_login_attempts'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER last_login_ip");
            }
            
            // Add locked_until column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'locked_until'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN locked_until INT NULL AFTER failed_login_attempts");
            }
            
            // Add index on password_reset_token for faster lookups
            $indexes = Database::select("SHOW INDEX FROM users WHERE Key_name = 'idx_password_reset_token'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_password_reset_token ON users(password_reset_token)");
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(64) NULL");
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires_at INTEGER NULL");
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INTEGER DEFAULT 0");
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until INTEGER NULL");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_password_reset_token ON users(password_reset_token)");
        }
    }

    public function down(): void
    {
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Drop indexes
            $indexes = Database::select("SHOW INDEX FROM users WHERE Key_name = 'idx_password_reset_token'");
            if (!empty($indexes)) {
                Database::execute("DROP INDEX idx_password_reset_token ON users");
            }
            
            // Drop columns
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'password_reset_token'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN password_reset_token");
            }
            
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'password_reset_expires_at'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN password_reset_expires_at");
            }
            
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'failed_login_attempts'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN failed_login_attempts");
            }
            
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'locked_until'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN locked_until");
            }
        } elseif ($driver === 'pgsql') {
            Database::execute("DROP INDEX IF EXISTS idx_password_reset_token");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS password_reset_token");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS password_reset_expires_at");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS failed_login_attempts");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS locked_until");
        }
    }
}


if (!\class_exists('Migration_015_AddPasswordResetAndAccountLockout', false) && !\interface_exists('Migration_015_AddPasswordResetAndAccountLockout', false) && !\trait_exists('Migration_015_AddPasswordResetAndAccountLockout', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_015_AddPasswordResetAndAccountLockout', 'Migration_015_AddPasswordResetAndAccountLockout');
}
