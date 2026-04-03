<?php
/**
 * Migration: Update Existing IP Tracking Language IDs
 * 
 * Migration 031
 * 
 * Updates existing ip_tracking records to populate language_id based on country_code
 * This will backfill the language_id for all existing IP tracking entries
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

// Load GeoLocation for country to language mapping
require_once __DIR__ . '/../../services/GeoLocation.php';

echo "📋 Migration 031: Updating existing IP tracking records with language_id...\n\n";

try {
    $driver = Database::getDriver();
    
    // Check if language_id column exists
    echo "1. Checking if language_id column exists...\n";
    $hasLanguageId = false;
    if ($driver === 'mysql') {
        $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'language_id'");
        $hasLanguageId = !empty($columns);
    } elseif ($driver === 'pgsql') {
        $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ip_tracking' AND column_name = 'language_id'");
        $hasLanguageId = !empty($columns);
    }
    
    if (!$hasLanguageId) {
        echo "   ❌ Column 'language_id' does not exist in ip_tracking table!\n";
        echo "   ⚠️  Please run migration 030 first.\n";
        throw new RuntimeException("Column 'language_id' does not exist in ip_tracking table. Please run migration 030 first.");
    }
    
    echo "   ✅ Column 'language_id' exists\n\n";
    
    // Check if languages table exists
    $tables = DatabaseBuilder::getTables();
    if (!in_array('languages', $tables)) {
        echo "   ❌ Languages table does not exist!\n";
        throw new RuntimeException("Languages table does not exist.");
    }
    
    // Get all unique country codes from ip_tracking that don't have language_id
    echo "2. Finding IP tracking records without language_id...\n";
    $recordsToUpdate = Database::select("
        SELECT DISTINCT country_code 
        FROM ip_tracking 
        WHERE country_code IS NOT NULL 
        AND language_id IS NULL
        AND country_code != ''
    ");
    
    $totalRecords = count($recordsToUpdate);
    echo "   Found {$totalRecords} unique country codes to process\n\n";
    
    if ($totalRecords === 0) {
        echo "   ✅ All records already have language_id assigned\n";
        return;
    }
    
    // Initialize GeoLocation for country to language mapping
    $geo = new GeoLocation();
    $updated = 0;
    $created = 0;
    $errors = 0;
    
    echo "3. Processing country codes and assigning language_id...\n";
    
    foreach ($recordsToUpdate as $record) {
        $countryCode = $record['country_code'];
        
        try {
            // Map country code to language code
            $languageCode = $geo->getLanguageForCountry($countryCode);
            
            if (!$languageCode) {
                echo "   ⚠️  No language mapping for country: {$countryCode}\n";
                $errors++;
                continue;
            }
            
            // Find language in database
            $language = Database::selectOne(
                "SELECT id FROM languages WHERE code = ? LIMIT 1",
                [$languageCode]
            );
            
            $languageId = null;
            
            if ($language && isset($language['id'])) {
                // Language exists, use its ID
                $languageId = (int)$language['id'];
            } else {
                // Language doesn't exist, create it
                // Get country name mapping
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
                $created++;
                echo "   ➕ Created language: {$languageCode} (ID: {$languageId}) for country: {$countryCode}\n";
            }
            
            // Update all ip_tracking records with this country_code
            $affected = Database::execute(
                "UPDATE ip_tracking SET language_id = ? WHERE country_code = ? AND language_id IS NULL",
                [$languageId, $countryCode]
            );
            
            if ($affected > 0) {
                $updated += $affected;
                echo "   ✅ Updated {$affected} records for country: {$countryCode} -> language: {$languageCode} (ID: {$languageId})\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error processing country {$countryCode}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n📊 Summary:\n";
    echo "   ✅ Updated: {$updated} IP tracking records\n";
    echo "   ➕ Created: {$created} new language entries\n";
    if ($errors > 0) {
        echo "   ⚠️  Errors: {$errors} countries\n";
    }
    
    echo "\n✅ Migration 031 completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    throw $e;
}

