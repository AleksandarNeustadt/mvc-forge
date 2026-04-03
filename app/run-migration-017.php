<?php
/**
 * Run Migration 017: Add Email Verification
 * 
 * Adds email_verification_token and email_verification_expires_at columns
 * Run with: php run-migration-017.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';

echo "🚀 Running Migration 017: Add Email Verification\n\n";

try {
    require_once __DIR__ . '/core/database/migrations/017_add_email_verification.php';
    
    $migration = new Migration_017_AddEmailVerification();
    $migration->up();
    
    echo "\n✅ Migration 017 completed successfully!\n";
    echo "📧 Email verification columns added to users table.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration 017 failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

