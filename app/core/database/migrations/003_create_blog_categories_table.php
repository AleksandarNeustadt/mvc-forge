<?php
/**
 * Migration: Create Blog Categories Table
 * 
 * Creates the blog_categories table with hierarchical structure
 * Run with: php core/database/migrations/003_create_blog_categories_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📂 Creating blog_categories table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('blog_categories', $tables)) {
        echo "⚠️  Table 'blog_categories' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS blog_categories");
    }

    $builder = new DatabaseTableBuilder('blog_categories');
    $builder->id()
        ->string('name', 255)
        ->string('slug', 255)->unique()
        ->text('description')->nullable()
        ->integer('parent_id')->nullable()  // For hierarchical structure
        ->string('image', 255)->nullable()
        ->string('meta_title', 255)->nullable()
        ->text('meta_description')->nullable()
        ->integer('sort_order')->default(0)
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_blog_categories_slug ON blog_categories(slug)");
    Database::execute("CREATE INDEX idx_blog_categories_parent ON blog_categories(parent_id)");
    Database::execute("CREATE INDEX idx_blog_categories_sort ON blog_categories(sort_order)");

    echo "✅ Table 'blog_categories' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

