<?php
/**
 * Migration: Create IP Services Table
 * 
 * This migration creates the ip_services table for centralized IP -> service mapping.
 * This ensures consistency - same IP always has the same service.
 * Run once with: php core/database/migrations/020_create_ip_services_table.php
 */

// Load environment and database classes
require_once __DIR__ . '/../../../core/config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating ip_services table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('ip_services')) {
    echo "⚠️  Table 'ip_services' already exists!\n";
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
    DatabaseTableBuilder::drop('ip_services');
}

// Create ip_services table
try {
    $builder = new DatabaseTableBuilder('ip_services');
    
    // Primary key (ID)
    $builder->id()
        // IP address (unique - one service per IP)
        ->string('ip_address', 45)->unique()
        // Service information
        ->string('known_service', 100)->nullable()
        ->string('isp', 200)->nullable()
        ->string('organization', 200)->nullable()
        ->boolean('is_proxy')->default(0)
        ->boolean('is_vpn')->default(0)
        ->boolean('is_hosting')->default(0)
        // Detection metadata
        ->string('detection_method', 50)->nullable() // 'user_agent', 'headers', 'geo_api', 'reverse_dns'
        ->integer('detection_count')->default(1) // How many times this service was detected
        ->dateTime('first_detected_at')->nullable()
        ->dateTime('last_detected_at')->nullable()
        // Timestamps
        ->timestamps();
    
    // Create indexes for performance
    // Note: idx_ip_address is already created by ->unique() above
    $builder->index('idx_known_service', ['known_service'])
        ->index('idx_last_detected_at', ['last_detected_at']);
    
    // Create the table
    $builder->create();
    
    echo "✅ Table 'ip_services' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('ip_services');
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

