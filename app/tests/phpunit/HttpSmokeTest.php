<?php

namespace Tests\PhpUnit;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpSmokeTest extends TestCase
{
    public function testRootRequestReturnsRedirectOrPageWithoutFatalError(): void
    {
        $response = $this->runHttpRequest('GET', '/');

        self::assertContains($response['status'], [200, 301, 302]);
        self::assertSame('', $response['stderr']);
    }

    public function testDashboardGuestRequestIsRejected(): void
    {
        $response = $this->runHttpRequest('GET', '/sr/dashboard', [], 'text/html');

        self::assertSame(302, $response['status']);
        self::assertSame('', $response['stderr']);
    }

    public function testDynamicDatabasePageRendersWithoutFatalError(): void
    {
        $response = $this->runHttpRequest('GET', '/sr/o-autoru', [], 'text/html');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('O Autoru', $response['body']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);
    }

    public function testLoginPageRendersCsrfProtectedForm(): void
    {
        $response = $this->runHttpRequest('GET', '/sr/login', [], 'text/html');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('name="_csrf_token"', $response['body']);
        self::assertStringContainsString('name="csrf-token"', $response['body']);
        self::assertSame('', $response['stderr']);
    }

    public function testLogoutWithoutCsrfTokenIsRejected(): void
    {
        $response = $this->runHttpRequest('POST', '/sr/logout', [], 'text/html');

        self::assertSame(403, $response['status']);
        self::assertStringContainsString('CSRF token mismatch', $response['body']);
        self::assertSame('', $response['stderr']);
    }

    public function testLoginSubmitWithoutCsrfTokenIsRejected(): void
    {
        $response = $this->runHttpRequest('POST', '/sr/login', [], 'text/html');

        self::assertSame(403, $response['status']);
        self::assertStringContainsString('CSRF token mismatch', $response['body']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);
    }

    public function testRegisterSubmitWithoutCsrfTokenIsRejected(): void
    {
        $response = $this->runHttpRequest('POST', '/sr/register', [], 'text/html');

        self::assertSame(403, $response['status']);
        self::assertStringContainsString('CSRF token mismatch', $response['body']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);
    }

    public function testDashboardPageStoreRejectsGuestRequest(): void
    {
        $response = $this->runHttpRequest('POST', '/sr/dashboard/pages', [
            'title' => 'Smoke Page',
            'route' => '/smoke-page',
        ], 'text/html');

        self::assertSame(302, $response['status']);
        self::assertSame('', $response['stderr']);
    }

    public function testProtectedDashboardApiRejectsGuestJsonRequest(): void
    {
        $response = $this->runHttpRequest('GET', '/api/dashboard/users', [], 'application/json');

        self::assertSame(401, $response['status']);
        self::assertStringContainsString('Unauthorized', $response['body']);
        self::assertSame('', $response['stderr']);
    }

    public function testPublicApiLoginRejectsMissingCredentials(): void
    {
        $response = $this->runHttpRequest('POST', '/api/auth/login', [], 'application/json');

        self::assertSame(400, $response['status']);
        self::assertStringContainsString('Username and password are required', $response['body']);
        self::assertStringNotContainsString('Fatal error', $response['stderr']);
        self::assertStringNotContainsString('PHP Fatal error', $response['stderr']);
    }

    /**
     * @param array<string, mixed> $postData
     * @return array{status:int,body:string,stderr:string}
     */
    private function runHttpRequest(
        string $method,
        string $uri,
        array $postData = [],
        string $accept = 'text/html'
    ): array {
        $scriptPath = tempnam(sys_get_temp_dir(), 'ap_http_smoke_');
        if ($scriptPath === false) {
            throw new RuntimeException('Unable to create temporary smoke script.');
        }

        $postExport = var_export($postData, true);
        $methodExport = var_export($method, true);
        $uriExport = var_export($uri, true);
        $acceptExport = var_export($accept, true);
        $remoteAddressExport = var_export(
            '127.0.' . random_int(0, 255) . '.' . random_int(1, 254),
            true
        );

        $script = <<<PHP
<?php
putenv('APP_ENV=local');
putenv('APP_ENV_FILE=.env');

\$_SERVER['REQUEST_METHOD'] = {$methodExport};
\$_SERVER['REQUEST_URI'] = {$uriExport};
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['HTTP_ACCEPT'] = {$acceptExport};
\$_SERVER['REMOTE_ADDR'] = {$remoteAddressExport};
\$_POST = {$postExport};
\$_GET = [];
\$_FILES = [];

ob_start();
register_shutdown_function(static function (): void {
    \$body = ob_get_clean();
    echo json_encode([
        'status' => http_response_code(),
        'body' => is_string(\$body) ? \$body : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

require '/path/to/project/public_html/index.php';
PHP;

        file_put_contents($scriptPath, $script);

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open([PHP_BINARY, $scriptPath], $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($scriptPath);
            throw new RuntimeException('Unable to start smoke subprocess.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        @unlink($scriptPath);

        try {
            $payload = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Invalid smoke subprocess output: ' . (string) $stdout,
                0,
                $exception
            );
        }

        return [
            'status' => (int) ($payload['status'] ?? 0) ?: 200,
            'body' => (string) ($payload['body'] ?? ''),
            'stderr' => trim((string) $stderr),
        ];
    }
}
