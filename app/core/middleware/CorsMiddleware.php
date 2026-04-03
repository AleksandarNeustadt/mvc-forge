<?php

namespace App\Core\middleware;

use App\Core\config\Env;
use App\Core\http\Request;use BadMethodCallException;
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

// core/middleware/CorsMiddleware.php

class CorsMiddleware implements MiddlewareInterface {
    /**
     * Handle CORS (Cross-Origin Resource Sharing) headers
     */
    public function handle(Request $request, Closure $next) {
        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        $allowCredentials = $this->allowCredentials();
        $allowedOrigin = $this->resolveAllowedOrigin($origin, $allowCredentials);

        if ($allowedOrigin !== null) {
            header('Access-Control-Allow-Origin: ' . $allowedOrigin);
            header('Vary: Origin', false);
        }

        if ($allowCredentials && $allowedOrigin !== null && $allowedOrigin !== '*') {
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN');
        header('Access-Control-Max-Age: 86400'); // 24 hours

        // Handle preflight OPTIONS request
        if ($request->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Continue to next middleware/controller
        $response = $next($request);

        // Can modify response here if needed
        return $response;
    }

    private function resolveAllowedOrigin(string $origin, bool $allowCredentials): ?string
    {
        $allowedOrigins = $this->allowedOrigins();

        if ($origin === '') {
            return $allowCredentials ? null : '*';
        }

        if (in_array('*', $allowedOrigins, true)) {
            return $allowCredentials ? null : '*';
        }

        return in_array($origin, $allowedOrigins, true) ? $origin : null;
    }

    /**
     * @return list<string>
     */
    private function allowedOrigins(): array
    {
        $configuredOrigins = trim((string) Env::get('CORS_ALLOWED_ORIGINS', ''));
        if ($configuredOrigins === '') {
            $appUrl = trim((string) Env::get('APP_URL', ''));
            return $appUrl !== '' ? [$appUrl] : ['*'];
        }

        $origins = array_values(array_filter(array_map('trim', explode(',', $configuredOrigins))));
        return $origins !== [] ? $origins : ['*'];
    }

    private function allowCredentials(): bool
    {
        $value = Env::get('CORS_ALLOW_CREDENTIALS', false);
        return $value === true || $value === 'true' || $value === '1' || $value === 1;
    }
}


if (!\class_exists('CorsMiddleware', false) && !\interface_exists('CorsMiddleware', false) && !\trait_exists('CorsMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\CorsMiddleware', 'CorsMiddleware');
}
