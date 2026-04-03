<?php

namespace Tests\PhpUnit;

use App\Core\security\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '198.51.100.77';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testRateLimiterBlocksAfterLimitAndCanBeCleared(): void
    {
        $key = 'phpunit_rate_limit_' . bin2hex(random_bytes(8));
        RateLimiter::clear($key);

        self::assertTrue(RateLimiter::attempt($key, 2, 60));
        self::assertTrue(RateLimiter::attempt($key, 2, 60));
        self::assertTrue(RateLimiter::tooManyAttempts($key, 2, 60));
        self::assertSame(0, RateLimiter::remaining($key, 2, 60));
        self::assertFalse(RateLimiter::attempt($key, 2, 60));

        RateLimiter::clear($key);

        self::assertFalse(RateLimiter::tooManyAttempts($key, 2, 60));
        self::assertSame(2, RateLimiter::remaining($key, 2, 60));
    }
}
