<?php
/**
 * Migration: Create Pages Table
 * 
 * Creates the pages table for Page Manager system
 * Run with: php core/database/migrations/002_create_pages_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Creating pages table...\n\n";

try {
    // Check if table already exists
    $tables = DatabaseBuilder::getTables();
    if (in_array('pages', $tables)) {
        echo "⚠️  Table 'pages' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS pages");
    }

    // Create pages table
    $builder = new DatabaseTableBuilder('pages');
    $builder->id()
        ->string('title', 255)
        ->string('slug', 255)->unique()
        ->string('route', 255)->unique()  // e.g., '/blog', '/about'
        ->string('page_type', 50)  // 'custom', 'blog_list', 'blog_post', 'blog_category', 'blog_tag'
        ->text('content')->nullable()  // HTML content for 'custom' pages
        ->string('template', 100)->nullable()  // Template name for rendering
        ->string('meta_title', 255)->nullable()
        ->text('meta_description')->nullable()
        ->string('meta_keywords', 255)->nullable()
        ->boolean('is_active')->default(true)
        ->boolean('is_in_menu')->default(true)
        ->integer('menu_order')->default(0)
        ->integer('parent_page_id')->nullable()  // For submenu items
        ->integer('blog_post_id')->nullable()  // FK to blog_posts
        ->integer('blog_category_id')->nullable()  // FK to blog_categories
        ->integer('blog_tag_id')->nullable()  // FK to blog_tags
        ->timestamps()
        ->create();

    // Create indexes (table is new, so indexes don't exist yet)
    Database::execute("CREATE INDEX idx_pages_slug ON pages(slug)");
    Database::execute("CREATE INDEX idx_pages_route ON pages(route)");
    Database::execute("CREATE INDEX idx_pages_active ON pages(is_active)");
    Database::execute("CREATE INDEX idx_pages_menu ON pages(is_in_menu, menu_order)");
    Database::execute("CREATE INDEX idx_pages_parent ON pages(parent_page_id)");

    echo "✅ Table 'pages' created successfully!\n\n";

    // Add foreign key constraints (if tables exist)
    $tables = DatabaseBuilder::getTables();
    $driver = Database::getDriver();
    
    if (in_array('blog_posts', $tables)) {
        if ($driver === 'mysql') {
            // Check if index exists
            $indexes = Database::select("SHOW INDEX FROM pages WHERE Key_name = 'idx_pages_blog_post'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_pages_blog_post ON pages(blog_post_id)");
            }
        } else {
            Database::execute("CREATE INDEX IF NOT EXISTS idx_pages_blog_post ON pages(blog_post_id)");
        }
    }
    if (in_array('blog_categories', $tables)) {
        if ($driver === 'mysql') {
            $indexes = Database::select("SHOW INDEX FROM pages WHERE Key_name = 'idx_pages_blog_category'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_pages_blog_category ON pages(blog_category_id)");
            }
        } else {
            Database::execute("CREATE INDEX IF NOT EXISTS idx_pages_blog_category ON pages(blog_category_id)");
        }
    }
    if (in_array('blog_tags', $tables)) {
        if ($driver === 'mysql') {
            $indexes = Database::select("SHOW INDEX FROM pages WHERE Key_name = 'idx_pages_blog_tag'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_pages_blog_tag ON pages(blog_tag_id)");
            }
        } else {
            Database::execute("CREATE INDEX IF NOT EXISTS idx_pages_blog_tag ON pages(blog_tag_id)");
        }
    }

    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

