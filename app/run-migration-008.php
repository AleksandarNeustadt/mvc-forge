<?php
/**
 * Run Migration 008: Add Application to Pages Table
 * 
 * Adds application and display_options columns to pages table
 * Run with: php run-migration-008.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';

echo "🚀 Running Migration 008: Add Application to Pages Table\n\n";

try {
    require_once __DIR__ . '/core/database/migrations/008_add_application_to_pages_table.php';
    
    echo "\n✅ Migration 008 completed successfully!\n";
    echo "📄 Application and display_options columns added to pages table.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration 008 failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

