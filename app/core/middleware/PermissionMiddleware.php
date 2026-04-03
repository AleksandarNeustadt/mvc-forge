<?php

namespace App\Core\middleware;


use App\Core\http\Request;
use App\Core\logging\Logger;
use App\Models\User;use BadMethodCallException;
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
 * Permission Middleware
 * 
 * Checks if the authenticated user has the required permission(s)
 */
class PermissionMiddleware implements MiddlewareInterface
{
    private array $permissions;
    private bool $requireAll;

    /**
     * Constructor
     * 
     * @param string|array $permissions Permission slug(s) required
     * @param bool $requireAll If true, user must have ALL permissions. If false, user needs ANY permission.
     */
    public function __construct($permissions, bool $requireAll = false)
    {
        $this->permissions = is_array($permissions) ? $permissions : [$permissions];
        $this->requireAll = $requireAll;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        // Debug: Log that PermissionMiddleware is being executed
        Logger::debug('Permission middleware check started', [
            'uri' => $request->uri(),
            'required_permissions' => $this->permissions,
            'require_all' => $this->requireAll,
        ]);
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
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

            global $router;
            $lang = $router->lang ?? 'sr';
            http_response_code(302);
            header("Location: /{$lang}/login");
            exit;
        }

        // Get current user
        $user = User::find($_SESSION['user_id']);
        
        if (!$user) {
            if ($request->wantsJson() || $request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error' => 'User not found'
                ]);
                exit;
            }

            global $router;
            $lang = $router->lang ?? 'sr';
            http_response_code(302);
            header("Location: /{$lang}/login");
            exit;
        }

        // Super admin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check permissions
        $hasPermission = $this->requireAll
            ? $user->hasAllPermissions($this->permissions)
            : $user->hasAnyPermission($this->permissions);
        
        // Debug logging
        Logger::debug('Permission middleware decision', [
            'user_id' => $user->id,
            'is_super_admin' => $user->isSuperAdmin(),
            'has_permission' => $hasPermission,
            'required_permissions' => $this->permissions,
        ]);
        
        // Get user roles for debugging
        $userRoles = $user->roles();
        $roleNames = array_map(fn($r) => $r->slug, $userRoles);
        Logger::debug('Permission middleware user roles', [
            'user_id' => $user->id,
            'roles' => $roleNames,
        ]);

        if (!$hasPermission) {
            if ($request->wantsJson() || $request->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Forbidden',
                    'error' => 'You do not have permission to access this resource'
                ]);
                exit;
            }

            // Show 403 page using EXACT same approach as Router::handle404() (with layout)
            http_response_code(403);
            
            // Render 403 view
            // PermissionMiddleware.php is in core/middleware/, views are in mvc/views/ at root level
            // EXACT same approach as Router::handle404() - set $viewPath and include layout
            // Use /../../ to go from core/middleware/ to root, then mvc/views/
            $viewPath = __DIR__ . '/../../mvc/views/pages/403.php';
            if (file_exists($viewPath)) {
                include __DIR__ . '/../../mvc/views/layout.php';
            } else {
                // Fallback to simple 403 page
                echo '<!DOCTYPE html>
<html lang="' . htmlspecialchars($lang) . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Forbidden</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #0f172a;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        h1 { font-size: 4rem; margin: 0; color: #eab308; }
        p { font-size: 1.25rem; color: #94a3b8; }
        a { color: #eab308; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>403</h1>
        <p>You do not have permission to access this resource.</p>
        <a href="/' . htmlspecialchars($lang) . '">Go Home</a>
    </div>
</body>
</html>';
            }
            exit;
        }

        // User has required permission(s), continue
        return $next($request);
    }
}


if (!\class_exists('PermissionMiddleware', false) && !\interface_exists('PermissionMiddleware', false) && !\trait_exists('PermissionMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\PermissionMiddleware', 'PermissionMiddleware');
}
