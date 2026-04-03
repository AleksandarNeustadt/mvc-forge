<?php
/**
 * Migration: Create Users Table
 * 
 * This migration creates the users table with all necessary fields
 * Run once with: php core/database/migrations/001_create_users_table.php
 */

if (!function_exists('ap_bootstrap_cli_application')) {
    require_once __DIR__ . '/../../../bootstrap/app.php';
    ap_bootstrap_cli_application(dirname(__DIR__, 3));
}

// Skip routing, just load database
if (!class_exists('DatabaseTableBuilder')) {
    require_once __DIR__ . '/../Database.php';
    require_once __DIR__ . '/../QueryBuilder.php';
    require_once __DIR__ . '/../DatabaseTableBuilder.php';
    require_once __DIR__ . '/../DatabaseBuilder.php';
}

echo "📋 Creating users table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('users')) {
    echo "⚠️  Table 'users' already exists!\n";
    echo "❓ Do you want to drop and recreate it? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $answer = trim(strtolower($line));
    fclose($handle);
    
    if ($answer !== 'y' && $answer !== 'yes') {
        echo "❌ Cancelled.\n";
        exit(0);
    }
    
    echo "🗑️  Dropping existing table...\n";
    DatabaseTableBuilder::drop('users');
}

// Create users table
try {
    $builder = new DatabaseTableBuilder('users');
    
    // Primary key (ID)
    $builder->id()
        // Username and authentication
        ->string('username', 100)->unique()
        ->string('email', 255)->unique()
        ->string('password_hash', 255)
        // Personal information
        ->string('first_name', 100)->nullable()
        ->string('last_name', 100)->nullable()
        ->string('slug', 255)->unique()->nullable()
        ->string('avatar', 500)->nullable()
        // Settings
        ->boolean('newsletter')->default(0)
        // Email verification
        ->integer('email_verified_at')->nullable()
        ->string('email_verification_token', 255)->nullable()
        ->integer('email_verification_expires_at')->nullable()
        // Login tracking
        ->integer('last_login_at')->nullable()
        ->string('last_login_ip', 45)->nullable()
        // Account state and security
        ->integer('banned_at')->nullable()
        ->integer('approved_at')->nullable()
        ->string('status', 20)->default('active')
        ->integer('deleted_at')->nullable()
        ->integer('failed_login_attempts')->default(0)
        ->integer('locked_until')->nullable()
        ->string('password_reset_token', 255)->nullable()
        ->integer('password_reset_expires_at')->nullable()
        // Timestamps
        ->timestamps();
    
    // Create the table
    $builder->create();
    Database::execute("CREATE INDEX idx_users_status ON users(status)");
    Database::execute("CREATE INDEX idx_deleted_at ON users(deleted_at)");
    
    echo "✅ Table 'users' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('users');
    foreach ($columns as $column) {
        $nullable = ($column['nullable'] ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['default_value'] ? " DEFAULT {$column['default_value']}" : '';
        echo "  - {$column['name']}: {$column['type']} {$nullable}{$default}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Migration completed!\n";

