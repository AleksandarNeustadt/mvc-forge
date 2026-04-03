<?php

namespace App\Core\middleware;


use App\Core\http\Request;
use App\Core\logging\Logger;use BadMethodCallException;
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

// core/middleware/AuthMiddleware.php

class AuthMiddleware implements MiddlewareInterface {
    /**
     * Handle authentication check
     */
    public function handle(Request $request, Closure $next) {
        // Check if user is authenticated
        // This is a simple example using session
        if (!isset($_SESSION['user_id'])) {
            // User is not authenticated

            // If API request (wants JSON), return 401
            if ($request->wantsJson() || $request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'You must be logged in to access this resource'
                ]);
                exit;
            }

            // Otherwise redirect to login page (with language prefix)
            global $router;
            $lang = $router->lang ?? 'sr';
            http_response_code(302);
            header("Location: /{$lang}/login");
            exit;
        }

        // Session hijacking protection - check IP and user agent
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();
        $sessionIp = $_SESSION['login_ip'] ?? null;
        $sessionUserAgent = $_SESSION['login_user_agent'] ?? null;
        
        // If IP or user agent changed, invalidate session (security measure)
        // Note: IP can change legitimately (mobile networks, VPN), so we'll log but not block
        // User agent changes are more suspicious
        if ($sessionUserAgent && $sessionUserAgent !== $currentUserAgent) {
            $userId = $_SESSION['user_id'] ?? null;
            // User agent changed - this is suspicious, invalidate session
            $this->invalidateSession();
            
            Logger::warning('Session invalidated due to user agent change', [
                'user_id' => $userId,
                'old_user_agent' => $sessionUserAgent,
                'new_user_agent' => $currentUserAgent,
                'ip' => $currentIp
            ]);
            
            if ($request->wantsJson() || $request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expired',
                    'error' => 'Your session has been invalidated for security reasons. Please log in again.'
                ]);
                exit;
            }
            
            global $router;
            $lang = $router->lang ?? 'sr';
            http_response_code(302);
            header("Location: /{$lang}/login");
            exit;
        }
        
        // Log IP change (but don't block - IP can change legitimately)
        if ($sessionIp && $sessionIp !== $currentIp) {
            Logger::info('User IP changed during session', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'old_ip' => $sessionIp,
                'new_ip' => $currentIp
            ]);
        }
        
        // Session timeout - check last activity (30 minutes of inactivity)
        $sessionTimeout = 30 * 60; // 30 minutes in seconds
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        
        if (time() - $lastActivity > $sessionTimeout) {
            $userId = $_SESSION['user_id'] ?? null;
            // Session expired due to inactivity
            $this->invalidateSession();
            
            Logger::info('Session expired due to inactivity', [
                'user_id' => $userId,
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'ip' => $currentIp
            ]);
            
            if ($request->wantsJson() || $request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expired',
                    'error' => 'Your session has expired due to inactivity. Please log in again.'
                ]);
                exit;
            }
            
            global $router;
            $lang = $router->lang ?? 'sr';
            http_response_code(302);
            header("Location: /{$lang}/login");
            exit;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();

        // User is authenticated, continue to next middleware/controller
        return $next($request);
    }

    private function invalidateSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $cookieParams = session_get_cookie_params();
        session_unset();
        session_destroy();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $cookieParams['path'] ?? '/',
                'domain' => $cookieParams['domain'] ?? '',
                'secure' => (bool) ($cookieParams['secure'] ?? false),
                'httponly' => true,
                'samesite' => $cookieParams['samesite'] ?? 'Lax',
            ]
        );
    }
}


if (!\class_exists('AuthMiddleware', false) && !\interface_exists('AuthMiddleware', false) && !\trait_exists('AuthMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\AuthMiddleware', 'AuthMiddleware');
}
