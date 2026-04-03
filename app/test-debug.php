<?php
/**
 * Test Debug Functions (dd and dp)
 * 
 * Usage: php test-debug.php
 * Or open in browser
 */

// Load environment
require_once __DIR__ . '/core/config/Env.php';
Env::load(__DIR__ . '/.env');

// Load Debug class and helpers
require_once __DIR__ . '/core/debug/Debug.php';
require_once __DIR__ . '/core/helpers.php';

// Test data
$testData = [
    'string' => 'Hello World',
    'number' => 42,
    'boolean' => true,
    'array' => [
        'nested' => [
            'deep' => 'value'
        ],
        'list' => [1, 2, 3]
    ],
    'null' => null
];

$user = (object)[
    'id' => 1,
    'name' => 'Aleksandar',
    'email' => 'test@example.com'
];

echo "<h1>Debug Functions Test</h1>";
echo "<p>APP_DEBUG: " . (Env::get('APP_DEBUG') ? 'true' : 'false') . "</p>";

echo "<h2>Test 1: dp() - Dump and Print (doesn't stop execution)</h2>";
dp('Single string value');
dp($testData);
dp($user);

echo "<h2>Test 2: dd() - Dump and Die (stops execution)</h2>";
echo "<p>This should be the last output before dd() stops execution:</p>";
dd([
    'message' => 'This is dd() - execution stops here!',
    'data' => $testData,
    'user' => $user
]);

echo "<p>This line should never be reached because dd() stops execution.</p>";

