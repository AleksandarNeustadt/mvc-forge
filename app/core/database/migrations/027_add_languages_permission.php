<?php
/**
 * Migration: Add Languages Permission
 * 
 * Adds system.languages permission to the database
 * Run with: php core/database/migrations/027_add_languages_permission.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📄 Adding languages permission...\n\n";

try {
    // Check if permissions table exists
    $tables = DatabaseBuilder::getTables();
    if (!in_array('permissions', $tables)) {
        echo "❌ Permissions table does not exist!\n";
        echo "⚠️  Please run migration 010_create_permissions_table.php first.\n";
        exit(1);
    }

    // Check if permission already exists
    $existing = Database::selectOne(
        "SELECT id FROM permissions WHERE slug = ?",
        ['system.languages']
    );

    if ($existing) {
        echo "ℹ️  Permission 'system.languages' already exists. Skipping...\n";
    } else {
        // Insert permission
        Database::execute(
            "INSERT INTO permissions (name, slug, description, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
            [
                'Manage Languages',
                'system.languages',
                'Manage site languages',
                'system',
                time(),
                time()
            ]
        );
        echo "✅ Permission 'system.languages' added successfully!\n";
    }

    echo "\n✅ Migration completed!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

