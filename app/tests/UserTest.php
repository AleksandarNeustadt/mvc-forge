<?php

namespace Tests;

/**
 * Unit Tests for User Model
 * 
 * Run with: php tests/UserTest.php
 */
class UserTest
{
    private static array $testResults = [];
    
    public static function run(): void
    {
        echo "Running User Model Tests...\n\n";
        
        self::testPasswordHashing();
        self::testPasswordVerification();
        self::testSlugGeneration();
        self::testUserStatus();
        self::testSoftDelete();
        
        self::printResults();
    }
    
    private static function testPasswordHashing(): void
    {
        $password = 'TestPassword123!@#';
        $hash = \Security::hashPassword($password);
        
        self::assert(
            !empty($hash) && strlen($hash) > 50,
            'Password hashing',
            'Password should be hashed'
        );
        
        self::assert(
            $hash !== $password,
            'Password hash security',
            'Hash should not equal original password'
        );
    }
    
    private static function testPasswordVerification(): void
    {
        $password = 'TestPassword123!@#';
        $hash = \Security::hashPassword($password);
        
        self::assert(
            \Security::verifyPassword($password, $hash),
            'Password verification - correct',
            'Correct password should verify'
        );
        
        self::assert(
            !\Security::verifyPassword('WrongPassword', $hash),
            'Password verification - incorrect',
            'Incorrect password should not verify'
        );
    }
    
    private static function testSlugGeneration(): void
    {
        $slug = \str_slug('Test User Name');
        
        self::assert(
            $slug === 'test-user-name',
            'Slug generation',
            'Slug should be lowercase with hyphens'
        );
    }
    
    private static function testUserStatus(): void
    {
        $user = new \User();
        $user->status = 'active';
        
        self::assert(
            $user->isApproved(),
            'User status - active',
            'Active user should be approved'
        );
        
        $user->status = 'banned';
        self::assert(
            $user->isBanned(),
            'User status - banned',
            'Banned user should be banned'
        );
        
        $user->status = 'pending';
        self::assert(
            $user->isPending(),
            'User status - pending',
            'Pending user should be pending'
        );
    }
    
    private static function testSoftDelete(): void
    {
        $user = new \User();
        $user->deleted_at = null;
        
        self::assert(
            !$user->isDeleted(),
            'Soft delete - not deleted',
            'User without deleted_at should not be deleted'
        );
        
        $user->deleted_at = time();
        self::assert(
            $user->isDeleted(),
            'Soft delete - deleted',
            'User with deleted_at should be deleted'
        );
    }
    
    private static function assert(bool $condition, string $testName, string $description): void
    {
        $result = [
            'name' => $testName,
            'description' => $description,
            'passed' => $condition
        ];
        
        self::$testResults[] = $result;
        
        echo ($condition ? '✓' : '✗') . " {$testName}\n";
        if (!$condition) {
            echo "  Failed: {$description}\n";
        }
    }
    
    private static function printResults(): void
    {
        $passed = count(array_filter(self::$testResults, fn($r) => $r['passed']));
        $total = count(self::$testResults);
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Results: {$passed}/{$total} tests passed\n";
        echo str_repeat('=', 50) . "\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/bootstrap.php';
    UserTest::run();
}

