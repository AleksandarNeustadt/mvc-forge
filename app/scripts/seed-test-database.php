<?php

require_once __DIR__ . '/../tests/bootstrap.php';

use Tests\Support\TestDatabaseManager;

echo "Resetting and seeding test database...\n";

$fixtures = TestDatabaseManager::resetAndSeed();

echo "Test database seed completed.\n";
echo "Admin user ID: {$fixtures['admin_user_id']}\n";
echo "Language ID: {$fixtures['language_id']}\n";
echo "Page ID: {$fixtures['page_id']}\n";
echo "API token: {$fixtures['api_token']}\n";
