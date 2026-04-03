<?php
/**
 * Migration: Add language_id to content tables
 * 
 * Adds language_id column to pages, navigation_menus, blog_posts, blog_categories, blog_tags
 * Run with: php core/database/migrations/025_add_language_id_to_tables.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Adding language_id columns to content tables...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    
    // Tables to update
    $tablesToUpdate = [
        'pages',
        'navigation_menus',
        'blog_posts',
        'blog_categories',
        'blog_tags'
    ];
    
    foreach ($tablesToUpdate as $tableName) {
        if (!in_array($tableName, $tables)) {
            echo "⚠️  Table '{$tableName}' does not exist. Skipping...\n";
            continue;
        }
        
        // Check if column already exists
        $columns = DatabaseBuilder::getTableColumns($tableName);
        $columnExists = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'language_id') {
                $columnExists = true;
                break;
            }
        }
        
        if ($columnExists) {
            echo "ℹ️  Column 'language_id' already exists in '{$tableName}'. Skipping...\n";
            continue;
        }
        
        // Add language_id column
        echo "➕ Adding language_id to '{$tableName}'...\n";
        Database::execute("ALTER TABLE {$tableName} ADD COLUMN language_id INTEGER NULL");
        
        // Create index
        Database::execute("CREATE INDEX idx_{$tableName}_language ON {$tableName}(language_id)");
        
        // Add foreign key constraint if languages table exists
        if (in_array('languages', $tables)) {
            try {
                // Check database driver
                $driver = Database::getDriver();
                
                if ($driver === 'mysql' || $driver === 'pgsql') {
                    // MySQL/PostgreSQL: Use FOREIGN KEY constraint directly
                    Database::execute("
                        ALTER TABLE {$tableName} 
                        ADD CONSTRAINT fk_{$tableName}_language 
                        FOREIGN KEY (language_id) 
                        REFERENCES languages(id) 
                        ON DELETE SET NULL 
                        ON UPDATE CASCADE
                    ");
                    echo "✅ Foreign key constraint added for {$tableName}.language_id\n";
                } else {
                    // SQLite: Use triggers (SQLite doesn't support foreign keys in ALTER TABLE the same way)
                    try {
                        Database::execute("
                            CREATE TRIGGER fk_{$tableName}_language 
                            BEFORE INSERT ON {$tableName}
                            FOR EACH ROW
                            WHEN NEW.language_id IS NOT NULL
                            BEGIN
                                SELECT CASE
                                    WHEN (SELECT COUNT(*) FROM languages WHERE id = NEW.language_id) = 0
                                    THEN RAISE(ABORT, 'Foreign key constraint failed: language_id does not exist')
                                END;
                            END
                        ");
                        
                        Database::execute("
                            CREATE TRIGGER fk_{$tableName}_language_update 
                            BEFORE UPDATE ON {$tableName}
                            FOR EACH ROW
                            WHEN NEW.language_id IS NOT NULL
                            BEGIN
                                SELECT CASE
                                    WHEN (SELECT COUNT(*) FROM languages WHERE id = NEW.language_id) = 0
                                    THEN RAISE(ABORT, 'Foreign key constraint failed: language_id does not exist')
                                END;
                            END
                        ");
                        echo "✅ Foreign key triggers added for {$tableName}.language_id (SQLite)\n";
                    } catch (Exception $e) {
                        echo "⚠️  Could not create foreign key constraint for {$tableName}.language_id: " . $e->getMessage() . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "⚠️  Could not create foreign key constraint for {$tableName}.language_id: " . $e->getMessage() . "\n";
            }
        }
        
        echo "✅ Added language_id to '{$tableName}' successfully!\n";
    }
    
    echo "\n✅ All language_id columns added successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

