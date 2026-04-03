<?php
/**
 * Sync IP Services to Tracking Table
 * 
 * This script updates all ip_tracking records to use services from ip_services table.
 * This ensures consistency - all records for the same IP will have the same service.
 * 
 * Usage: php scripts/sync-ip-services-to-tracking.php [--dry-run]
 * 
 * Options:
 *   --dry-run    Show what would be updated without actually updating
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

// Parse command line arguments
$dryRun = false;
foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    }
}

echo "🔄 IP Services Sync Script\n";
echo "==========================\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n\n";
}

try {
    // Check if ip_services table exists
    $tables = Database::select("SHOW TABLES LIKE 'ip_services'");
    if (empty($tables)) {
        echo "❌ Table 'ip_services' does not exist!\n";
        echo "💡 Run: php core/database/migrations/020_create_ip_services_table.php\n";
        exit(1);
    }
    
    // Check if known_service column exists in ip_tracking
    $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
    if (empty($columns)) {
        echo "❌ Column 'known_service' does not exist in ip_tracking table!\n";
        echo "💡 Run: php core/database/migrations/019_add_known_service_to_ip_tracking.php\n";
        exit(1);
    }
    
    // Get all IP addresses with services
    $ipServices = Database::select(
        "SELECT ip_address, known_service FROM ip_services WHERE known_service IS NOT NULL AND known_service != ''"
    );
    
    if (empty($ipServices)) {
        echo "ℹ️  No services found in ip_services table.\n";
        echo "💡 Run: php scripts/update-ip-services.php to detect services first.\n";
        exit(0);
    }
    
    echo "📊 Found " . count($ipServices) . " IP addresses with services\n\n";
    
    $totalUpdated = 0;
    $totalRecords = 0;
    
    foreach ($ipServices as $ipService) {
        $ipAddress = $ipService['ip_address'];
        $service = $ipService['known_service'];
        
        // Count records that need updating
        $countSql = "SELECT COUNT(*) as count FROM ip_tracking 
                     WHERE ip_address = ? 
                     AND (known_service IS NULL OR known_service = '' OR known_service != ?)";
        $countResult = Database::selectOne($countSql, [$ipAddress, $service]);
        $recordsToUpdate = $countResult['count'] ?? 0;
        
        if ($recordsToUpdate > 0) {
            $totalRecords += $recordsToUpdate;
            
            if ($dryRun) {
                echo "  [DRY RUN] Would update {$recordsToUpdate} records for IP {$ipAddress} → {$service}\n";
            } else {
                // Update all records for this IP
                $updated = Database::execute(
                    "UPDATE ip_tracking SET known_service = ? WHERE ip_address = ?",
                    [$service, $ipAddress]
                );
                
                if ($updated > 0) {
                    $totalUpdated += $updated;
                    echo "  ✅ Updated {$updated} records for IP {$ipAddress} → {$service}\n";
                }
            }
        }
    }
    
    echo "\n";
    echo "================================\n";
    echo "📊 Summary:\n";
    
    if ($dryRun) {
        echo "  Records that would be updated: {$totalRecords}\n";
        echo "\n💡 Run without --dry-run to apply changes\n";
    } else {
        echo "  Records updated: {$totalUpdated}\n";
        echo "\n✅ Sync completed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

