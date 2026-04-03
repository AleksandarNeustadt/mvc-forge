<?php

namespace Tests;

/**
 * Integration Tests for DashboardController
 * 
 * Run with: php tests/DashboardControllerTest.php
 */
class DashboardControllerTest
{
    private static array $testResults = [];
    
    public static function run(): void
    {
        echo "Running DashboardController Integration Tests...\n\n";
        
        self::testPasswordPolicy();
        self::testUserUniquenessValidation();
        self::testCacheIntegration();
        
        self::printResults();
    }
    
    private static function testPasswordPolicy(): void
    {
        // Test weak password
        $weakPassword = 'password';
        $errors = \Security::validatePasswordStrength($weakPassword, 8);
        
        self::assert(
            !empty($errors),
            'Password policy - weak password',
            'Weak password should fail validation'
        );
        
        // Test strong password
        $strongPassword = 'StrongPassword123!@#';
        $errors = \Security::validatePasswordStrength($strongPassword, 8);
        
        self::assert(
            empty($errors),
            'Password policy - strong password',
            'Strong password should pass validation'
        );
    }
    
    private static function testUserUniquenessValidation(): void
    {
        // This would require database connection
        // For now, just test the method exists
        $controller = new \DashboardController();
        $reflection = new \ReflectionClass($controller);
        
        self::assert(
            $reflection->hasMethod('validateUserUniqueness'),
            'User uniqueness validation method',
            'validateUserUniqueness method should exist'
        );
    }
    
    private static function testCacheIntegration(): void
    {
        $testKey = 'test_cache_key_' . time();
        $testValue = ['test' => 'data'];
        
        // Test set
        $set = \Cache::set($testKey, $testValue, 60);
        self::assert($set, 'Cache set', 'Cache set should succeed');
        
        // Test get
        $get = \Cache::get($testKey);
        self::assert(
            $get === $testValue,
            'Cache get',
            'Cache get should return stored value'
        );
        
        // Test forget
        \Cache::forget($testKey);
        $getAfterForget = \Cache::get($testKey);
        self::assert(
            $getAfterForget === null,
            'Cache forget',
            'Cache forget should remove value'
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
    DashboardControllerTest::run();
}

