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
 * Migration: Add Email Verification
 * 
 * Migration 017
 * 
 * Adds:
 * - email_verification_token column to users table
 * - email_verification_expires_at column to users table
 */

class Migration_017_AddEmailVerification
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Add email_verification_token column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'email_verification_token'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at");
            }
            
            // Add email_verification_expires_at column
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'email_verification_expires_at'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE users ADD COLUMN email_verification_expires_at INT NULL AFTER email_verification_token");
            }
            
            // Add index on email_verification_token for faster lookups
            $indexes = Database::select("SHOW INDEX FROM users WHERE Key_name = 'idx_email_verification_token'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_email_verification_token ON users(email_verification_token)");
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) NULL");
            Database::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_expires_at INTEGER NULL");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_email_verification_token ON users(email_verification_token)");
        }
    }

    public function down(): void
    {
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Drop indexes
            $indexes = Database::select("SHOW INDEX FROM users WHERE Key_name = 'idx_email_verification_token'");
            if (!empty($indexes)) {
                Database::execute("DROP INDEX idx_email_verification_token ON users");
            }
            
            // Drop columns
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'email_verification_token'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN email_verification_token");
            }
            
            $columns = Database::select("SHOW COLUMNS FROM users LIKE 'email_verification_expires_at'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE users DROP COLUMN email_verification_expires_at");
            }
        } elseif ($driver === 'pgsql') {
            Database::execute("DROP INDEX IF EXISTS idx_email_verification_token");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS email_verification_token");
            Database::execute("ALTER TABLE users DROP COLUMN IF EXISTS email_verification_expires_at");
        }
    }
}


if (!\class_exists('Migration_017_AddEmailVerification', false) && !\interface_exists('Migration_017_AddEmailVerification', false) && !\trait_exists('Migration_017_AddEmailVerification', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_017_AddEmailVerification', 'Migration_017_AddEmailVerification');
}
