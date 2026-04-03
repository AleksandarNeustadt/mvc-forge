<?php
/**
 * Migration: Create Regions Table
 * 
 * Creates the regions table for grouping languages by region within continents
 * Run with: php core/database/migrations/034_create_regions_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Creating regions table...\n\n";

try {
    // Check if table already exists
    $tables = DatabaseBuilder::getTables();
    if (in_array('regions', $tables)) {
        echo "⚠️  Table 'regions' already exists. Skipping creation.\n";
        echo "✅ Migration completed (table already exists)!\n\n";
        exit(0);
    }

    // Create regions table
    $builder = new DatabaseTableBuilder('regions');
    $builder->id()
        ->integer('continent_id')  // FK to continents
        ->string('code', 20)->nullable()  // e.g., "western-europe", "eastern-europe"
        ->string('name', 100)  // e.g., "Western Europe", "Eastern Europe"
        ->string('native_name', 100)->nullable()  // Native name if applicable
        ->text('description')->nullable()  // Optional description
        ->integer('sort_order')->default(0)
        ->boolean('is_active')->default(true)
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_regions_continent ON regions(continent_id)");
    Database::execute("CREATE INDEX idx_regions_code ON regions(code)");
    Database::execute("CREATE INDEX idx_regions_active ON regions(is_active)");
    Database::execute("CREATE INDEX idx_regions_sort ON regions(sort_order)");
    
    // Create foreign key
    Database::execute("CREATE INDEX idx_regions_continent_fk ON regions(continent_id)");
    // Note: Foreign key constraint will be added in a separate migration if needed

    echo "✅ Table 'regions' created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
