<?php
/**
 * Migration: Add Continent and Region to Languages
 * 
 * Adds continent_id and region_id columns to languages table
 * Run with: php core/database/migrations/035_add_continent_region_to_languages.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Adding continent_id and region_id to languages table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    
    if (!in_array('languages', $tables)) {
        echo "❌ Table 'languages' does not exist. Please run migration 024 first.\n";
        exit(1);
    }
    
    if (!in_array('continents', $tables)) {
        echo "❌ Table 'continents' does not exist. Please run migration 033 first.\n";
        exit(1);
    }
    
    if (!in_array('regions', $tables)) {
        echo "❌ Table 'regions' does not exist. Please run migration 034 first.\n";
        exit(1);
    }

    // Check if columns already exist
    $columns = DatabaseBuilder::getTableColumns('languages');
    $columnNames = array_column($columns, 'name');
    
    if (in_array('continent_id', $columnNames)) {
        echo "⚠️  Column 'continent_id' already exists.\n";
    } else {
        echo "➕ Adding 'continent_id' column...\n";
        Database::execute("ALTER TABLE languages ADD COLUMN continent_id INTEGER NULL");
        Database::execute("CREATE INDEX idx_languages_continent ON languages(continent_id)");
        echo "✅ Column 'continent_id' added successfully!\n";
    }
    
    if (in_array('region_id', $columnNames)) {
        echo "⚠️  Column 'region_id' already exists.\n";
    } else {
        echo "➕ Adding 'region_id' column...\n";
        Database::execute("ALTER TABLE languages ADD COLUMN region_id INTEGER NULL");
        Database::execute("CREATE INDEX idx_languages_region ON languages(region_id)");
        echo "✅ Column 'region_id' added successfully!\n";
    }

    echo "\n✅ Migration completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
