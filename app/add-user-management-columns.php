<?php
/**
 * Add User Management Columns
 * 
 * Adds banned_at, approved_at, and status columns to users table
 * Run: php add-user-management-columns.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';
require_once __DIR__ . '/core/database/DatabaseTableBuilder.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';

echo "🔧 Adding user management columns...\n\n";

try {
    // Get PDO connection
    $connection = Database::connection();
    
    // Check if columns already exist
    $tableInfo = DatabaseBuilder::getTableInfo('users');
    $existingColumns = array_column($tableInfo['columns'] ?? [], 'name');
    
    // Add banned_at column
    if (!in_array('banned_at', $existingColumns)) {
        echo "➕ Adding 'banned_at' column...\n";
        $connection->exec("ALTER TABLE users ADD COLUMN banned_at INTEGER NULL DEFAULT NULL");
        echo "✅ Column 'banned_at' added!\n\n";
    } else {
        echo "⏭️  Column 'banned_at' already exists.\n\n";
    }
    
    // Add approved_at column
    if (!in_array('approved_at', $existingColumns)) {
        echo "➕ Adding 'approved_at' column...\n";
        $connection->exec("ALTER TABLE users ADD COLUMN approved_at INTEGER NULL DEFAULT NULL");
        echo "✅ Column 'approved_at' added!\n\n";
    } else {
        echo "⏭️  Column 'approved_at' already exists.\n\n";
    }
    
    // Add status column (active, pending, banned)
    if (!in_array('status', $existingColumns)) {
        echo "➕ Adding 'status' column...\n";
        $connection->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        echo "✅ Column 'status' added!\n\n";
    } else {
        echo "⏭️  Column 'status' already exists.\n\n";
    }
    
    // Update existing users to have 'active' status
    echo "🔄 Updating existing users...\n";
    $connection->exec("UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''");
    echo "✅ Existing users updated!\n\n";
    
    echo "✅ All columns added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

