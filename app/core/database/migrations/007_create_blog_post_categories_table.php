<?php
/**
 * Migration: Create Blog Post Categories Pivot Table
 * 
 * Creates the blog_post_categories table (many-to-many relationship)
 * Run with: php core/database/migrations/007_create_blog_post_categories_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "🔗 Creating blog_post_categories pivot table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('blog_post_categories', $tables)) {
        echo "⚠️  Table 'blog_post_categories' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS blog_post_categories");
    }

    $builder = new DatabaseTableBuilder('blog_post_categories');
    $builder->id()
        ->integer('blog_post_id')
        ->integer('blog_category_id')
        ->integer('created_at');
    // Note: Unique constraint will be handled via SQL after table creation
    $builder->create();
    
    // Create indexes and unique constraint
    Database::execute("CREATE INDEX idx_post_categories_post ON blog_post_categories(blog_post_id)");
    Database::execute("CREATE INDEX idx_post_categories_category ON blog_post_categories(blog_category_id)");
    Database::execute("CREATE UNIQUE INDEX idx_unique_post_category ON blog_post_categories(blog_post_id, blog_category_id)");

    echo "✅ Table 'blog_post_categories' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

