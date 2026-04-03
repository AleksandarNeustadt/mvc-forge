<?php
/**
 * Migration: Create IP Tracking Table
 * 
 * This migration creates the ip_tracking table for monitoring visitor IP addresses
 * Run once with: php core/database/migrations/018_create_ip_tracking_table.php
 */

// Load environment and database classes
require_once __DIR__ . '/../../../core/config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating ip_tracking table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('ip_tracking')) {
    echo "⚠️  Table 'ip_tracking' already exists!\n";
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
    DatabaseTableBuilder::drop('ip_tracking');
}

// Create ip_tracking table
try {
    $builder = new DatabaseTableBuilder('ip_tracking');
    
    // Primary key (ID)
    $builder->id()
        // IP address tracking
        ->string('ip_address', 45)
        ->integer('user_id')->nullable()
        ->string('username', 100)->nullable()
        // Request information
        ->string('request_method', 10)
        ->string('request_path', 500)
        ->text('user_agent')->nullable()
        // Geo-location
        ->string('country_code', 2)->nullable()
        ->string('country_name', 100)->nullable()
        // Security flags
        ->boolean('is_suspicious')->default(0)
        ->integer('request_count')->default(1)
        // Timestamps
        ->timestamps();
    
    // Create indexes for performance
    $builder->index('idx_ip_address', ['ip_address'])
        ->index('idx_user_id', ['user_id'])
        ->index('idx_created_at', ['created_at'])
        ->index('idx_is_suspicious', ['is_suspicious'])
        ->index('idx_country_code', ['country_code']);
    
    // Create the table
    $builder->create();
    
    echo "✅ Table 'ip_tracking' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('ip_tracking');
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

