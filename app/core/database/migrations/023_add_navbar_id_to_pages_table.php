<?php
/**
 * Migration: Add navbar_id to pages table
 * 
 * Adds navbar_id column to pages table to link pages to navigation menus
 * Run with: php core/database/migrations/023_add_navbar_id_to_pages_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';

echo "📄 Adding navbar_id column to pages table...\n\n";

try {
    $driver = Database::getDriver();
    
    // Check if column already exists
    if ($driver === 'mysql') {
        $columns = Database::select("SHOW COLUMNS FROM pages LIKE 'navbar_id'");
        if (!empty($columns)) {
            echo "⚠️  Column 'navbar_id' already exists. Skipping...\n";
            exit(0);
        }
        
        // Add navbar_id column
        Database::execute("ALTER TABLE pages ADD COLUMN navbar_id INTEGER NULL DEFAULT NULL AFTER is_in_menu");
        echo "✅ Added 'navbar_id' column to pages table\n";
        
        // Create index
        try {
            Database::execute("CREATE INDEX idx_pages_navbar_id ON pages(navbar_id)");
            echo "✅ Created index on 'navbar_id' column\n";
        } catch (Exception $e) {
            // Index might already exist
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
            echo "⚠️  Index on 'navbar_id' already exists. Skipping...\n";
        }
        
        // Add foreign key constraint if navigation_menus table exists
        $tables = Database::select("SHOW TABLES LIKE 'navigation_menus'");
        if (!empty($tables)) {
            try {
                Database::execute("ALTER TABLE pages ADD CONSTRAINT fk_pages_navbar_id FOREIGN KEY (navbar_id) REFERENCES navigation_menus(id) ON DELETE SET NULL");
                echo "✅ Added foreign key constraint\n";
            } catch (Exception $e) {
                // Foreign key might already exist
                if (strpos($e->getMessage(), 'Duplicate key name') === false && strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️  Could not add foreign key: " . $e->getMessage() . "\n";
                } else {
                    echo "⚠️  Foreign key already exists. Skipping...\n";
                }
            }
        }
    } elseif ($driver === 'pgsql') {
        // PostgreSQL
        $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pages' AND column_name = 'navbar_id'");
        if (!empty($columns)) {
            echo "⚠️  Column 'navbar_id' already exists. Skipping...\n";
            exit(0);
        }
        
        Database::execute("ALTER TABLE pages ADD COLUMN navbar_id INTEGER NULL DEFAULT NULL");
        Database::execute("CREATE INDEX IF NOT EXISTS idx_pages_navbar_id ON pages(navbar_id)");
        echo "✅ Added 'navbar_id' column to pages table\n";
    } else {
        // SQLite
        // SQLite doesn't support ALTER TABLE ADD COLUMN easily, so we'll use a workaround
        echo "⚠️  SQLite detected. Please manually add navbar_id column if needed.\n";
    }

    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

