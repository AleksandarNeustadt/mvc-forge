<?php

namespace App\Core\middleware;


use App\Core\http\Request;
use App\Core\security\RateLimiter;use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Rate Limit Middleware
 *
 * Apply rate limiting to routes.
 *
 * Usage in routes:
 *   Route::post('/api/login', [AuthController::class, 'login'])
 *       ->middleware('ratelimit:5,60'); // 5 attempts per 60 seconds
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts, $this->decaySeconds)) {
            return $this->buildResponse($key);
        }

        RateLimiter::hit($key);

        // Add rate limit headers BEFORE calling next middleware/controller
        // This prevents "headers already sent" errors
        if (!headers_sent()) {
            $this->addHeaders($key);
        }

        $response = $next($request);

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    private function resolveRequestSignature(Request $request): string
    {
        $uri = $request->path();
        $method = $request->method();
        $identity = 'ip:' . $request->ip();

        if (!empty($_SESSION['user_id'])) {
            $identity = 'user:' . (int) $_SESSION['user_id'];
        } else {
            $authorizationHeader = trim((string) $request->header('authorization', ''));
            if ($authorizationHeader !== '') {
                $identity = 'auth:' . substr(hash('sha256', $authorizationHeader), 0, 16);
            } elseif ($request->query('api_token')) {
                $identity = 'query-token:' . substr(hash('sha256', (string) $request->query('api_token')), 0, 16);
            }
        }

        return "request:{$method}:{$uri}:{$identity}";
    }

    /**
     * Build rate limit exceeded response
     */
    private function buildResponse(string $key): void
    {
        $retryAfter = RateLimiter::availableIn($key, $this->decaySeconds);

        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . $this->maxAttempts);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . (time() + $retryAfter));

        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter
            ]);
        } else {
            echo '<h1>429 Too Many Requests</h1>';
            echo '<p>Please try again in ' . $retryAfter . ' seconds.</p>';
        }

        exit;
    }

    /**
     * Add rate limit headers to response
     */
    private function addHeaders(string $key): void
    {
        // Check if headers are already sent to prevent errors
        if (headers_sent()) {
            return;
        }

        $remaining = RateLimiter::remaining($key, $this->maxAttempts, $this->decaySeconds);
        $resetTime = time() + $this->decaySeconds;

        @header('X-RateLimit-Limit: ' . $this->maxAttempts);
        @header('X-RateLimit-Remaining: ' . $remaining);
        @header('X-RateLimit-Reset: ' . $resetTime);
    }

    /**
     * Check if request wants JSON response
     */
    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}


if (!\class_exists('RateLimitMiddleware', false) && !\interface_exists('RateLimitMiddleware', false) && !\trait_exists('RateLimitMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\RateLimitMiddleware', 'RateLimitMiddleware');
}
