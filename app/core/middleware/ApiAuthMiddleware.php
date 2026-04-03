<?php

namespace App\Core\middleware;


use App\Core\http\Input;
use App\Core\logging\Logger;
use App\Core\http\Request;
use App\Models\ApiToken;use BadMethodCallException;
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
 * API Authentication Middleware
 * 
 * Handles API token authentication for API requests
 */
class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        Logger::debug('API auth middleware request received', [
            'uri' => $request->uri(),
            'method' => $request->method(),
            'has_authorization_header' => $request->header('authorization') !== null,
            'has_http_authorization' => !empty($_SERVER['HTTP_AUTHORIZATION']),
            'has_redirect_http_authorization' => !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']),
        ]);
        
        // Get token from Authorization header
        $token = Input::bearerToken();
        Logger::debug('API bearer token lookup completed', [
            'token_source' => $token ? 'authorization_header' : 'missing',
            'token_preview' => $this->tokenPreview($token),
        ]);
        
        if (!$token) {
            // Try to get from query parameter (for testing)
            $token = $request->query('api_token');
            Logger::debug('API query token fallback checked', [
                'token_source' => $token ? 'query' : 'missing',
                'token_preview' => $this->tokenPreview($token),
            ]);
        }
        
        if (!$token) {
            Logger::warning('API request rejected: missing token', [
                'uri' => $request->uri(),
                'method' => $request->method(),
            ]);
            return $this->unauthorized('No API token provided');
        }
        
        // Find token in database
        $apiToken = ApiToken::findByToken($token);
        
        if (!$apiToken) {
            Logger::warning('API request rejected: invalid token', [
                'uri' => $request->uri(),
                'method' => $request->method(),
                'token_preview' => $this->tokenPreview($token),
            ]);
            return $this->unauthorized('Invalid API token');
        }
        
        Logger::debug('API token resolved', [
            'user_id' => $apiToken->user_id,
            'token_id' => $apiToken->id ?? null,
        ]);
        
        // Check if token is expired
        if ($apiToken->isExpired()) {
            Logger::warning('API request rejected: expired token', [
                'user_id' => $apiToken->user_id,
                'token_id' => $apiToken->id ?? null,
            ]);
            return $this->unauthorized('API token has expired');
        }
        
        // Get user
        $user = $apiToken->user();
        
        if (!$user) {
            Logger::warning('API request rejected: token without user', [
                'token_id' => $apiToken->id ?? null,
                'user_id' => $apiToken->user_id,
            ]);
            return $this->unauthorized('User associated with token not found');
        }
        
        // Check if user is banned
        if ($user->isBanned()) {
            Logger::warning('API request rejected: banned user', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
            return $this->unauthorized('User account is banned');
        }
        
        // Check if user is approved
        if (!$user->isApproved()) {
            Logger::warning('API request rejected: user pending approval', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
            return $this->unauthorized('User account is pending approval');
        }
        
        Logger::debug('API user authenticated', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);
        
        // Update last used timestamp
        $apiToken->updateLastUsed();
        
        // Set user in session for compatibility with existing code
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_username'] = $user->username;
        
        return $next($request);
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized',
            'error' => $message
        ]);
        exit;
    }

    private function tokenPreview(?string $token): string
    {
        if (!$token) {
            return 'missing';
        }

        return substr(hash('sha256', $token), 0, 12);
    }
}


if (!\class_exists('ApiAuthMiddleware', false) && !\interface_exists('ApiAuthMiddleware', false) && !\trait_exists('ApiAuthMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiAuthMiddleware', 'ApiAuthMiddleware');
}
