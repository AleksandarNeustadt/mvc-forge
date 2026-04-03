<?php
/**
 * Migration: Seed Languages Table
 * 
 * Populates languages table with languages from resources/translations
 * Run with: php core/database/migrations/028_seed_languages.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Seeding languages table...\n\n";

try {
    // Check if languages table exists
    $tables = DatabaseBuilder::getTables();
    if (!in_array('languages', $tables)) {
        echo "❌ Languages table does not exist!\n";
        echo "⚠️  Please run migration 024_create_languages_table.php first.\n";
        exit(1);
    }

    // Language data: code => [name, native_name, flag, country_code, sort_order]
    // country_code is the primary country code for this language (for flag display)
    $languages = [
        'sr' => ['Serbian', 'Српски', '🇷🇸', 'RS', 1],
        'en' => ['English', 'English', '🇬🇧', 'GB', 2],
        'de' => ['German', 'Deutsch', '🇩🇪', 'DE', 3],
        'fr' => ['French', 'Français', '🇫🇷', 'FR', 4],
        'es' => ['Spanish', 'Español', '🇪🇸', 'ES', 5],
        'it' => ['Italian', 'Italiano', '🇮🇹', 'IT', 6],
        'pt' => ['Portuguese', 'Português', '🇵🇹', 'PT', 7],
        'nl' => ['Dutch', 'Nederlands', '🇳🇱', 'NL', 8],
        'pl' => ['Polish', 'Polski', '🇵🇱', 'PL', 9],
        'ru' => ['Russian', 'Русский', '🇷🇺', 'RU', 10],
        'uk' => ['Ukrainian', 'Українська', '🇺🇦', 'UA', 11],
        'cs' => ['Czech', 'Čeština', '🇨🇿', 'CZ', 12],
        'hu' => ['Hungarian', 'Magyar', '🇭🇺', 'HU', 13],
        'el' => ['Greek', 'Ελληνικά', '🇬🇷', 'GR', 14],
        'ro' => ['Romanian', 'Română', '🇷🇴', 'RO', 15],
        'hr' => ['Croatian', 'Hrvatski', '🇭🇷', 'HR', 16],
        'bg' => ['Bulgarian', 'Български', '🇧🇬', 'BG', 17],
        'sk' => ['Slovak', 'Slovenčina', '🇸🇰', 'SK', 18],
        'sv' => ['Swedish', 'Svenska', '🇸🇪', 'SE', 19],
        'da' => ['Danish', 'Dansk', '🇩🇰', 'DK', 20],
        'no' => ['Norwegian', 'Norsk', '🇳🇴', 'NO', 21],
        'fi' => ['Finnish', 'Suomi', '🇫🇮', 'FI', 22],
        'lt' => ['Lithuanian', 'Lietuvių', '🇱🇹', 'LT', 23],
        'et' => ['Estonian', 'Eesti', '🇪🇪', 'EE', 24],
        'lv' => ['Latvian', 'Latviešu', '🇱🇻', 'LV', 25],
        'sl' => ['Slovenian', 'Slovenščina', '🇸🇮', 'SI', 26],
        'ja' => ['Japanese', '日本語', '🇯🇵', 'JP', 27],
        'ko' => ['Korean', '한국어', '🇰🇷', 'KR', 28],
        'zh' => ['Chinese', '中文', '🇨🇳', 'CN', 29],
        'tr' => ['Turkish', 'Türkçe', '🇹🇷', 'TR', 30],
    ];

    $inserted = 0;
    $updated = 0;

    // Check if any default language exists
    $hasDefaultResult = Database::selectOne("SELECT COUNT(*) as count FROM languages WHERE is_default = 1");
    $hasDefault = ($hasDefaultResult['count'] ?? 0) > 0;

    $now = date('Y-m-d H:i:s');

    foreach ($languages as $code => $data) {
        list($name, $nativeName, $flag, $countryCode, $sortOrder) = $data;

        // Check if language already exists
        $existing = Database::selectOne(
            "SELECT id, is_default FROM languages WHERE code = ?",
            [$code]
        );

        if ($existing) {
            // Update existing language (preserve is_default status)
            $isDefault = $existing['is_default'] ?? false;
            // Check if country_code column exists
            $hasCountryCode = false;
            try {
                $driver = Database::getDriver();
                if ($driver === 'mysql') {
                    $columns = Database::select("SHOW COLUMNS FROM languages LIKE 'country_code'");
                    $hasCountryCode = !empty($columns);
                } elseif ($driver === 'pgsql') {
                    $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'languages' AND column_name = 'country_code'");
                    $hasCountryCode = !empty($columns);
                } else {
                    // For other drivers, try to update with country_code and catch error if it doesn't exist
                    $hasCountryCode = true; // Assume it exists, will fail gracefully if not
                }
            } catch (Exception $e) {
                $hasCountryCode = false;
            }
            
            if ($hasCountryCode) {
                try {
                    Database::execute(
                        "UPDATE languages SET name = ?, native_name = ?, flag = ?, country_code = ?, sort_order = ?, updated_at = ? WHERE code = ?",
                        [$name, $nativeName, $flag, $countryCode, $sortOrder, $now, $code]
                    );
                } catch (Exception $e) {
                    // Column might not exist, try without it
                    Database::execute(
                        "UPDATE languages SET name = ?, native_name = ?, flag = ?, sort_order = ?, updated_at = ? WHERE code = ?",
                        [$name, $nativeName, $flag, $sortOrder, $now, $code]
                    );
                }
            } else {
                Database::execute(
                    "UPDATE languages SET name = ?, native_name = ?, flag = ?, sort_order = ?, updated_at = ? WHERE code = ?",
                    [$name, $nativeName, $flag, $sortOrder, $now, $code]
                );
            }
            $updated++;
            echo "  ✅ Updated: {$code} - {$name} (Country: {$countryCode})\n";
        } else {
            // Insert new language
            // Set first language (sr) as default if no default exists
            $isDefault = (!$hasDefault && $code === 'sr') ? 1 : 0;

            // Check if country_code column exists
            $hasCountryCode = false;
            try {
                $driver = Database::getDriver();
                if ($driver === 'mysql') {
                    $columns = Database::select("SHOW COLUMNS FROM languages LIKE 'country_code'");
                    $hasCountryCode = !empty($columns);
                } elseif ($driver === 'pgsql') {
                    $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'languages' AND column_name = 'country_code'");
                    $hasCountryCode = !empty($columns);
                } else {
                    $hasCountryCode = true; // Assume it exists
                }
            } catch (Exception $e) {
                $hasCountryCode = false;
            }
            
            if ($hasCountryCode) {
                try {
                    Database::execute(
                        "INSERT INTO languages (code, name, native_name, flag, country_code, is_active, is_default, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?)",
                        [$code, $name, $nativeName, $flag, $countryCode, $isDefault, $sortOrder, $now, $now]
                    );
                } catch (Exception $e) {
                    // Column might not exist, try without it
                    Database::execute(
                        "INSERT INTO languages (code, name, native_name, flag, is_active, is_default, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)",
                        [$code, $name, $nativeName, $flag, $isDefault, $sortOrder, $now, $now]
                    );
                }
            } else {
                Database::execute(
                    "INSERT INTO languages (code, name, native_name, flag, is_active, is_default, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)",
                    [$code, $name, $nativeName, $flag, $isDefault, $sortOrder, $now, $now]
                );
            }
            $inserted++;
            echo "  ➕ Inserted: {$code} - {$name} ({$nativeName}) {$flag} (Country: {$countryCode})\n";
            
            // Mark that we now have a default
            if ($isDefault) {
                $hasDefault = true;
            }
        }
    }

    echo "\n📊 Summary:\n";
    echo "  ➕ Inserted: {$inserted} languages\n";
    echo "  ✅ Updated: {$updated} languages\n";
    echo "\n✅ Seeding completed!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
