<?php

namespace Tests\PhpUnit;

use App\Core\routing\RouteCollection;
use App\Core\routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private array $serverBackup = [];
    private array $postBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->postBackup = $_POST;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
    }

    public function testLanguagePrefixIsExtractedFromRegularRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/sr/o-autoru?preview=1';
        $_POST = [];

        $router = new Router(new RouteCollection());

        self::assertSame('sr', $router->getLanguage());
        self::assertSame('/o-autoru', $router->getUri());
        self::assertSame('GET', $router->getMethod());
    }

    public function testApiRouteKeepsApiPrefixAndUsesDefaultLanguage(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/status';
        $_POST = [];

        $router = new Router(new RouteCollection());

        self::assertSame('sr', $router->getLanguage());
        self::assertSame('/api/status', $router->getUri());
        self::assertSame('GET', $router->getMethod());
    }

    public function testLanguagePrefixedApiRouteIsNormalizedToApiRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/en/api/status';
        $_POST = [];

        $router = new Router(new RouteCollection());

        self::assertSame('sr', $router->getLanguage());
        self::assertSame('/api/status', $router->getUri());
    }

    public function testPostMethodCanBeSpoofedToDelete(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/sr/dashboard/pages/10';
        $_POST = ['_method' => 'DELETE'];

        $router = new Router(new RouteCollection());

        self::assertSame('DELETE', $router->getMethod());
        self::assertSame('/dashboard/pages/10', $router->getUri());
    }
}
