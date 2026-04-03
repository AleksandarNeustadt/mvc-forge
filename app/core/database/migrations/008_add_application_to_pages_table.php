<?php
/**
 * Migration: Add application and display_options to pages table
 * 
 * Adds:
 * - application field (varchar 50): The application this page belongs to (e.g., 'blog', 'shop', etc.)
 * - display_options field (JSON/TEXT): Display settings for list/grid views
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';


// Check if columns already exist
$columns = Database::select("SHOW COLUMNS FROM pages LIKE 'application'");
if (!empty($columns)) {
    echo "⚠️  Column 'application' already exists. Skipping...\n";
} else {
    // Add application column
    Database::execute("ALTER TABLE pages ADD COLUMN application VARCHAR(50) NULL DEFAULT NULL AFTER page_type");
    echo "✅ Added 'application' column to pages table\n";
}

// Check if display_options column exists
$columns = Database::select("SHOW COLUMNS FROM pages LIKE 'display_options'");
if (!empty($columns)) {
    echo "⚠️  Column 'display_options' already exists. Skipping...\n";
} else {
    // Add display_options column (JSON or TEXT)
    Database::execute("ALTER TABLE pages ADD COLUMN display_options TEXT NULL DEFAULT NULL AFTER application");
    echo "✅ Added 'display_options' column to pages table\n";
}

// Create index for application
try {
    Database::execute("CREATE INDEX idx_pages_application ON pages(application)");
    echo "✅ Created index on 'application' column\n";
} catch (Exception $e) {
    // Index might already exist
    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
        throw $e;
    }
    echo "⚠️  Index on 'application' already exists. Skipping...\n";
}

// Migrate existing data: set application='blog' for existing blog page types
Database::execute("UPDATE pages SET application = 'blog' WHERE page_type IN ('blog_post', 'blog_category', 'blog_tag', 'blog_list') AND application IS NULL");
echo "✅ Migrated existing blog pages to application='blog'\n";

echo "\n✅ Migration completed successfully!\n";

