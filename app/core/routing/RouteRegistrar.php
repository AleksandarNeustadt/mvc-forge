<?php

namespace App\Core\routing;

use App\Core\logging\Logger;
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

// core/routing/RouteRegistrar.php

class RouteRegistrar {
    private RouteCollection $collection;
    private array $attributes = [];
    private ?array $pendingRoute = null;

    public function __construct(RouteCollection $collection, array $attributes = []) {
        $this->collection = $collection;
        $this->attributes = $attributes;
    }

    /**
     * Register GET route
     */
    public function get(string $uri, $action): self {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register POST route
     */
    public function post(string $uri, $action): self {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register PUT route
     */
    public function put(string $uri, $action): self {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $uri, $action): self {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register PATCH route
     */
    public function patch(string $uri, $action): self {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register route for any HTTP method
     */
    public function any(string $uri, $action): self {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $uri, $action);
    }

    /**
     * Register route for specific HTTP methods
     */
    public function match(array $methods, string $uri, $action): self {
        return $this->addRoute($methods, $uri, $action);
    }

    /**
     * Add middleware to route
     */
    public function middleware($middleware): self {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->attributes['middleware'] = array_merge(
            $this->attributes['middleware'] ?? [],
            $middleware
        );

        // Update pending route if exists
        if ($this->pendingRoute) {
            // Pending route already contains group middleware from RouteCollection::add(),
            // so only append route-specific middleware here to avoid duplicated middleware.
            $this->pendingRoute['middleware'] = array_merge(
                $this->pendingRoute['middleware'] ?? [],
                $middleware
            );
            
            Logger::debug("RouteRegistrar::middleware() - Updated pending route middleware for URI: {$this->pendingRoute['uri']}, Middleware: " . json_encode(array_map(function($mw) {
                return is_object($mw) ? get_class($mw) : (is_string($mw) ? $mw : 'unknown');
            }, $this->pendingRoute['middleware'])));
            
            // Also update the route in the collection if it's already registered
            $this->collection->updateRouteMiddleware($this->pendingRoute['uri'], $this->pendingRoute['methods'] ?? ['GET'], $this->pendingRoute['middleware']);
        }

        return $this;
    }

    /**
     * Set route name
     */
    public function name(string $name): self {
        $this->attributes['name'] = $name;

        // Update pending route if exists
        if ($this->pendingRoute) {
            // IMPORTANT: Create a deep copy IMMEDIATELY to avoid any reference issues
            // Do this before any modifications to ensure we have a clean snapshot
            $routeCopy = $this->copyRoute($this->pendingRoute);
            
            // Apply any pending attributes (like where constraints) to the copy
            if (!empty($this->attributes['where'])) {
                $routeCopy['where'] = $this->attributes['where'];
            }
            
            // Update the route's name field on the copy
            $routeCopy['name'] = $name;
            
            // Register the route by name in the collection
            $this->collection->registerNamedRoute($name, $routeCopy);
        }

        return $this;
    }

    /**
     * Set parameter constraints
     */
    public function where($name, string $pattern = null): self {
        if (is_array($name)) {
            $this->attributes['where'] = array_merge(
                $this->attributes['where'] ?? [],
                $name
            );
        } else {
            $this->attributes['where'][$name] = $pattern;
        }

        // Don't update pendingRoute here - we'll use attributes when name() is called
        // This avoids reference issues

        return $this;
    }

    /**
     * Set route prefix
     */
    public function prefix(string $prefix): self {
        $this->attributes['prefix'] = trim($prefix, '/');
        return $this;
    }

    /**
     * Create route group
     */
    public function group(Closure $callback): void {
        // Push group attributes to collection
        $this->collection->pushGroup($this->attributes);

        // Execute callback to register routes
        $callback($this);

        // Pop group attributes
        $this->collection->popGroup();

        // Reset attributes after group
        $this->attributes = [];
    }

    /**
     * Add route to collection
     */
    private function addRoute($methods, string $uri, $action): self {
        // Flush pending route if exists
        if ($this->pendingRoute) {
            $this->flushPendingRoute();
        }

        // Store route temporarily for method chaining
        $this->pendingRoute = $this->collection->add(
            $methods,
            $uri,
            $action,
            $this->attributes
        );

        // Reset attributes after adding route
        // NOTE: Attributes should not persist across routes unless explicitly in a group
        $this->attributes = [];

        return $this;
    }

    /**
     * Flush pending route
     */
    private function flushPendingRoute(): void {
        $this->pendingRoute = null;
    }

    /**
     * Create a deep copy of route array (handles closures properly)
     */
    private function copyRoute(array $route): array {
        // Create a true deep copy by serializing and unserializing
        // This ensures all nested arrays are copied, not referenced
        return unserialize(serialize($route));
    }

    /**
     * Magic method to proxy calls to Route facade
     */
    public function __call($method, $parameters) {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist on RouteRegistrar");
    }
}


if (!\class_exists('RouteRegistrar', false) && !\interface_exists('RouteRegistrar', false) && !\trait_exists('RouteRegistrar', false)) {
    \class_alias(__NAMESPACE__ . '\\RouteRegistrar', 'RouteRegistrar');
}
