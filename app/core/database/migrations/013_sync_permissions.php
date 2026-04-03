<?php
/**
 * Migration: Sync Permissions
 * 
 * This migration syncs all registered permissions to the database
 * Run with: php core/database/migrations/013_sync_permissions.php
 * 
 * This can be run anytime to add new permissions that were registered in code
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

// Load base Model class
// From core/database/migrations, go up to root, then to mvc/models
require_once __DIR__ . '/../../../mvc/models/Model.php';

// Load Permission model
require_once __DIR__ . '/../../../mvc/models/Permission.php';

// Load PermissionRegistry
require_once __DIR__ . '/../../../core/permissions/PermissionRegistry.php';

echo "🔄 Syncing permissions...\n\n";

try {
    // Check if permissions table exists
    if (!DatabaseTableBuilder::exists('permissions')) {
        echo "❌ Permissions table does not exist!\n";
        echo "⚠️  Please run migration 010_create_permissions_table.php first.\n";
        exit(1);
    }

    // Load default permissions
    PermissionRegistry::loadDefaults();
    
    // Sync to database
    PermissionRegistry::sync();
    
    $permissions = PermissionRegistry::all();
    echo "✅ Synced " . count($permissions) . " permissions to database!\n\n";
    
    // Show summary by category
    $byCategory = [];
    foreach ($permissions as $permission) {
        $category = $permission['category'] ?? 'other';
        if (!isset($byCategory[$category])) {
            $byCategory[$category] = 0;
        }
        $byCategory[$category]++;
    }
    
    echo "📊 Permissions by category:\n";
    foreach ($byCategory as $category => $count) {
        echo "  - {$category}: {$count}\n";
    }
    
    echo "\n✅ Sync completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error syncing permissions: " . $e->getMessage() . "\n";
    exit(1);
}

