<?php

namespace App\Core\routing;


use App\Core\cache\Cache;
use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;
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

use App\Controllers\PageController;
use App\Models\Page;

/**
 * Dynamic Route Registry
 * 
 * Handles runtime registration of routes from Page Manager
 * 
 * Usage:
 *   DynamicRouteRegistry::register('/about', [PageController::class, 'show']);
 *   $route = DynamicRouteRegistry::findRoute('/about');
 */
class DynamicRouteRegistry
{
    private const CACHE_KEY = 'dynamic_routes.registry.v1';
    private const CACHE_TTL = 3600;

    private static array $dynamicRoutes = [];
    private static bool $loaded = false;

    /**
     * Register a dynamic route
     * 
     * @param string $uri Route URI (e.g., '/about', '/blog')
     * @param mixed $handler Controller action [Class, 'method'] or Closure
     * @param array $options Additional options (middleware, name)
     */
    public static function register(string $uri, $handler, array $options = []): void
    {
        // Normalize URI (ensure it starts with /)
        $uri = '/' . ltrim($uri, '/');
        
        // Remove trailing slash (except for root)
        if ($uri !== '/' && $uri[strlen($uri) - 1] === '/') {
            $uri = rtrim($uri, '/');
        }

        self::$dynamicRoutes[$uri] = [
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $options['middleware'] ?? [],
            'name' => $options['name'] ?? null,
            'methods' => $options['methods'] ?? ['GET'],
            'priority' => $options['priority'] ?? 0, // Higher priority routes are checked first
        ];
    }

    /**
     * Unregister a dynamic route
     * 
     * @param string $uri Route URI
     */
    public static function unregister(string $uri): void
    {
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/' && $uri[strlen($uri) - 1] === '/') {
            $uri = rtrim($uri, '/');
        }

