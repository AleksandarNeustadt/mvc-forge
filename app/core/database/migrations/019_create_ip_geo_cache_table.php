<?php
/**
 * Migration: Create IP Geo Cache Table
 * 
 * This migration creates the ip_geo_cache table for caching IP geolocation data
 * to avoid repeated API calls and improve performance.
 * Run once with: php core/database/migrations/019_create_ip_geo_cache_table.php
 */

// Load environment and database classes
require_once __DIR__ . '/../../../core/config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating ip_geo_cache table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('ip_geo_cache')) {
    echo "⚠️  Table 'ip_geo_cache' already exists!\n";
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
    DatabaseTableBuilder::drop('ip_geo_cache');
}

// Create ip_geo_cache table
try {
    $builder = new DatabaseTableBuilder('ip_geo_cache');
    
    // Primary key (ID)
    $builder->id()
        // IP address (unique)
        ->string('ip_address', 45)->unique()
        // Geo-location data
        ->string('country_code', 2)->nullable()
        ->string('country_name', 100)->nullable()
        ->string('region', 100)->nullable()
        ->string('city', 100)->nullable()
        ->string('isp', 200)->nullable()
        ->string('organization', 200)->nullable()
        ->boolean('is_proxy')->default(0)
        ->boolean('is_vpn')->default(0)
        ->boolean('is_hosting')->default(0)
        ->string('known_service', 100)->nullable()
        // Cache metadata
        ->integer('lookup_count')->default(1)
        ->dateTime('last_lookup_at')->nullable()
        // Timestamps
        ->timestamps();
    
    // Create indexes for performance
    // Note: idx_ip_address is already created by ->unique() above
    $builder->index('idx_country_code', ['country_code'])
        ->index('idx_known_service', ['known_service'])
        ->index('idx_last_lookup_at', ['last_lookup_at']);
    
    // Create the table
    $builder->create();
    
    echo "✅ Table 'ip_geo_cache' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('ip_geo_cache');
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

