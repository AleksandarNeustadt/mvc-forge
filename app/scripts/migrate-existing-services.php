<?php
/**
 * Migrate Existing Services to ip_services Table
 * 
 * This script migrates existing services from ip_tracking to ip_services table.
 * This ensures we don't lose existing service data.
 * 
 * Usage: php scripts/migrate-existing-services.php
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

echo "🔄 Migrate Existing Services Script\n";
echo "===================================\n\n";

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
        exit(1);
    }
    
    // Get distinct IPs with services from ip_tracking
    $sql = "SELECT DISTINCT ip_address, known_service 
            FROM ip_tracking 
            WHERE known_service IS NOT NULL 
            AND known_service != ''
            AND ip_address != '0.0.0.0'
            AND ip_address NOT LIKE '127.%'
            AND ip_address NOT LIKE '192.168.%'
            AND ip_address NOT LIKE '10.%'
            AND ip_address NOT LIKE '172.%'";
    
    $existingServices = Database::select($sql);
    
    if (empty($existingServices)) {
        echo "ℹ️  No existing services found in ip_tracking table.\n";
        echo "💡 Services will be detected when processing IPs.\n";
        exit(0);
    }
    
    echo "📊 Found " . count($existingServices) . " IP addresses with existing services\n\n";
    
    $migrated = 0;
    $skipped = 0;
    $updated = 0;
    
    foreach ($existingServices as $row) {
        $ipAddress = $row['ip_address'];
        $service = $row['known_service'];
        
        // Check if already exists in ip_services
        $existing = Database::table('ip_services')
            ->where('ip_address', $ipAddress)
            ->first();
        
        if ($existing) {
            // Update if different
            if (($existing['known_service'] ?? '') !== $service) {
                Database::table('ip_services')
                    ->where('ip_address', $ipAddress)
                    ->update([
                        'known_service' => $service,
                        'last_detected_at' => date('Y-m-d H:i:s'),
                    ]);
                $updated++;
                echo "  ✅ Updated: {$ipAddress} → {$service}\n";
            } else {
                $skipped++;
            }
        } else {
            // Insert new
            Database::table('ip_services')->insert([
                'ip_address' => $ipAddress,
                'known_service' => $service,
                'detection_method' => 'migrated',
                'detection_count' => 1,
                'first_detected_at' => date('Y-m-d H:i:s'),
                'last_detected_at' => date('Y-m-d H:i:s'),
            ]);
            $migrated++;
            echo "  ✅ Migrated: {$ipAddress} → {$service}\n";
        }
    }
    
    echo "\n";
    echo "================================\n";
    echo "📊 Summary:\n";
    echo "  Migrated: {$migrated} new services\n";
    echo "  Updated: {$updated} existing services\n";
    echo "  Skipped: {$skipped} (already up to date)\n";
    echo "\n✅ Migration completed!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

