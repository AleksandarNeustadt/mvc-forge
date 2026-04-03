<?php
/**
 * Migration: Create Continents Table
 * 
 * Creates the continents table for grouping languages by continent
 * Run with: php core/database/migrations/033_create_continents_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Creating continents table...\n\n";

try {
    // Check if table already exists
    $tables = DatabaseBuilder::getTables();
    if (in_array('continents', $tables)) {
        echo "⚠️  Table 'continents' already exists. Skipping creation.\n";
        echo "✅ Migration completed (table already exists)!\n\n";
        exit(0);
    }

    // Create continents table
    $builder = new DatabaseTableBuilder('continents');
    $builder->id()
        ->string('code', 10)->unique()  // e.g., "eu", "as", "na", "sa", "af", "oc", "an"
        ->string('name', 100)  // e.g., "Europe", "Asia", "North America"
        ->string('native_name', 100)->nullable()  // Native name if applicable
        ->integer('sort_order')->default(0)
        ->boolean('is_active')->default(true)
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_continents_code ON continents(code)");
    Database::execute("CREATE INDEX idx_continents_active ON continents(is_active)");
    Database::execute("CREATE INDEX idx_continents_sort ON continents(sort_order)");

    echo "✅ Table 'continents' created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
