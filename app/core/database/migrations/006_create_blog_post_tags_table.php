<?php
/**
 * Migration: Create Blog Post Tags Pivot Table
 * 
 * Creates the blog_post_tags table (many-to-many relationship)
 * Run with: php core/database/migrations/006_create_blog_post_tags_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "🔗 Creating blog_post_tags pivot table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('blog_post_tags', $tables)) {
        echo "⚠️  Table 'blog_post_tags' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS blog_post_tags");
    }

    $builder = new DatabaseTableBuilder('blog_post_tags');
    $builder->id()
        ->integer('blog_post_id')
        ->integer('blog_tag_id')
        ->integer('created_at');
    $builder->create();
    
    // Create indexes
    Database::execute("CREATE INDEX idx_post_tags_post ON blog_post_tags(blog_post_id)");
    Database::execute("CREATE INDEX idx_post_tags_tag ON blog_post_tags(blog_tag_id)");
    Database::execute("CREATE UNIQUE INDEX idx_unique_post_tag ON blog_post_tags(blog_post_id, blog_tag_id)");

    echo "✅ Table 'blog_post_tags' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

