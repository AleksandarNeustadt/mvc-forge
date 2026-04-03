<?php
/**
 * Migration: Backfill IP Tracking Geolocation Data
 * 
 * Migration 032
 * 
 * Updates existing ip_tracking records that don't have country_code/country_name
 * by fetching geolocation data from API and updating the records
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

// Load services
require_once __DIR__ . '/../../services/IpGeoCache.php';
require_once __DIR__ . '/../../services/GeoLocation.php';

echo "📋 Migration 032: Backfilling IP tracking geolocation data...\n\n";

try {
    $driver = Database::getDriver();
    
    // Check if required tables exist
    $tables = DatabaseBuilder::getTables();
    if (!in_array('ip_tracking', $tables)) {
        echo "   ❌ ip_tracking table does not exist!\n";
        throw new RuntimeException("ip_tracking table does not exist.");
    }
    
    // Check if ip_geo_cache table exists (optional but recommended)
    if (!in_array('ip_geo_cache', $tables)) {
        echo "   ⚠️  ip_geo_cache table does not exist!\n";
        echo "   ⚠️  Geolocation will work but won't be cached. Consider running migration 019.\n";
        echo "   ⏭️  Continuing anyway...\n\n";
    } else {
        echo "   ✅ ip_geo_cache table exists - caching enabled\n\n";
    }
    
    // Find all unique IP addresses that don't have country_code
    echo "1. Finding IP addresses without geolocation data...\n";
    
    // First, count total IPs that need updating
    $totalCount = Database::selectOne("
        SELECT COUNT(DISTINCT ip_address) as total
        FROM ip_tracking 
        WHERE (country_code IS NULL OR country_code = '' OR country_name IS NULL OR country_name = '')
        AND ip_address IS NOT NULL
        AND ip_address != ''
    ");
    $totalIpsNeeded = (int)($totalCount['total'] ?? 0);
    
    echo "   Total IPs needing geolocation: {$totalIpsNeeded}\n";
    
    if ($totalIpsNeeded === 0) {
        echo "   ✅ All IP addresses already have geolocation data\n";
        return;
    }
    
    // Get IPs to process (with limit for this run)
    $ipsToUpdate = Database::select("
        SELECT DISTINCT ip_address, MAX(created_at) as latest_created_at
        FROM ip_tracking 
        WHERE (country_code IS NULL OR country_code = '' OR country_name IS NULL OR country_name = '')
        AND ip_address IS NOT NULL
        AND ip_address != ''
        GROUP BY ip_address
        ORDER BY latest_created_at DESC
        LIMIT 1000
    ");
    
    $totalIps = count($ipsToUpdate);
    echo "   Processing {$totalIps} IP addresses in this run\n";
    if ($totalIpsNeeded > 1000) {
        echo "   ⚠️  Note: {$totalIpsNeeded} total IPs need updating. Run again to process more.\n";
    }
    echo "\n";
    
    $geo = new GeoLocation();
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    $apiCalls = 0;
    $lastApiCallTime = 0;
    
    // Rate limit: ip-api.com allows 45 requests per minute
    $minTimeBetweenCalls = 1.5; // ~1.5 seconds between calls = ~40 calls per minute (safe margin)
    
    echo "2. Fetching geolocation data and updating records...\n";
    echo "   (Rate limited to ~40 API calls per minute)\n\n";
    
    foreach ($ipsToUpdate as $record) {
        $ipAddress = $record['ip_address'];
        
        // Skip private/local IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            echo "   ⏭️  Skipping private IP: {$ipAddress}\n";
            $skipped++;
            continue;
        }
        
        try {
            // Rate limiting: wait if needed
            $timeSinceLastCall = microtime(true) - $lastApiCallTime;
            if ($timeSinceLastCall < $minTimeBetweenCalls) {
                $sleepTime = ($minTimeBetweenCalls - $timeSinceLastCall) * 1000000; // Convert to microseconds
                usleep((int)$sleepTime);
            }
            
            // Get geo data (this will use cache if available, or fetch from API)
            $geoData = IpGeoCache::getGeoData($ipAddress, true); // Force refresh for backfill
            
            $apiCalls++;
            $lastApiCallTime = microtime(true);
            
            if (empty($geoData) || empty($geoData['country_code'])) {
                echo "   ⚠️  No geolocation data for IP: {$ipAddress}\n";
                $errors++;
                continue;
            }
            
            $countryCode = $geoData['country_code'];
            $countryName = $geoData['country_name'] ?? null;
            
            // Get or create language for this country
            $languageId = null;
            if (!empty($countryCode)) {
                $languageCode = $geo->getLanguageForCountry($countryCode);
                if ($languageCode) {
                    $language = Database::selectOne(
                        "SELECT id FROM languages WHERE code = ? LIMIT 1",
                        [$languageCode]
                    );
                    if ($language && isset($language['id'])) {
                        $languageId = (int)$language['id'];
                    } else {
                        // Create language if it doesn't exist
                        $countryToLanguageName = [
                            'RS' => 'Serbian', 'BA' => 'Serbian', 'ME' => 'Serbian',
                            'US' => 'English', 'GB' => 'English', 'CA' => 'English',
                            'DE' => 'German', 'AT' => 'German',
                            'FR' => 'French', 'BE' => 'French',
                            'ES' => 'Spanish', 'MX' => 'Spanish',
                            'IT' => 'Italian',
                            'PT' => 'Portuguese', 'BR' => 'Portuguese',
                            'NL' => 'Dutch',
                            'PL' => 'Polish',
                            'RU' => 'Russian', 'BY' => 'Russian',
                            'UA' => 'Ukrainian',
                            'CZ' => 'Czech',
                            'HU' => 'Hungarian',
                            'GR' => 'Greek', 'CY' => 'Greek',
                            'RO' => 'Romanian', 'MD' => 'Romanian',
                            'HR' => 'Croatian',
                            'BG' => 'Bulgarian',
                            'SK' => 'Slovak',
                            'SE' => 'Swedish',
                            'DK' => 'Danish',
                            'NO' => 'Norwegian',
                            'FI' => 'Finnish',
                            'LT' => 'Lithuanian',
                            'EE' => 'Estonian',
                            'LV' => 'Latvian',
                            'SI' => 'Slovenian',
                            'CN' => 'Chinese', 'TW' => 'Chinese', 'HK' => 'Chinese',
                            'JP' => 'Japanese',
                            'KR' => 'Korean',
                            'TR' => 'Turkish',
                        ];
                        
                        $languageName = $countryToLanguageName[strtoupper($countryCode)] ?? ucfirst($languageCode);
                        $now = date('Y-m-d H:i:s');
                        Database::execute(
                            "INSERT INTO languages (code, name, native_name, flag, country_code, is_active, is_site_language, is_default, sort_order, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, 1, 0, 0, 999, ?, ?)",
                            [$languageCode, $languageName, $languageName, '', $countryCode, $now, $now]
                        );
                        $languageId = Database::lastInsertId();
                    }
                }
            }
            
            // Update all records with this IP address
            $updateData = [
                'country_code' => $countryCode,
                'country_name' => $countryName,
            ];
            
            // Add language_id if column exists
            if ($languageId !== null) {
                try {
                    if ($driver === 'mysql') {
                        $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'language_id'");
                        if (!empty($columns)) {
                            $updateData['language_id'] = $languageId;
                        }
                    } elseif ($driver === 'pgsql') {
                        $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ip_tracking' AND column_name = 'language_id'");
                        if (!empty($columns)) {
                            $updateData['language_id'] = $languageId;
                        }
                    }
                } catch (Exception $e) {
                    // Column doesn't exist, skip it
                }
            }
            
            // Build UPDATE query
            $setClause = [];
            $bindings = [];
            foreach ($updateData as $key => $value) {
                $setClause[] = "{$key} = ?";
                $bindings[] = $value;
            }
            $bindings[] = $ipAddress;
            
            $sql = "UPDATE ip_tracking SET " . implode(', ', $setClause) . " WHERE ip_address = ?";
            $affected = Database::execute($sql, $bindings);
            
            if ($affected > 0) {
                $updated += $affected;
                echo "   ✅ Updated {$affected} record(s) for IP: {$ipAddress} -> {$countryName} ({$countryCode})";
                if ($languageId) {
                    echo " [Language ID: {$languageId}]";
                }
                echo "\n";
            }
            
            // Progress indicator
            if ($apiCalls % 10 === 0) {
                echo "   📊 Progress: {$apiCalls}/{$totalIps} API calls, {$updated} records updated\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error processing IP {$ipAddress}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "   ✅ Updated: {$updated} IP tracking records\n";
    echo "   ⏭️  Skipped: {$skipped} private IPs\n";
    echo "   📡 API Calls: {$apiCalls}\n";
    if ($errors > 0) {
        echo "   ⚠️  Errors: {$errors} IPs\n";
    }
    
    $remaining = $totalIpsNeeded - $updated;
    if ($remaining > 0) {
        echo "\n   ⚠️  Note: {$remaining} IP addresses still need geolocation data.\n";
        echo "   💡 Run this migration again to process more IPs.\n";
    }
    
    echo "\n✅ Migration 032 completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    throw $e;
}

