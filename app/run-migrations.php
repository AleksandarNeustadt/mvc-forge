<?php
/**
 * Run All Migrations
 * 
 * Executes all database migrations in order
 * Run with: php run-migrations.php
 */

echo "🚀 Running database migrations...\n\n";

$migrations = [
    '002_create_pages_table.php',
    '003_create_blog_categories_table.php',
    '004_create_blog_posts_table.php',
    '005_create_blog_tags_table.php',
    '006_create_blog_post_tags_table.php',
    '007_create_blog_post_categories_table.php',
    '008_add_application_to_pages_table.php',
];

$migrationDir = __DIR__ . '/core/database/migrations';
$failed = [];

foreach ($migrations as $migration) {
    $file = $migrationDir . '/' . $migration;
    
    if (!file_exists($file)) {
        echo "⚠️  Migration file not found: {$migration}\n";
        continue;
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Running: {$migration}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    try {
        require $file;
        echo "\n✅ {$migration} completed successfully!\n\n";
    } catch (Exception $e) {
        echo "\n❌ {$migration} failed: " . $e->getMessage() . "\n\n";
        $failed[] = $migration;
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
if (empty($failed)) {
    echo "✅ All migrations completed successfully!\n";
} else {
    echo "❌ Some migrations failed:\n";
    foreach ($failed as $migration) {
        echo "   - {$migration}\n";
    }
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

