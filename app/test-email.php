<?php
/**
 * Test Email Service
 * 
 * Usage: php test-email.php
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("❌ Composer autoloader not found. Run 'composer install' first.\n");
}

// Load environment
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load EmailService
require_once __DIR__ . '/core/services/EmailService.php';
require_once __DIR__ . '/core/logging/Logger.php';

echo "🧪 Testing Email Service...\n\n";

// Check configuration
echo "📋 Configuration:\n";
echo "  MAIL_USE_SMTP: " . (Env::get('MAIL_USE_SMTP', false) ? 'true' : 'false') . "\n";
echo "  MAIL_HOST: " . Env::get('MAIL_HOST', 'not set') . "\n";
echo "  MAIL_PORT: " . Env::get('MAIL_PORT', 'not set') . "\n";
echo "  MAIL_AUTH: " . (Env::get('MAIL_AUTH', false) ? 'true' : 'false') . "\n";
echo "  MAIL_ENCRYPTION: " . (Env::get('MAIL_ENCRYPTION', '') ?: 'not set (no encryption)') . "\n";
echo "  MAIL_FROM_ADDRESS: " . Env::get('MAIL_FROM_ADDRESS', 'not set') . "\n";
echo "  APP_URL: " . Env::get('APP_URL', 'not set') . "\n";
echo "\n";

// Check if MailHog is running
$host = Env::get('MAIL_HOST', 'localhost');
$port = (int) Env::get('MAIL_PORT', 1025);
echo "🔌 Testing connection to {$host}:{$port}...\n";

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✅ Connection successful!\n";
    fclose($connection);
} else {
    echo "❌ Connection failed: {$errstr} ({$errno})\n";
    echo "💡 Make sure MailHog is running:\n";
    echo "   ./start-mailhog.sh\n";
    echo "   or\n";
    echo "   mailhog\n";
    exit(1);
}

echo "\n";

// Create test user object
$testUser = new stdClass();
$testUser->id = 999;
$testUser->email = 'test@example.com';
$testUser->first_name = 'Test';
$testUser->last_name = 'User';

// Test sending email
echo "📧 Sending test email...\n";
$result = EmailService::send(
    'test@example.com',
    'Test Email from aleksandar.pro',
    '<h1>Test Email</h1><p>This is a test email from the EmailService.</p>',
    'html'
);

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "💡 Check MailHog UI: http://localhost:8025\n";
} else {
    echo "❌ Email sending failed!\n";
    echo "💡 Check logs: storage/logs/error.log\n";
    echo "💡 Check MailHog UI: http://localhost:8025\n";
}

echo "\n";

