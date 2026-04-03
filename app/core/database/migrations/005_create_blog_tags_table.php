<?php
/**
 * Migration: Create Blog Tags Table
 * 
 * Creates the blog_tags table
 * Run with: php core/database/migrations/005_create_blog_tags_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "🏷️  Creating blog_tags table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('blog_tags', $tables)) {
        echo "⚠️  Table 'blog_tags' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS blog_tags");
    }

    $builder = new DatabaseTableBuilder('blog_tags');
    $builder->id()
        ->string('name', 100)
        ->string('slug', 100)->unique()
        ->text('description')->nullable()
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_blog_tags_slug ON blog_tags(slug)");

    echo "✅ Table 'blog_tags' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

