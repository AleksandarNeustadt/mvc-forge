<?php

namespace App\Core\routing;

use BadMethodCallException;
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

// core/routing/Route.php

class Route {
    private static ?RouteCollection $collection = null;
    private static ?RouteRegistrar $registrar = null;

    /**
     * Set the route collection instance
     */
    public static function setCollection(RouteCollection $collection): void {
        self::$collection = $collection;
        self::$registrar = new RouteRegistrar($collection);
    }

    /**
     * Get the route collection instance
     */
    public static function getCollection(): RouteCollection {
        if (self::$collection === null) {
            self::$collection = new RouteCollection();
            self::$registrar = new RouteRegistrar(self::$collection);
        }

        return self::$collection;
    }

    /**
     * Register GET route
     */
    public static function get(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->get($uri, $action);
    }

    /**
     * Register POST route
     */
    public static function post(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->post($uri, $action);
    }

    /**
     * Register PUT route
     */
    public static function put(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->put($uri, $action);
    }

    /**
     * Register DELETE route
     */
    public static function delete(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->delete($uri, $action);
    }

    /**
     * Register PATCH route
     */
    public static function patch(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->patch($uri, $action);
    }

    /**
     * Register route for any HTTP method
     */
    public static function any(string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->any($uri, $action);
    }

    /**
     * Register route for specific HTTP methods
     */
    public static function match(array $methods, string $uri, $action): RouteRegistrar {
        return self::getRegistrar()->match($methods, $uri, $action);
    }

    /**
     * Create route group with prefix
     */
    public static function prefix(string $prefix): RouteRegistrar {
        return new RouteRegistrar(self::getCollection(), ['prefix' => $prefix]);
    }

    /**
     * Create route group with middleware
     */
    public static function middleware($middleware): RouteRegistrar {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        return new RouteRegistrar(self::getCollection(), ['middleware' => $middleware]);
    }

    /**
     * Create route group
     */
    public static function group(Closure $callback): void {
        self::getRegistrar()->group($callback);
    }

    /**
     * Get registrar instance
     */
    private static function getRegistrar(): RouteRegistrar {
        if (self::$registrar === null) {
            self::getCollection(); // This will initialize both
        }

        return self::$registrar;
    }

    /**
     * Find route by name
     */
    public static function getByName(string $name): ?array {
        return self::getCollection()->findByName($name);
    }

    /**
     * Generate URL from named route
     */
    public static function url(string $name, array $params = []): ?string {
        return self::getCollection()->generateUrl($name, $params);
    }
}


if (!\class_exists('Route', false) && !\interface_exists('Route', false) && !\trait_exists('Route', false)) {
    \class_alias(__NAMESPACE__ . '\\Route', 'Route');
}
