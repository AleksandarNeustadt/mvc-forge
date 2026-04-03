<?php
/**
 * Migration: Create Permissions Table
 * 
 * This migration creates the permissions table for fine-grained access control
 * Run once with: php core/database/migrations/010_create_permissions_table.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Creating permissions table...\n";

// Check if table already exists
if (DatabaseTableBuilder::exists('permissions')) {
    echo "⚠️  Table 'permissions' already exists!\n";
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
    DatabaseTableBuilder::drop('permissions');
}

// Create permissions table
try {
    $builder = new DatabaseTableBuilder('permissions');
    
    // Primary key (ID)
    $builder->id()
        // Permission name and description
        ->string('name', 100)->unique()
        ->string('slug', 100)->unique()
        ->text('description')->nullable()
        // Permission category for grouping
        ->string('category', 50)->nullable() // e.g., 'users', 'blog', 'pages', 'system'
        // Timestamps
        ->timestamps();
    
    // Create the table
    $builder->create();
    
    echo "✅ Table 'permissions' created successfully!\n";
    echo "\n📊 Table structure:\n";
    
    // Show table info
    $columns = DatabaseBuilder::getTableColumns('permissions');
    foreach ($columns as $column) {
        $nullable = ($column['nullable'] ?? 'NO') === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['default_value'] ? " DEFAULT {$column['default_value']}" : '';
        echo "  - {$column['name']}: {$column['type']} {$nullable}{$default}\n";
    }
    
    // Insert default permissions
    echo "\n📝 Inserting default permissions...\n";
    
    $defaultPermissions = [
        // User Management
        ['name' => 'View Users', 'slug' => 'users.view', 'description' => 'View user list and details', 'category' => 'users'],
        ['name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Create new users', 'category' => 'users'],
        ['name' => 'Edit Users', 'slug' => 'users.edit', 'description' => 'Edit existing users', 'category' => 'users'],
        ['name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Delete users', 'category' => 'users'],
        ['name' => 'Manage User Roles', 'slug' => 'users.manage-roles', 'description' => 'Assign and manage user roles', 'category' => 'users'],
        ['name' => 'Manage Permissions', 'slug' => 'users.manage-permissions', 'description' => 'Manage role permissions', 'category' => 'users'],
        
        // Blog Management
        ['name' => 'View Blog Posts', 'slug' => 'blog.view', 'description' => 'View blog posts', 'category' => 'blog'],
        ['name' => 'Create Blog Posts', 'slug' => 'blog.create', 'description' => 'Create new blog posts', 'category' => 'blog'],
        ['name' => 'Edit Blog Posts', 'slug' => 'blog.edit', 'description' => 'Edit blog posts', 'category' => 'blog'],
        ['name' => 'Delete Blog Posts', 'slug' => 'blog.delete', 'description' => 'Delete blog posts', 'category' => 'blog'],
        ['name' => 'Publish Blog Posts', 'slug' => 'blog.publish', 'description' => 'Publish blog posts', 'category' => 'blog'],
        ['name' => 'Manage Blog Categories', 'slug' => 'blog.manage-categories', 'description' => 'Manage blog categories', 'category' => 'blog'],
        ['name' => 'Manage Blog Tags', 'slug' => 'blog.manage-tags', 'description' => 'Manage blog tags', 'category' => 'blog'],
        
        // Page Management
        ['name' => 'View Pages', 'slug' => 'pages.view', 'description' => 'View pages', 'category' => 'pages'],
        ['name' => 'Create Pages', 'slug' => 'pages.create', 'description' => 'Create new pages', 'category' => 'pages'],
        ['name' => 'Edit Pages', 'slug' => 'pages.edit', 'description' => 'Edit pages', 'category' => 'pages'],
        ['name' => 'Delete Pages', 'slug' => 'pages.delete', 'description' => 'Delete pages', 'category' => 'pages'],
        ['name' => 'Publish Pages', 'slug' => 'pages.publish', 'description' => 'Publish pages', 'category' => 'pages'],
        
        // System Management
        ['name' => 'Access Dashboard', 'slug' => 'system.dashboard', 'description' => 'Access dashboard', 'category' => 'system'],
        ['name' => 'Manage Database', 'slug' => 'system.database', 'description' => 'Manage database', 'category' => 'system'],
        ['name' => 'Manage Settings', 'slug' => 'system.settings', 'description' => 'Manage system settings', 'category' => 'system'],
        ['name' => 'Manage Languages', 'slug' => 'system.languages', 'description' => 'Manage site languages', 'category' => 'system'],
    ];
    
    foreach ($defaultPermissions as $permission) {
        Database::table('permissions')->insert($permission);
        echo "  ✅ Inserted permission: {$permission['name']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Migration completed!\n";

