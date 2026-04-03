<?php
/**
 * Background Job: Update IP Services
 * 
 * This script updates all IP addresses in ip_tracking with services from ip_services table.
 * It also detects services for IPs that don't have one yet.
 * 
 * Usage: php scripts/update-ip-services.php [--limit=100] [--delay=0.5]
 * 
 * Options:
 *   --limit=N    Process N IPs at a time (default: 100)
 *   --delay=N    Delay between processing in seconds (default: 0.5)
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

// Parse command line arguments
$limit = 100;
$delay = 0.5;

foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    } elseif (strpos($arg, '--delay=') === 0) {
        $delay = (float)substr($arg, 8);
    }
}

echo "🔧 IP Services Update Script\n";
echo "============================\n\n";
echo "Limit: {$limit} IPs per run\n";
echo "Delay: {$delay} seconds between processing\n\n";

try {
    // First, migrate existing services from ip_tracking to ip_services
    echo "📊 Step 1: Migrating existing services...\n";
    
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
    $migrated = 0;
    
    if (!empty($existingServices)) {
        foreach ($existingServices as $row) {
            $ipAddress = $row['ip_address'];
            $service = $row['known_service'];
            
            // Check if already exists in ip_services
            $existing = Database::table('ip_services')
                ->where('ip_address', $ipAddress)
                ->first();
            
            if (!$existing) {
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
            }
        }
        echo "  ✅ Migrated {$migrated} existing services to ip_services table\n";
    } else {
        echo "  ℹ️  No existing services found in ip_tracking\n";
    }
    
    echo "\n";
    
    // Then, detect services for IPs that don't have one
    echo "📊 Step 2: Detecting services for IPs without service...\n\n";
    
    // Get all distinct IPs
    $allIps = Database::select(
        "SELECT DISTINCT ip_address 
         FROM ip_tracking 
         WHERE ip_address != '0.0.0.0'
         AND ip_address NOT LIKE '127.%'
         AND ip_address NOT LIKE '192.168.%'
         AND ip_address NOT LIKE '10.%'
         AND ip_address NOT LIKE '172.%'
         LIMIT ?",
        [$limit]
    );
    
    $stats = [
        'processed' => 0,
        'updated' => 0,
        'services_found' => 0,
    ];
    
    foreach ($allIps as $row) {
        $ipAddress = $row['ip_address'];
        $stats['processed']++;
        
        // Check if already in ip_services
        $existing = Database::table('ip_services')
            ->where('ip_address', $ipAddress)
            ->first();
        
        if ($existing && !empty($existing['known_service'])) {
            // Already has service, just update tracking records
            $updated = IpServiceMapper::updateTrackingRecords($ipAddress);
            if ($updated > 0) {
                $stats['updated'] += $updated;
            }
            continue;
        }
        
        // Try to detect service
        // Get user agent from most recent request for this IP
        $recentRequest = Database::table('ip_tracking')
            ->where('ip_address', $ipAddress)
            ->whereNotNull('user_agent')
            ->orderBy('created_at', 'DESC')
            ->first();
        
        $userAgent = $recentRequest['user_agent'] ?? null;
        
        // Get or detect service
        $service = IpServiceMapper::getService($ipAddress, $userAgent);
        
        if ($service) {
            $stats['services_found']++;
            echo "  ✅ Detected: {$ipAddress} → {$service}\n";
            // Update tracking records
            $updated = IpServiceMapper::updateTrackingRecords($ipAddress);
            $stats['updated'] += $updated;
        }
    }
    
    echo "\n";
    echo "================================\n";
    echo "📊 Summary:\n";
    echo "  Processed: {$stats['processed']} IPs\n";
    echo "  Services Found: {$stats['services_found']}\n";
    echo "  Records Updated: {$stats['updated']}\n";
    echo "\n";
    
    if ($stats['processed'] < $limit) {
        echo "✅ All IPs processed!\n";
    } else {
        echo "💡 Tip: Run this script again to process more IPs\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

