<?php
/**
 * Run Migration 014: Add Soft Delete and Audit Log
 * 
 * Adds deleted_at column to users table and creates audit_logs table
 * Run with: php run-migration-014.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';

echo "🚀 Running Migration 014: Add Soft Delete and Audit Log\n\n";

try {
    require_once __DIR__ . '/core/database/migrations/014_add_soft_delete_and_audit_log.php';
    
    $migration = new Migration_014_AddSoftDeleteAndAuditLog();
    $migration->up();
    
    echo "\n✅ Migration 014 completed successfully!\n";
    echo "🗑️  Soft delete (deleted_at column) added to users table.\n";
    echo "📝 Audit logs table created.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration 014 failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

