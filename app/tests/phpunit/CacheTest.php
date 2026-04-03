<?php

namespace Tests\PhpUnit;

use App\Core\cache\Cache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    public function testSetGetForgetLifecycle(): void
    {
        $key = 'phpunit_cache_' . bin2hex(random_bytes(8));
        $value = ['answer' => 42, 'label' => 'smoke'];

        self::assertTrue(Cache::set($key, $value, 60));
        self::assertSame($value, Cache::get($key));

        self::assertTrue(Cache::forget($key));
        self::assertNull(Cache::get($key));
    }

    public function testRememberReturnsGeneratedValue(): void
    {
        $key = 'phpunit_remember_' . bin2hex(random_bytes(8));

        $value = Cache::remember($key, static fn(): array => ['generated' => true], 60);

        self::assertSame(['generated' => true], $value);
        self::assertSame(['generated' => true], Cache::get($key));

        Cache::forget($key);
    }
}
