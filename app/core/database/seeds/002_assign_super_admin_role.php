<?php
/**
 * Seed: Assign Super Admin Role to User
 * 
 * This seed assigns the super-admin role to a user
 * Run with: php core/database/seeds/002_assign_super_admin_role.php
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../../mvc/Model.php';
require_once __DIR__ . '/../../../mvc/models/User.php';
require_once __DIR__ . '/../../../mvc/models/Role.php';

echo "👤 Assigning Super Admin Role...\n\n";

// Check if tables exist
if (!DatabaseTableBuilder::exists('users')) {
    echo "❌ Table 'users' does not exist!\n";
    exit(1);
}

if (!DatabaseTableBuilder::exists('roles')) {
    echo "❌ Table 'roles' does not exist!\n";
    echo "💡 Run migration first: php core/database/migrations/009_create_roles_table.php\n";
    exit(1);
}

if (!DatabaseTableBuilder::exists('user_role')) {
    echo "❌ Table 'user_role' does not exist!\n";
    echo "💡 Run migration first: php core/database/migrations/012_create_user_role_pivot_table.php\n";
    exit(1);
}

// Get super-admin role
$superAdminRole = Role::findBySlug('super-admin');

if (!$superAdminRole) {
    echo "❌ Super Admin role not found!\n";
    echo "💡 Run migration first: php core/database/migrations/009_create_roles_table.php\n";
    exit(1);
}

// List all users
echo "📋 Available users:\n";
$users = User::all();
if (empty($users)) {
    echo "❌ No users found!\n";
    exit(1);
}

foreach ($users as $index => $user) {
    $hasRole = $user->hasRole('super-admin');
    $status = $hasRole ? '✅ (already has super-admin)' : '';
    echo "  " . ($index + 1) . ". ID: {$user->id} | Username: {$user->username} | Email: {$user->email} {$status}\n";
}

// Get user input
echo "\n📝 Enter user ID to assign Super Admin role: ";
$handle = fopen("php://stdin", "r");
$userId = trim(fgets($handle));
fclose($handle);

if (empty($userId) || !is_numeric($userId)) {
    echo "❌ Invalid user ID!\n";
    exit(1);
}

$user = User::find((int)$userId);

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

// Check if user already has super-admin role
if ($user->hasRole('super-admin')) {
    echo "⚠️  User already has Super Admin role!\n";
    exit(0);
}

// Assign role
try {
    $user->attachRole($superAdminRole);
    echo "✅ Super Admin role assigned to user: {$user->username} ({$user->email})\n";
} catch (Exception $e) {
    echo "❌ Error assigning role: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Done!\n";

