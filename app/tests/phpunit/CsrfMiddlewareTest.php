<?php

namespace Tests\PhpUnit;

use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsrfMiddlewareTest extends TestCase
{
    public function testPostRequestWithoutTokenIsRejected(): void
    {
        $scriptPath = tempnam(sys_get_temp_dir(), 'ap_csrf_test_');
        if ($scriptPath === false) {
            throw new RuntimeException('Unable to create temporary CSRF script.');
        }

        $script = <<<'PHP'
<?php
require '/path/to/project/app/tests/bootstrap.php';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/sr/profile';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_POST = [];

$request = new \App\Core\http\Request();
$middleware = new \App\Core\middleware\CsrfMiddleware();

ob_start();
register_shutdown_function(static function (): void {
    $body = ob_get_clean();
    echo json_encode([
        'status' => http_response_code(),
        'body' => is_string($body) ? $body : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

$middleware->handle($request, static fn() => 'next');
PHP;

        file_put_contents($scriptPath, $script);

        $process = proc_open([PHP_BINARY, $scriptPath], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            @unlink($scriptPath);
            throw new RuntimeException('Unable to start CSRF subprocess.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        @unlink($scriptPath);

        try {
            $payload = json_decode((string) $stdout, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid CSRF subprocess output: ' . (string) $stdout, 0, $exception);
        }

        self::assertSame(403, (int) ($payload['status'] ?? 0));
        self::assertStringContainsString('csrf_token_invalid', (string) ($payload['body'] ?? ''));
        self::assertStringNotContainsString('Fatal error', $stderr);
        self::assertStringNotContainsString('PHP Fatal error', $stderr);
    }
}
