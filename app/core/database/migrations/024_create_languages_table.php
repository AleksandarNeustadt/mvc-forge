<?php
/**
 * Migration: Create Languages Table
 * 
 * Creates the languages table for managing site languages
 * Run with: php core/database/migrations/024_create_languages_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Creating languages table...\n\n";

try {
    // Check if table already exists
    $tables = DatabaseBuilder::getTables();
    if (in_array('languages', $tables)) {
        echo "⚠️  Table 'languages' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS languages");
    }

    // Create languages table
    $builder = new DatabaseTableBuilder('languages');
    $builder->id()
        ->string('code', 10)->unique()  // e.g., "sr", "en", "de"
        ->string('name', 100)  // e.g., "Serbian", "English", "German"
        ->string('native_name', 100)  // e.g., "Српски", "English", "Deutsch"
        ->string('flag', 10)->nullable()  // Flag emoji or code
        ->boolean('is_active')->default(true)
        ->boolean('is_default')->default(false)  // Only one should be default
        ->integer('sort_order')->default(0)
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_languages_code ON languages(code)");
    Database::execute("CREATE INDEX idx_languages_active ON languages(is_active)");
    Database::execute("CREATE INDEX idx_languages_default ON languages(is_default)");
    Database::execute("CREATE INDEX idx_languages_sort ON languages(sort_order)");

    echo "✅ Table 'languages' created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

