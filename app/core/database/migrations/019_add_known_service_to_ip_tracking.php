<?php

namespace App\Core\database\migrations;


use App\Core\config\Env;
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
 * Migration: Add Known Service to IP Tracking
 * 
 * Migration 019
 * 
 * Adds:
 * - known_service column to ip_tracking table (for identifying Google, Cloudflare, AWS, etc.)
 */

class Migration_019_AddKnownServiceToIpTracking
{
    public function up(): void
    {
        // Load DatabaseBuilder if not loaded
        if (!class_exists('DatabaseBuilder')) {
            require_once __DIR__ . '/../DatabaseBuilder.php';
        }
        
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Check if column already exists
            $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE ip_tracking ADD COLUMN known_service VARCHAR(100) NULL AFTER country_name");
                
                // Add index for filtering
                $indexes = Database::select("SHOW INDEX FROM ip_tracking WHERE Key_name = 'idx_known_service'");
                if (empty($indexes)) {
                    Database::execute("CREATE INDEX idx_known_service ON ip_tracking(known_service)");
                }
                
                echo "✅ Added 'known_service' column to ip_tracking table\n";
            } else {
                echo "⚠️  Column 'known_service' already exists. Skipping...\n";
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            Database::execute("ALTER TABLE ip_tracking ADD COLUMN IF NOT EXISTS known_service VARCHAR(100) NULL");
            Database::execute("CREATE INDEX IF NOT EXISTS idx_known_service ON ip_tracking(known_service)");
            echo "✅ Added 'known_service' column to ip_tracking table\n";
        }
    }
    
    public function down(): void
    {
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            // Drop index
            $indexes = Database::select("SHOW INDEX FROM ip_tracking WHERE Key_name = 'idx_known_service'");
            if (!empty($indexes)) {
                Database::execute("DROP INDEX idx_known_service ON ip_tracking");
            }
            
            // Drop column
            $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
            if (!empty($columns)) {
                Database::execute("ALTER TABLE ip_tracking DROP COLUMN known_service");
            }
        } elseif ($driver === 'pgsql') {
            Database::execute("DROP INDEX IF EXISTS idx_known_service");
            Database::execute("ALTER TABLE ip_tracking DROP COLUMN IF EXISTS known_service");
        }
    }
}

// Auto-run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../../../core/config/Env.php';
    Env::load(__DIR__ . '/../../../.env');
    
    require_once __DIR__ . '/../Database.php';
    require_once __DIR__ . '/../DatabaseBuilder.php';
    
    echo "🚀 Running Migration 019: Add Known Service to IP Tracking\n\n";
    
    try {
        $migration = new Migration_019_AddKnownServiceToIpTracking();
        $migration->up();
        echo "\n✅ Migration 019 completed successfully!\n";
    } catch (Exception $e) {
        echo "\n❌ Migration 019 failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}


if (!\class_exists('Migration_019_AddKnownServiceToIpTracking', false) && !\interface_exists('Migration_019_AddKnownServiceToIpTracking', false) && !\trait_exists('Migration_019_AddKnownServiceToIpTracking', false)) {
    \class_alias(__NAMESPACE__ . '\\Migration_019_AddKnownServiceToIpTracking', 'Migration_019_AddKnownServiceToIpTracking');
}
