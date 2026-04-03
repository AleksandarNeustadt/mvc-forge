<?php

namespace Tests\PhpUnit;

use App\Controllers\PageController;
use App\Core\routing\DynamicRouteRegistry;
use App\Models\Page;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DynamicRouteRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        DynamicRouteRegistry::clearCache();
        $this->markRegistryAsLoaded();
    }

    protected function tearDown(): void
    {
        DynamicRouteRegistry::clearCache();
    }

    public function testManualRouteRegistrationIsNormalizedAndDiscoverable(): void
    {
        DynamicRouteRegistry::register('demo/route/', [PageController::class, 'show'], [
            'name' => 'demo.route',
            'methods' => ['GET'],
        ]);

        self::assertTrue(DynamicRouteRegistry::hasRoute('/demo/route'));

        $route = DynamicRouteRegistry::findRoute('demo/route/');

        self::assertNotNull($route);
        self::assertSame('/demo/route', $route['uri']);
        self::assertSame([PageController::class, 'show'], $route['handler']);
        self::assertSame('demo.route', $route['name']);
        self::assertSame(['GET'], $route['methods']);
    }

    public function testUnregisterRemovesRoute(): void
    {
        DynamicRouteRegistry::register('/temporary-page', [PageController::class, 'show']);

        self::assertTrue(DynamicRouteRegistry::hasRoute('/temporary-page'));

        DynamicRouteRegistry::unregister('temporary-page/');

        self::assertFalse(DynamicRouteRegistry::hasRoute('/temporary-page'));
    }

    public function testFallbackRouteUsesPageControllerWhenNoExactRouteExists(): void
    {
        $route = DynamicRouteRegistry::findRoute('/blog/custom-path');

        self::assertNotNull($route);
        self::assertSame('/blog/custom-path', $route['uri']);
        self::assertSame([PageController::class, 'show'], $route['handler']);
        self::assertSame('page.fallback', $route['name']);
        self::assertSame(['GET'], $route['methods']);
    }

    public function testRegisterFromPageSkipsInactivePageAndRegistersActivePage(): void
    {
        $inactivePage = new Page([
            'route' => '/inactive-page',
            'is_active' => 0,
        ]);

        DynamicRouteRegistry::registerFromPage($inactivePage);
        self::assertFalse(DynamicRouteRegistry::hasRoute('/inactive-page'));

        $activePage = new Page([
            'route' => '/active-page',
            'is_active' => 1,
        ]);

        DynamicRouteRegistry::registerFromPage($activePage);

        self::assertTrue(DynamicRouteRegistry::hasRoute('/active-page'));
        self::assertSame('page.active_page', DynamicRouteRegistry::findRoute('/active-page')['name'] ?? null);
    }

    private function markRegistryAsLoaded(): void
    {
        $ref = new ReflectionClass(DynamicRouteRegistry::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setValue(null, true);
    }
}
