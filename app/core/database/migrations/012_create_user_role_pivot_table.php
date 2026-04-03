<?php
/**
 * Migration: Create User-Role Pivot Table
 * 
 * This migration creates the pivot table for many-to-many relationship
 * between users and roles
 * Run once with: php core/database/migrations/012_create_user_role_pivot_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating user_role pivot table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('user_role')) {
    echo "⚠️  Table 'user_role' already exists!\n";
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
    DatabaseTableBuilder::drop('user_role');
}

// Create user_role pivot table
try {
    $builder = new DatabaseTableBuilder('user_role');
    
    // Foreign keys
    $builder->integer('user_id')
        ->integer('role_id')
        // Timestamps
        ->timestamps();
    
    // Create the table
    $builder->create();
    
    // Add composite primary key using direct SQL
    Database::execute("ALTER TABLE user_role ADD PRIMARY KEY (user_id, role_id)");
    
    echo "✅ Table 'user_role' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('user_role');
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