        unset(self::$dynamicRoutes[$uri]);
    }

    /**
     * Get all registered dynamic routes
     * 
     * @return array All dynamic routes
     */
    public static function getRoutes(): array
    {
        self::loadFromDatabase();
        return self::$dynamicRoutes;
    }

    /**
     * Find a route by URI
     * 
     * @param string $uri Request URI
     * @return array|null Route definition or null if not found
     */
    public static function findRoute(string $uri): ?array
    {
        self::loadFromDatabase();
        
        // Normalize URI
        $normalizedUri = '/' . ltrim($uri, '/');
        if ($normalizedUri !== '/' && $normalizedUri[strlen($normalizedUri) - 1] === '/') {
            $normalizedUri = rtrim($normalizedUri, '/');
        }

        // Debug: Log what we're looking for and what we have
        if (empty(self::$dynamicRoutes)) {
            self::debugLog("DynamicRouteRegistry::findRoute() - No routes loaded. Looking for: {$normalizedUri}");
        } else {
            self::debugLog("DynamicRouteRegistry::findRoute() - Looking for: {$normalizedUri}, Available routes: " . implode(', ', array_keys(self::$dynamicRoutes)));
        }

        // Exact match
        if (isset(self::$dynamicRoutes[$normalizedUri])) {
            return self::$dynamicRoutes[$normalizedUri];
        }

        // If no exact match, allow PageController to handle it
        // This enables PageController to handle:
        // - Hierarchical URLs like /category/post-slug
        // - Category listing URLs like /category-slug
        // - Backward compatibility with old URLs
        // PageController::show() will handle 404 if page doesn't exist
        if (class_exists(PageController::class)) {
            self::debugLog("DynamicRouteRegistry::findRoute() - No exact match, using PageController fallback for: {$normalizedUri}");
            return [
                'uri' => $normalizedUri,
                'handler' => [PageController::class, 'show'],
                'middleware' => [],
                'name' => 'page.fallback',
                'methods' => ['GET'],
                'priority' => 50, // Lower priority than exact matches
            ];
        }

        return null;
    }

    /**
     * Check if a route exists
     * 
     * @param string $uri Route URI
     * @return bool True if route exists
     */
    public static function hasRoute(string $uri): bool
    {
        self::loadFromDatabase();
        
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/' && $uri[strlen($uri) - 1] === '/') {
            $uri = rtrim($uri, '/');
        }

        return isset(self::$dynamicRoutes[$uri]);
    }

    /**
     * Load routes from database (pages table)
     * This is called automatically when routes are accessed
     */
    public static function loadFromDatabase(): void
    {
        if (self::$loaded) {
            return;
        }

        $cachedRoutes = Cache::get(self::CACHE_KEY);
        if (is_array($cachedRoutes)) {
            self::$dynamicRoutes = $cachedRoutes;
            self::$loaded = true;
            self::debugLog('DynamicRouteRegistry::loadFromDatabase() - Loaded dynamic routes from cache');
            return;
        }

        if ($cachedRoutes !== null) {
            Cache::forget(self::CACHE_KEY);
            self::debugLog('DynamicRouteRegistry::loadFromDatabase() - Dropped malformed dynamic routes cache payload');
        }

        try {
            // Check if Page model exists and table exists
            if (!class_exists(Page::class)) {
                return;
            }

            // Check if pages table exists
            $tables = DatabaseBuilder::getTables();
            if (!in_array('pages', $tables)) {
                self::$loaded = true;
                return;
            }

            // Get all active pages from database
            $pages = Database::select(
                "SELECT route, page_type, blog_post_id, blog_category_id, blog_tag_id 
                 FROM pages 
                 WHERE is_active = 1 AND route IS NOT NULL AND route != ''"
            );
            
            self::debugLog("DynamicRouteRegistry::loadFromDatabase() - Found " . count($pages) . " active pages");

            foreach ($pages as $page) {
                $route = $page['route'] ?? '';
                if (empty($route)) {
                    continue;
                }
                
                $pageType = $page['page_type'] ?? 'custom';

                // Normalize route (ensure it starts with / and doesn't end with /)
                $normalizedRoute = '/' . ltrim($route, '/');
                if ($normalizedRoute !== '/' && substr($normalizedRoute, -1) === '/') {
                    $normalizedRoute = rtrim($normalizedRoute, '/');
                }

                // Register route with PageController handler
                if (class_exists(PageController::class)) {
                    self::register($normalizedRoute, [PageController::class, 'show'], [
                        'methods' => ['GET'],
                        'name' => 'page.' . str_replace(['/', '-'], '_', trim($normalizedRoute, '/')),
                        'priority' => 100, // Lower priority than static routes
                    ]);
                    self::debugLog("DynamicRouteRegistry::loadFromDatabase() - Registered route: {$normalizedRoute}");
                }
            }

            self::$loaded = true;
            Cache::set(self::CACHE_KEY, self::$dynamicRoutes, self::CACHE_TTL);
        } catch (Exception $e) {
            // Table might not exist yet, or database error
            // Log the error for debugging
            self::debugLog("DynamicRouteRegistry::loadFromDatabase() error: " . $e->getMessage());
            self::$loaded = true; // Mark as loaded to prevent repeated errors
        } catch (Error $e) {
            // Fatal errors
            self::debugLog("DynamicRouteRegistry::loadFromDatabase() fatal error: " . $e->getMessage());
            self::$loaded = true;
        }
    }

    /**
     * Clear cached routes (force reload from database)
     */
    public static function clearCache(): void
    {
        self::$loaded = false;
        self::$dynamicRoutes = [];
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Register routes from a Page model instance
     * 
     * @param Page $page Page model instance
     */
    public static function registerFromPage(Page $page): void
    {
        if (!$page->is_active || empty($page->route)) {
            return;
        }

        if (class_exists(PageController::class)) {
            self::register($page->route, [PageController::class, 'show'], [
                'methods' => ['GET'],
                'name' => 'page.' . str_replace(['/', '-'], '_', trim($page->route, '/')),
                'priority' => 100,
            ]);
        }
    }

    private static function debugLog(string $message): void
    {
        Logger::debug($message);
    }
}


if (!\class_exists('DynamicRouteRegistry', false) && !\interface_exists('DynamicRouteRegistry', false) && !\trait_exists('DynamicRouteRegistry', false)) {
    \class_alias(__NAMESPACE__ . '\\DynamicRouteRegistry', 'DynamicRouteRegistry');
}
