<?php
/**
 * Migration: Seed Continents and Regions
 * 
 * Populates continents and regions tables with initial data
 * Run with: php core/database/migrations/036_seed_continents_regions.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Seeding continents and regions...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    
    if (!in_array('continents', $tables)) {
        echo "❌ Table 'continents' does not exist. Please run migration 033 first.\n";
        throw new RuntimeException("Table 'continents' does not exist. Please run migration 033 first.");
    }
    
    if (!in_array('regions', $tables)) {
        echo "❌ Table 'regions' does not exist. Please run migration 034 first.\n";
        throw new RuntimeException("Table 'regions' does not exist. Please run migration 034 first.");
    }

    // Continents data
    $continents = [
        ['code' => 'eu', 'name' => 'Europe', 'native_name' => 'Europe', 'sort_order' => 1],
        ['code' => 'as', 'name' => 'Asia', 'native_name' => 'Asia', 'sort_order' => 2],
        ['code' => 'na', 'name' => 'North America', 'native_name' => 'North America', 'sort_order' => 3],
        ['code' => 'sa', 'name' => 'South America', 'native_name' => 'South America', 'sort_order' => 4],
        ['code' => 'af', 'name' => 'Africa', 'native_name' => 'Africa', 'sort_order' => 5],
        ['code' => 'oc', 'name' => 'Oceania', 'native_name' => 'Oceania', 'sort_order' => 6],
        ['code' => 'an', 'name' => 'Antarctica', 'native_name' => 'Antarctica', 'sort_order' => 7],
    ];

    echo "🌍 Inserting continents...\n";
    $continentIds = [];
    foreach ($continents as $continent) {
        // Check if already exists
        $existing = Database::selectOne("SELECT id FROM continents WHERE code = ?", [$continent['code']]);
        
        if ($existing) {
            echo "  ⚠️  Continent '{$continent['name']}' already exists, skipping...\n";
            $continentIds[$continent['code']] = $existing['id'];
        } else {
            $now = date('Y-m-d H:i:s');
            Database::execute("INSERT INTO continents (code, name, native_name, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)", [
                $continent['code'], 
                $continent['name'], 
                $continent['native_name'], 
                $continent['sort_order'], 
                $now, 
                $now
            ]);
            $continentIds[$continent['code']] = Database::lastInsertId();
            echo "  ✅ Inserted continent: {$continent['name']}\n";
        }
    }

    // Regions data (organized by continent)
    $regions = [
        // Europe
        ['continent' => 'eu', 'code' => 'western-europe', 'name' => 'Western Europe', 'sort_order' => 1],
        ['continent' => 'eu', 'code' => 'eastern-europe', 'name' => 'Eastern Europe', 'sort_order' => 2],
        ['continent' => 'eu', 'code' => 'northern-europe', 'name' => 'Northern Europe', 'sort_order' => 3],
        ['continent' => 'eu', 'code' => 'southern-europe', 'name' => 'Southern Europe', 'sort_order' => 4],
        ['continent' => 'eu', 'code' => 'central-europe', 'name' => 'Central Europe', 'sort_order' => 5],
        ['continent' => 'eu', 'code' => 'balkans', 'name' => 'Balkans', 'sort_order' => 6],
        
        // Asia
        ['continent' => 'as', 'code' => 'east-asia', 'name' => 'East Asia', 'sort_order' => 1],
        ['continent' => 'as', 'code' => 'south-asia', 'name' => 'South Asia', 'sort_order' => 2],
        ['continent' => 'as', 'code' => 'southeast-asia', 'name' => 'Southeast Asia', 'sort_order' => 3],
        ['continent' => 'as', 'code' => 'central-asia', 'name' => 'Central Asia', 'sort_order' => 4],
        ['continent' => 'as', 'code' => 'west-asia', 'name' => 'West Asia', 'sort_order' => 5],
        ['continent' => 'as', 'code' => 'middle-east', 'name' => 'Middle East', 'sort_order' => 6],
        
        // North America
        ['continent' => 'na', 'code' => 'north-america', 'name' => 'North America', 'sort_order' => 1],
        ['continent' => 'na', 'code' => 'central-america', 'name' => 'Central America', 'sort_order' => 2],
        ['continent' => 'na', 'code' => 'caribbean', 'name' => 'Caribbean', 'sort_order' => 3],
        
        // South America
        ['continent' => 'sa', 'code' => 'south-america', 'name' => 'South America', 'sort_order' => 1],
        
        // Africa
        ['continent' => 'af', 'code' => 'north-africa', 'name' => 'North Africa', 'sort_order' => 1],
        ['continent' => 'af', 'code' => 'west-africa', 'name' => 'West Africa', 'sort_order' => 2],
        ['continent' => 'af', 'code' => 'east-africa', 'name' => 'East Africa', 'sort_order' => 3],
        ['continent' => 'af', 'code' => 'central-africa', 'name' => 'Central Africa', 'sort_order' => 4],
        ['continent' => 'af', 'code' => 'southern-africa', 'name' => 'Southern Africa', 'sort_order' => 5],
        
        // Oceania
        ['continent' => 'oc', 'code' => 'australasia', 'name' => 'Australasia', 'sort_order' => 1],
        ['continent' => 'oc', 'code' => 'polynesia', 'name' => 'Polynesia', 'sort_order' => 2],
        ['continent' => 'oc', 'code' => 'melanesia', 'name' => 'Melanesia', 'sort_order' => 3],
        ['continent' => 'oc', 'code' => 'micronesia', 'name' => 'Micronesia', 'sort_order' => 4],
    ];

    echo "\n🗺️  Inserting regions...\n";
    foreach ($regions as $region) {
        $continentId = $continentIds[$region['continent']] ?? null;
        
        if (!$continentId) {
            echo "  ⚠️  Continent '{$region['continent']}' not found, skipping region '{$region['name']}'...\n";
            continue;
        }
        
        // Check if already exists
        $existing = Database::selectOne("SELECT id FROM regions WHERE code = ? AND continent_id = ?", [$region['code'], $continentId]);
        
        if ($existing) {
            echo "  ⚠️  Region '{$region['name']}' already exists, skipping...\n";
        } else {
            $now = date('Y-m-d H:i:s');
            Database::execute("INSERT INTO regions (continent_id, code, name, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)", [
                $continentId, 
                $region['code'], 
                $region['name'], 
                $region['sort_order'], 
                $now, 
                $now
            ]);
            echo "  ✅ Inserted region: {$region['name']}\n";
        }
    }

    echo "\n✅ Seeding completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    throw $e;
}
