<?php
/**
 * Setup Initial Database
 * 
 * This script creates the users table and initial admin user
 * Run: php setup-initial-database.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';
require_once __DIR__ . '/core/database/DatabaseTableBuilder.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';
require_once __DIR__ . '/core/security/Security.php';
require_once __DIR__ . '/core/helpers.php'; // For str_slug()
require_once __DIR__ . '/core/mvc/Model.php';
require_once __DIR__ . '/mvc/models/User.php';

echo "🚀 Setting up initial database...\n\n";

// Step 1: Create users table
echo "📋 Step 1: Creating users table...\n";

if (DatabaseTableBuilder::exists('users')) {
    echo "⚠️  Table 'users' already exists!\n";
    echo "❓ Drop and recreate? (y/N): ";
    $answer = trim(strtolower(fgets(STDIN)));
    
    if ($answer === 'y' || $answer === 'yes') {
        echo "🗑️  Dropping existing table...\n";
        DatabaseTableBuilder::drop('users');
    } else {
        echo "⏭️  Skipping table creation.\n";
        goto seed_user;
    }
}

try {
    $builder = new DatabaseTableBuilder('users');
    
    // Primary key
    $builder->id()
        // Authentication
        ->string('username', 100)->unique()
        ->string('email', 255)->unique()
        ->string('password_hash', 255)
        // Personal info
        ->string('first_name', 100)->nullable()
        ->string('last_name', 100)->nullable()
        ->string('slug', 255)->unique()->nullable()
        ->string('avatar', 500)->nullable()
        // Settings
        ->boolean('newsletter')->default(0)
        // Verification
        ->integer('email_verified_at')->nullable()
        // Login tracking
        ->integer('last_login_at')->nullable()
        ->string('last_login_ip', 45)->nullable()
        // Timestamps
        ->timestamps();
    
    $builder->create();
    echo "✅ Table 'users' created successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Create admin user
seed_user:
echo "👤 Step 2: Creating admin user...\n";

$existingUser = User::findByEmail('admin@aleksandar.pro');

if ($existingUser) {
    echo "⚠️  Admin user already exists!\n";
    echo "❓ Update password? (y/N): ";
    $answer = trim(strtolower(fgets(STDIN)));
    
    if ($answer === 'y' || $answer === 'yes') {
        echo "🔑 New password: ";
        $password = trim(fgets(STDIN));
        if (!empty($password)) {
            $existingUser->updatePassword($password);
            echo "✅ Password updated!\n";
        }
    }
    exit(0);
}

// Get admin details
echo "\n📝 Admin user details:\n";

echo "Email [admin@aleksandar.pro]: ";
$email = trim(fgets(STDIN));
$email = !empty($email) ? $email : 'admin@aleksandar.pro';

echo "Username [admin]: ";
$username = trim(fgets(STDIN));
$username = !empty($username) ? $username : 'admin';

echo "Password: ";
$password = trim(fgets(STDIN));
if (empty($password)) {
    echo "❌ Password required!\n";
    exit(1);
}

echo "First name [Admin]: ";
$firstName = trim(fgets(STDIN));
$firstName = !empty($firstName) ? $firstName : 'Admin';

echo "Last name [User]: ";
$lastName = trim(fgets(STDIN));
$lastName = !empty($lastName) ? $lastName : 'User';

try {
    $userData = [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'newsletter' => false,
        'email_verified_at' => time(), // Integer timestamp
    ];
    
    $user = User::createUser($userData);
    
    if ($user && $user->id) {
        echo "\n✅ Admin user created!\n";
        echo "📋 Login credentials:\n";
        echo "   Email: {$email}\n";
        echo "   Username: {$username}\n";
        echo "   Password: [hidden]\n\n";
        echo "💡 Login at: /sr/login\n";
    } else {
        echo "❌ Failed to create user!\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Database setup completed!\n";

