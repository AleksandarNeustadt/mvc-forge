<?php
/**
 * Migration: Add Foreign Key Constraints for language_id
 * 
 * Adds foreign key constraints to language_id columns in content tables
 * Run with: php core/database/migrations/026_add_language_foreign_keys.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Adding foreign key constraints for language_id columns...\n\n";

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
    
    $driver = Database::getDriver();
    
    if (!in_array('languages', $tables)) {
        echo "⚠️  Table 'languages' does not exist. Please run migration 024_create_languages_table.php first.\n";
        exit(1);
    }
    
    foreach ($tablesToUpdate as $tableName) {
        if (!in_array($tableName, $tables)) {
            echo "⚠️  Table '{$tableName}' does not exist. Skipping...\n";
            continue;
        }
        
        // Check if column exists
        $columns = DatabaseBuilder::getTableColumns($tableName);
        $columnExists = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'language_id') {
                $columnExists = true;
                break;
            }
        }
        
        if (!$columnExists) {
            echo "⚠️  Column 'language_id' does not exist in '{$tableName}'. Skipping...\n";
            continue;
        }
        
        // Check if foreign key constraint already exists
        $constraintExists = false;
        if ($driver === 'mysql') {
            $dbName = Env::get('DB_DATABASE');
            $constraintName = "fk_{$tableName}_language";
            $checkSql = "SELECT COUNT(*) as count 
                        FROM information_schema.table_constraints 
                        WHERE constraint_schema = ? 
                        AND constraint_name = ? 
                        AND table_name = ?";
            $result = Database::selectOne($checkSql, [$dbName, $constraintName, $tableName]);
            $constraintExists = ($result['count'] ?? 0) > 0;
        } elseif ($driver === 'pgsql') {
            $constraintName = "fk_{$tableName}_language";
            $checkSql = "SELECT COUNT(*) as count 
                        FROM information_schema.table_constraints 
                        WHERE constraint_schema = 'public' 
                        AND constraint_name = ? 
                        AND table_name = ?";
            $result = Database::selectOne($checkSql, [$constraintName, $tableName]);
            $constraintExists = ($result['count'] ?? 0) > 0;
        }
        
        if ($constraintExists) {
            echo "ℹ️  Foreign key constraint already exists for '{$tableName}.language_id'. Skipping...\n";
            continue;
        }
        
        // Add foreign key constraint
        echo "➕ Adding foreign key constraint for '{$tableName}.language_id'...\n";
        
        try {
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
                // SQLite: Foreign keys are handled differently, skip for now
                echo "ℹ️  SQLite foreign key constraints require special setup. Skipping...\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Could not create foreign key constraint for {$tableName}.language_id: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Foreign key constraints added successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

