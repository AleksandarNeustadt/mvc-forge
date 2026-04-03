<?php

namespace Tests\PhpUnit;

use App\Core\security\Security;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testPasswordHashingAndVerification(): void
    {
        $password = 'StrongPassword123!@#';

        $hash = Security::hashPassword($password);

        self::assertNotSame($password, $hash);
        self::assertTrue(Security::verifyPassword($password, $hash));
        self::assertFalse(Security::verifyPassword('wrong-password', $hash));
    }

    public function testStrongAndWeakPasswordValidation(): void
    {
        self::assertNotEmpty(Security::validatePasswordStrength('password', 8));
        self::assertSame([], Security::validatePasswordStrength('StrongPassword123!@#', 8));
    }

    public function testSanitizationAndEscaping(): void
    {
        self::assertSame('hello world', Security::sanitizeString("  hello   world  \0"));
        self::assertSame('script', Security::sanitizeFilename('../.script>alert(1)</script'));
        self::assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', Security::escape('<script>alert("x")</script>'));
    }
}
