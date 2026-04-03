<?php

namespace App\Core\middleware;


use App\Core\http\Request;
use App\Core\logging\Logger;
use App\Core\security\CSRF;use BadMethodCallException;
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
 * CSRF Middleware
 *
 * Verifies CSRF token on state-changing requests (POST, PUT, PATCH, DELETE).
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Methods that require CSRF verification
     */
    private array $verifyMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * URIs to exclude from CSRF verification
     */
    private array $except = [
        // '/api/webhooks/*',
    ];

    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        $this->debugLog("CsrfMiddleware::handle() - Method: {$request->method()}, URI: {$request->uri()}");
        
        // Skip non-state-changing methods
        if (!in_array($request->method(), $this->verifyMethods)) {
            $this->debugLog("CsrfMiddleware::handle() - Skipping CSRF check (not state-changing method)");
            return $next($request);
        }

        // Skip excluded URIs
        if ($this->isExcluded($request)) {
            $this->debugLog("CsrfMiddleware::handle() - Skipping CSRF check (excluded URI)");
            return $next($request);
        }

        // Verify CSRF token
        $csrfValid = CSRF::verify();
        $this->debugLog("CsrfMiddleware::handle() - CSRF verification: " . ($csrfValid ? 'PASSED' : 'FAILED'));
        
        if (!$csrfValid) {
            return $this->handleFailure($request);
        }

        return $next($request);
    }

    /**
     * Check if URI is excluded from CSRF verification
     */
    private function isExcluded(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->except as $pattern) {
            // Convert wildcard pattern to regex
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            if (preg_match('/^' . $regex . '$/', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle CSRF verification failure
     */
    private function handleFailure(Request $request): void
    {
        http_response_code(403);

        if ($request->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token mismatch.',
                'error' => 'csrf_token_invalid'
            ]);
        } else {
            echo '<h1>403 Forbidden</h1>';
            echo '<p>CSRF token mismatch. Please refresh the page and try again.</p>';
        }

        exit;
    }

    private function debugLog(string $message): void
    {
        Logger::debug($message);
    }
}


if (!\class_exists('CsrfMiddleware', false) && !\interface_exists('CsrfMiddleware', false) && !\trait_exists('CsrfMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\CsrfMiddleware', 'CsrfMiddleware');
}
