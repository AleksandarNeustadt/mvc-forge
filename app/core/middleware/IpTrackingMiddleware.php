<?php

namespace App\Core\middleware;


use App\Core\http\Request;
use App\Core\logging\Logger;
use App\Models\IpTracking;
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
 * IP Tracking Middleware
 * 
 * Automatically tracks visitor IP addresses and their requests
 */
class IpTrackingMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request and track IP
     */
    public function handle(Request $request, Closure $next)
    {
        // Simple test - write to a file to see if middleware is called
        @file_put_contents(__DIR__ . '/../../storage/logs/ip_tracking_test.log', date('Y-m-d H:i:s') . " - Middleware called for: " . $request->uri() . "\n", FILE_APPEND);
        
        Logger::debug("IP Tracking middleware: handle() called for path: " . $request->uri());
        
        // Skip tracking for certain paths (optional - reduce noise)
        // Note: We removed /dashboard/ip-tracking from skip list to track all requests
        $skipPaths = [
            // Add paths to skip here if needed
        ];
        
        $requestPath = $request->uri();
        
        // Skip if in skip list
        foreach ($skipPaths as $skipPath) {
            if (strpos($requestPath, $skipPath) === 0) {
                Logger::debug("IP Tracking middleware: Skipping path: {$requestPath}");
                return $next($request);
            }
        }
        
        // Track the request directly (not async - for debugging)
        Logger::debug("IP Tracking middleware: Starting tracking for path: {$requestPath}");
        @file_put_contents(__DIR__ . '/../../storage/logs/ip_tracking_test.log', date('Y-m-d H:i:s') . " - About to log request: {$requestPath}\n", FILE_APPEND);
        try {
            if (!class_exists('IpTracking')) {
                Logger::debug("IP Tracking middleware: IpTracking class not found");
                return $next($request);
            }
            
            // Get IP address
            $ipAddress = $request->ip();
            
            // Get user agent
            $userAgent = $request->userAgent();
            
            // Only skip obvious bots/crawlers based on User-Agent (very strict check)
            if ($userAgent) {
                $botPatterns = [
                    '/^googlebot/i',
                    '/^bingbot/i',
                    '/^slurp/i',
                    '/^duckduckbot/i',
                    '/^baiduspider/i',
                    '/^yandexbot/i',
                    '/^facebookexternalhit/i',
                    '/^twitterbot/i',
                    '/^linkedinbot/i',
                    '/^applebot/i',
                    '/^petalbot/i',
                    '/^semrushbot/i',
                    '/^ahrefsbot/i',
                    '/^mj12bot/i',
                    '/^dotbot/i',
                    '/^exabot/i',
                    '/^gigabot/i',
                    '/^msnbot/i',
                    '/crawl\.yahoo\.net/i',
                    '/search\.msn\.com/i',
                ];
                
                foreach ($botPatterns as $pattern) {
                    if (preg_match($pattern, $userAgent)) {
                        return $next($request); // Skip tracking for known bots
                    }
                }
            }
            
            // Get request method
            $requestMethod = $request->method();
            
            // Get user info if logged in
            $userId = $_SESSION['user_id'] ?? null;
            $username = null;
            if ($userId && class_exists('User')) {
                try {
                    $user = User::find($userId);
                    if ($user) {
                        $username = $user->username;
                    }
                } catch (Exception $e) {
                    // Silently fail
                }
            }
            
            // Log the request (only basic data - no geo-location, no service detection)
            Logger::debug("IP Tracking middleware: About to log request - IP: {$ipAddress}, Path: {$requestPath}");
            @file_put_contents(__DIR__ . '/../../storage/logs/ip_tracking_test.log', date('Y-m-d H:i:s') . " - Calling logRequest()\n", FILE_APPEND);
            IpTracking::logRequest(
                $ipAddress,
                $requestMethod,
                $requestPath,
                $userAgent,
                $userId,
                $username
            );
            @file_put_contents(__DIR__ . '/../../storage/logs/ip_tracking_test.log', date('Y-m-d H:i:s') . " - After logRequest()\n", FILE_APPEND);
            Logger::debug("IP Tracking middleware: After logRequest call");
        } catch (Exception $e) {
            // Log error for debugging
            Logger::debug("IP Tracking middleware error: " . $e->getMessage());
            Logger::debug("IP Tracking middleware trace: " . $e->getTraceAsString());
        }
        
        // Continue with the request
        return $next($request);
    }
}


if (!\class_exists('IpTrackingMiddleware', false) && !\interface_exists('IpTrackingMiddleware', false) && !\trait_exists('IpTrackingMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\IpTrackingMiddleware', 'IpTrackingMiddleware');
}
