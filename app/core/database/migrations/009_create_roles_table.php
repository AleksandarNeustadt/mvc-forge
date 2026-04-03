<?php
/**
 * Migration: Create Roles Table
 * 
 * This migration creates the roles table for user role management
 * Run once with: php core/database/migrations/009_create_roles_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating roles table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('roles')) {
    echo "⚠️  Table 'roles' already exists!\n";
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
    DatabaseTableBuilder::drop('roles');
}

// Create roles table
try {
    $builder = new DatabaseTableBuilder('roles');
    
    // Primary key (ID)
    $builder->id()
        // Role name and description
        ->string('name', 100)->unique()
        ->string('slug', 100)->unique()
        ->text('description')->nullable()
        // Role settings
        ->boolean('is_system')->default(0) // System roles cannot be deleted
        ->integer('priority')->default(0) // Lower number = higher priority
        // Timestamps
        ->timestamps();
    
    // Create the table
    $builder->create();
    
    echo "✅ Table 'roles' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('roles');
    foreach ($columns as $column) {
        $nullable = ($column['nullable'] ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['default_value'] ? " DEFAULT {$column['default_value']}" : '';
        echo "  - {$column['name']}: {$column['type']} {$nullable}{$default}\n";
    }
    
    // Insert default roles
    echo "\n📝 Inserting default roles...\n";
    
    $defaultRoles = [
        ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full system access', 'is_system' => 1, 'priority' => 1],
        ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'is_system' => 1, 'priority' => 10],
        ['name' => 'Editor', 'slug' => 'editor', 'description' => 'Content editing access', 'is_system' => 1, 'priority' => 20],
        ['name' => 'Author', 'slug' => 'author', 'description' => 'Content creation access', 'is_system' => 1, 'priority' => 30],
        ['name' => 'User', 'slug' => 'user', 'description' => 'Basic user access', 'is_system' => 1, 'priority' => 100],
    ];
    
    foreach ($defaultRoles as $role) {
        Database::table('roles')->insert($role);
        echo "  ✅ Inserted role: {$role['name']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Migration completed!\n";

