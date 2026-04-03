<?php

namespace Tests\PhpUnit;

use App\Controllers\DashboardController;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LegacyContractTest extends TestCase
{
    public function testLegacyAliasesAndHelpersStayAvailable(): void
    {
        self::assertTrue(class_exists('User'));
        self::assertInstanceOf(User::class, new \User());
        self::assertSame('test-user-name', \str_slug('Test User Name'));
    }

    public function testDashboardControllerStillExposesLegacyValidationMethod(): void
    {
        $controller = new DashboardController();
        $reflection = new ReflectionClass($controller);

        self::assertTrue($reflection->hasMethod('validateUserUniqueness'));
    }
}
