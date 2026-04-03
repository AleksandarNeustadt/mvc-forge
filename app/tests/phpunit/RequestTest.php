<?php

namespace Tests\PhpUnit;

use App\Core\http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private array $serverBackup = [];
    private array $getBackup = [];
    private array $postBackup = [];
    private array $filesBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->filesBackup = $_FILES;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_FILES = $this->filesBackup;
    }

    public function testRequestParsesMethodUriInputHeadersAndClientMetadata(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/sr/kontakt?ref=hero',
            'HTTP_HOST' => 'localhost',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10, 10.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
            'HTTP_REFERER' => 'https://example.test/source',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ];
        $_GET = ['ref' => 'hero'];
        $_POST = ['name' => 'Aleksandar'];
        $_FILES = ['avatar' => ['name' => 'avatar.png']];

        $request = new Request();

        self::assertSame('POST', $request->method());
        self::assertSame('/sr/kontakt', $request->uri());
        self::assertSame('hero', $request->query('ref'));
        self::assertSame('Aleksandar', $request->post('name'));
        self::assertSame('Aleksandar', $request->input('name'));
        self::assertTrue($request->has('ref'));
        self::assertSame('avatar.png', $request->files('avatar')['name']);
        self::assertSame('application/json', $request->header('accept'));
        self::assertTrue($request->isAjax());
        self::assertTrue($request->wantsJson());
        self::assertSame('203.0.113.10', $request->ip());
        self::assertSame('PHPUnit', $request->userAgent());
        self::assertSame('https://example.test/source', $request->referer());
        self::assertTrue($request->isSecure());
        self::assertSame('https://localhost/sr/kontakt?ref=hero', $request->fullUrl());
        self::assertSame('https://localhost/sr/kontakt', $request->url());
    }
}
