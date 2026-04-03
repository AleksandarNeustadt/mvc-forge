<?php
/**
 * Migration: Create Role-Permission Pivot Table
 * 
 * This migration creates the pivot table for many-to-many relationship
 * between roles and permissions
 * Run once with: php core/database/migrations/011_create_role_permission_pivot_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating role_permission pivot table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('role_permission')) {
    echo "⚠️  Table 'role_permission' already exists!\n";
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
    DatabaseTableBuilder::drop('role_permission');
}

// Create role_permission pivot table
try {
    $builder = new DatabaseTableBuilder('role_permission');
    
    // Foreign keys
    $builder->integer('role_id')
        ->integer('permission_id')
        // Timestamps
        ->timestamps();
    
    // Create the table
    $builder->create();
    
    // Add composite primary key using direct SQL
    Database::execute("ALTER TABLE role_permission ADD PRIMARY KEY (role_id, permission_id)");
    
    echo "✅ Table 'role_permission' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('role_permission');
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

