<?php
/**
 * Migration: Create Blog Posts Table
 * 
 * Creates the blog_posts table
 * Run with: php core/database/migrations/004_create_blog_posts_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📝 Creating blog_posts table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('blog_posts', $tables)) {
        echo "⚠️  Table 'blog_posts' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS blog_posts");
    }

    $builder = new DatabaseTableBuilder('blog_posts');
    $builder->id()
        ->string('title', 255)
        ->string('slug', 255)->unique()
        ->text('excerpt')->nullable()  // Short description
        ->text('content')  // Full content (HTML/Markdown)
        ->string('featured_image', 255)->nullable()
        ->string('status', 20)->default('draft')  // 'draft', 'published', 'archived'
        ->integer('published_at')->nullable()
        ->integer('author_id')  // FK to users
        ->integer('views')->default(0)
        ->string('meta_title', 255)->nullable()
        ->text('meta_description')->nullable()
        ->string('meta_keywords', 255)->nullable()
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_blog_posts_slug ON blog_posts(slug)");
    Database::execute("CREATE INDEX idx_blog_posts_status ON blog_posts(status)");
    Database::execute("CREATE INDEX idx_blog_posts_published ON blog_posts(published_at)");
    Database::execute("CREATE INDEX idx_blog_posts_author ON blog_posts(author_id)");

    echo "✅ Table 'blog_posts' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

