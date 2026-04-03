<?php
/**
 * Seed: Create Admin User
 * 
 * This seed creates the initial admin user
 * Run once with: php core/database/seeds/001_create_admin_user.php
 */

require_once __DIR__ . '/../../../../public/index.php';

// Skip routing, just load necessary classes
if (!class_exists('Security')) {
    require_once __DIR__ . '/../Database.php';
    require_once __DIR__ . '/../../security/Security.php';
    require_once __DIR__ . '/../DatabaseTableBuilder.php';
    require_once __DIR__ . '/../../mvc/Model.php';
    require_once __DIR__ . '/../../../../mvc/models/User.php';
}

use Database;
use Security;
use User;

echo "👤 Creating admin user...\n\n";

// Check if users table exists
if (!DatabaseTableBuilder::exists('users')) {
    echo "❌ Table 'users' does not exist!\n";
    echo "💡 Run migration first: php core/database/migrations/001_create_users_table.php\n";
    exit(1);
}

// Get user input
echo "📝 Admin user details:\n";

// Email
echo "Email [admin@aleksandar.pro]: ";
$handle = fopen("php://stdin", "r");
$email = trim(fgets($handle));
if (empty($email)) {
    $email = 'admin@aleksandar.pro';
}
fclose($handle);

// Username
echo "Username [admin]: ";
$handle = fopen("php://stdin", "r");
$username = trim(fgets($handle));
if (empty($username)) {
    $username = 'admin';
}
fclose($handle);

// Password (hidden input - allow paste)
echo "Password: ";
$password = '';
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    // Unix/Linux - use stty to hide input
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
} else {
    // Windows - just read normally (can't hide easily)
    $password = trim(fgets(STDIN));
}

if (empty($password)) {
    echo "❌ Password is required!\n";
    exit(1);
}

// First name
echo "First name [Admin]: ";
$handle = fopen("php://stdin", "r");
$firstName = trim(fgets($handle));
if (empty($firstName)) {
    $firstName = 'Admin';
}
fclose($handle);

// Last name
echo "Last name [User]: ";
$handle = fopen("php://stdin", "r");
$lastName = trim(fgets($handle));
if (empty($lastName)) {
    $lastName = 'User';
}
fclose($handle);

try {
    // Check if user already exists
    if (User::emailExists($email)) {
        echo "⚠️  User with email '{$email}' already exists!\n";
        exit(1);
    }
    
    if (User::usernameExists($username)) {
        echo "⚠️  User with username '{$username}' already exists!\n";
        exit(1);
    }
    
    // Create user
    $user = User::createUser([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'newsletter' => false,
    ]);
    
    echo "\n✅ Admin user created successfully!\n";
    echo "📊 User details:\n";
    echo "   ID: {$user->id}\n";
    echo "   Username: {$user->username}\n";
    echo "   Email: {$user->email}\n";
    echo "   Slug: {$user->slug}\n";
    echo "   Name: {$user->first_name} {$user->last_name}\n\n";
    echo "🔐 You can now login with:\n";
    echo "   Email: {$email}\n";
    echo "   Password: [the password you entered]\n";
    
} catch (Exception $e) {
    echo "❌ Failed to create user!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

