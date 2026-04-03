<?php
/**
 * Approve users who have verified their email but are still pending
 * 
 * Run with: php approve-verified-users.php
 */

// Load environment first
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load required classes
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';
require_once __DIR__ . '/core/database/DatabaseBuilder.php';
require_once __DIR__ . '/core/logging/Logger.php';
require_once __DIR__ . '/mvc/models/Model.php';
require_once __DIR__ . '/mvc/models/User.php';

echo "🔍 Finding users with verified emails but pending status...\n\n";

try {
    // Find users who have email_verified_at set but status is still 'pending'
    $users = Database::table('users')
        ->where('status', 'pending')
        ->whereNotNull('email_verified_at')
        ->get();
    
    if (empty($users)) {
        echo "✅ No users found that need approval.\n";
        echo "All verified users are already approved.\n";
        exit(0);
    }
    
    echo "📋 Found " . count($users) . " user(s) to approve:\n\n";
    
    $approved = 0;
    foreach ($users as $userData) {
        $user = User::find($userData['id']);
        if ($user) {
            echo "  - User ID {$user->id}: {$user->email} ({$user->username})\n";
            
            // Approve user
            $user->approved_at = time();
            $user->status = 'active';
            if ($user->save()) {
                $approved++;
                echo "    ✅ Approved!\n";
            } else {
                echo "    ❌ Failed to approve\n";
            }
        }
    }
    
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Approved {$approved} user(s) successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

