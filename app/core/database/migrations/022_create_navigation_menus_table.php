<?php
/**
 * Migration: Create Navigation Menus Table
 * 
 * Creates the navigation_menus table for managing navigation menu modules
 * Run with: php core/database/migrations/022_create_navigation_menus_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Creating navigation_menus table...\n\n";

try {
    // Check if table already exists
    $tables = DatabaseBuilder::getTables();
    if (in_array('navigation_menus', $tables)) {
        echo "⚠️  Table 'navigation_menus' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS navigation_menus");
    }

    // Create navigation_menus table
    $builder = new DatabaseTableBuilder('navigation_menus');
    $builder->id()
        ->string('name', 255)  // e.g., "Navbar-header", "navbar-footer"
        ->string('position', 50)  // e.g., "header", "footer"
        ->boolean('is_active')->default(true)
        ->integer('menu_order')->default(0)  // Order within the same position (using menu_order instead of order to avoid MySQL reserved word)
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_nav_menus_position ON navigation_menus(position)");
    Database::execute("CREATE INDEX idx_nav_menus_active ON navigation_menus(is_active)");
    Database::execute("CREATE INDEX idx_nav_menus_order ON navigation_menus(position, menu_order)");

    echo "✅ Table 'navigation_menus' created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

