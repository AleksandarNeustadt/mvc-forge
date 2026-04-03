<?php
/**
 * Background Job: Populate IP Geo Data
 * 
 * This script populates geo data for IP addresses in ip_tracking table
 * that don't have country information yet.
 * 
 * Usage: php scripts/populate-ip-geo-data.php [--limit=100] [--delay=1]
 * 
 * Options:
 *   --limit=N    Process N IPs at a time (default: 100)
 *   --delay=N    Delay between API calls in seconds (default: 1, to respect rate limits)
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

// Parse command line arguments
$limit = 100;
$delay = 1;

foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    } elseif (strpos($arg, '--delay=') === 0) {
        $delay = (float)substr($arg, 8);
    }
}

echo "🌍 IP Geo Data Population Script\n";
echo "================================\n\n";
echo "Limit: {$limit} IPs per run\n";
echo "Delay: {$delay} seconds between API calls\n\n";

try {
    // Get IPs that need geo data (no country_code or country_name)
    // Use raw SQL for complex WHERE conditions
    $sql = "SELECT DISTINCT ip_address 
            FROM ip_tracking 
            WHERE (country_code IS NULL OR country_name IS NULL)
            AND ip_address != '0.0.0.0'
            AND ip_address NOT LIKE '127.%'
            AND ip_address NOT LIKE '192.168.%'
            AND ip_address NOT LIKE '10.%'
            AND ip_address NOT LIKE '172.%'
            LIMIT ?";
    
    $ipsToProcess = Database::select($sql, [$limit]);
    
    if (empty($ipsToProcess)) {
        echo "✅ No IPs need geo data. All done!\n";
        exit(0);
    }
    
    $total = count($ipsToProcess);
    echo "📊 Found {$total} IPs to process\n\n";
    
    $processed = 0;
    $updated = 0;
    $errors = 0;
    
    foreach ($ipsToProcess as $row) {
        $ipAddress = $row['ip_address'];
        $processed++;
        
        echo "[{$processed}/{$total}] Processing {$ipAddress}... ";
        
        try {
            // Get geo data (will use cache if available, or fetch from API)
            $geoData = IpGeoCache::getGeoData($ipAddress);
            
            if (!empty($geoData)) {
                // Update all records with this IP address
                $updateData = [
                    'country_code' => $geoData['country_code'],
                    'country_name' => $geoData['country_name'],
                ];
                
                // Add known_service if available
                if (!empty($geoData['known_service'])) {
                    // Check if column exists
                    try {
                        $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
                        if (!empty($columns)) {
                            $updateData['known_service'] = $geoData['known_service'];
                        }
                    } catch (Exception $e) {
                        // Column doesn't exist, skip it
                    }
                }
                
                // Update records that need geo data
                $updateSql = "UPDATE ip_tracking 
                              SET country_code = ?, country_name = ?";
                $updateParams = [$geoData['country_code'], $geoData['country_name']];
                
                if (!empty($geoData['known_service'])) {
                    // Check if column exists
                    try {
                        $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
                        if (!empty($columns)) {
                            $updateSql .= ", known_service = ?";
                            $updateParams[] = $geoData['known_service'];
                        }
                    } catch (Exception $e) {
                        // Column doesn't exist, skip it
                    }
                }
                
                $updateSql .= " WHERE ip_address = ? AND (country_code IS NULL OR country_name IS NULL)";
                $updateParams[] = $ipAddress;
                
                $affected = Database::execute($updateSql, $updateParams);
                
                if ($affected > 0) {
                    $updated += $affected;
                    echo "✅ Updated {$affected} record(s) - {$geoData['country_name']} ({$geoData['country_code']})";
                    if (!empty($geoData['known_service'])) {
                        echo " - Service: {$geoData['known_service']}";
                    }
                    echo "\n";
                } else {
                    echo "ℹ️  Already has geo data\n";
                }
            } else {
                echo "⚠️  No geo data available\n";
            }
            
            // Delay to respect API rate limits (ip-api.com allows 45 requests/minute)
            if ($delay > 0 && $processed < $total) {
                usleep($delay * 1000000); // Convert to microseconds
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "================================\n";
    echo "📊 Summary:\n";
    echo "  Processed: {$processed} IPs\n";
    echo "  Updated: {$updated} records\n";
    echo "  Errors: {$errors}\n";
    echo "\n";
    
    if ($processed < $total) {
        echo "💡 Tip: Run this script again to process more IPs\n";
    } else {
        echo "✅ All IPs processed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

