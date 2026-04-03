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

// core/routing/RouteCollection.php

class RouteCollection {
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];

    /**
     * Add a route to the collection
     *
     * @param string|array $methods HTTP method(s)
     * @param string $uri URI pattern (e.g., '/user/{id}')
     * @param mixed $action Controller action [Class, 'method'] or Closure
     * @param array $options Additional options (middleware, name, where)
     * @return array The added route definition
     */
    public function add($methods, string $uri, $action, array $options = []): array {
        $methods = (array) $methods;

        // Apply group stack attributes
        if (!empty($this->groupStack)) {
            $groupAttributes = end($this->groupStack);

            // Prepend group prefix to URI
            if (isset($groupAttributes['prefix'])) {
                $uri = trim($groupAttributes['prefix'], '/') . '/' . trim($uri, '/');
                $uri = '/' . trim($uri, '/');
            }

            // Merge group middleware with route middleware
            if (isset($groupAttributes['middleware'])) {
                $options['middleware'] = array_merge(
                    $groupAttributes['middleware'],
                    $options['middleware'] ?? []
                );
            }
        }

        // Compile URI pattern to regex (with constraints)
        $pattern = $this->compile($uri, $options['where'] ?? []);
        $parameters = $this->extractParameterNames($uri);

        // Create route definition
        $route = [
            'methods' => $methods,
            'uri' => $uri,
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => $options['middleware'] ?? [],
            'name' => $options['name'] ?? null,
            'parameters' => $parameters,
            'where' => $options['where'] ?? []
        ];

        // Register route for each HTTP method
        // IMPORTANT: Create a copy for each method to avoid reference issues
        foreach ($methods as $method) {
            $this->routes[] = $this->copyRoute($route);
        }

        // Register named route
        if (isset($options['name'])) {
            // IMPORTANT: Create a copy to avoid reference issues
            $this->namedRoutes[$options['name']] = $this->copyRoute($route);
        }

        // Return a copy for the caller to use
        return $this->copyRoute($route);
    }

    /**
     * Match a request against registered routes
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array|null Matched route with parameters or null
     */
    public function match(string $method, string $uri): ?array {
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        Logger::debug("RouteCollection::match() - Looking for: {$method} {$uri}");
        Logger::debug("RouteCollection::match() - Total routes to check: " . count($this->routes));

        foreach ($this->routes as $index => $route) {
            // Check if HTTP method matches
            if (!in_array($method, $route['methods'])) {
                continue;
            }

            // Check if URI matches pattern
            if (preg_match($route['pattern'], $uri, $matches)) {
                Logger::debug("RouteCollection::match() - ✅ Pattern matched at index {$index}: {$route['pattern']} for URI: {$uri}");
                Logger::debug("RouteCollection::match() - Route URI: {$route['uri']}");
                Logger::debug("RouteCollection::match() - Matches: " . json_encode($matches));
                Logger::debug("RouteCollection::match() - Route parameters: " . json_encode($route['parameters']));
                
                // Remove full match
                array_shift($matches);

                // Bind parameter names to values
                $parameters = [];
                foreach ($route['parameters'] as $index => $paramName) {
                    $parameters[$paramName] = $matches[$index] ?? null;
                    Logger::debug("RouteCollection::match() - Binding param '{$paramName}' (index {$index}) = " . ($matches[$index] ?? 'null'));
                }

                Logger::debug("RouteCollection::match() - Final parameters: " . json_encode($parameters));
                
                // Return matched route with parameters
                return array_merge($route, ['params' => $parameters]);
            }
        }

        Logger::debug("RouteCollection::match() - ❌ No match found for {$method} {$uri}");
        // Log first few routes for debugging
        $checkedCount = 0;
        foreach ($this->routes as $route) {
            if (in_array($method, $route['methods'])) {
                Logger::debug("RouteCollection::match() - Checked route: {$route['uri']} (pattern: {$route['pattern']})");
                $checkedCount++;
                if ($checkedCount >= 5) break; // Limit to first 5
            }
        }

        return null;
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
     * Find route by name
     *
     * @param string $name Route name
     * @return array|null Route definition or null
     */
    public function findByName(string $name): ?array {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Register a named route (used when name is set after route creation)
     *
     * @param string $name Route name
     * @param array $route Route definition
     */
    public function registerNamedRoute(string $name, array $route): void {
        // IMPORTANT: Create a copy FIRST to avoid reference issues
        // The route passed in might be a reference that gets modified later
        $routeCopy = $this->copyRoute($route);
        // Update the route's name field on the copy
        $routeCopy['name'] = $name;
        // Register it in namedRoutes
        $this->namedRoutes[$name] = $routeCopy;
    }

    /**
     * Compile URI pattern to regex
     *
     * @param string $uri URI pattern (e.g., '/user/{id}/post/{slug?}')
     * @param array $constraints Parameter constraints (e.g., ['id' => '[0-9]+'])
     * @return string Compiled regex pattern
     */
    private function compile(string $uri, array $constraints = []): string {
        // Normalize URI
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // First, escape all forward slashes
        $pattern = str_replace('/', '\/', $uri);

        // Replace {param?} with optional parameter regex (after escaping)
        $pattern = preg_replace_callback('/\\\\\\/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', function($matches) use ($constraints) {
            $paramName = $matches[1];
            // Use custom constraint if provided, otherwise match any characters
            $constraint = isset($constraints[$paramName]) ? $constraints[$paramName] : '[^\/]*';
            return '(?:\/(' . $constraint . '))?';
        }, $pattern);

        // Replace {param} with required parameter regex (after escaping)
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function($matches) use ($constraints) {
            $paramName = $matches[1];
            // Use custom constraint if provided, otherwise match non-slash characters
            $constraint = isset($constraints[$paramName]) ? $constraints[$paramName] : '[^\/]+';
            return '(' . $constraint . ')';
        }, $pattern);

        // Add anchors
        $pattern = '#^' . $pattern . '$#';

        return $pattern;
    }

    /**
     * Extract parameter names from URI
     *
     * @param string $uri URI pattern
     * @return array Parameter names
     */
    private function extractParameterNames(string $uri): array {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??}/', $uri, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Get all registered routes
     *
     * @return array All routes
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Push group attributes onto stack
     *
     * @param array $attributes Group attributes (prefix, middleware, etc.)
     */
    public function pushGroup(array $attributes): void {
        // Merge with parent group if exists
        if (!empty($this->groupStack)) {
            $parent = end($this->groupStack);

            // Concatenate prefixes
            if (isset($parent['prefix']) && isset($attributes['prefix'])) {
                $attributes['prefix'] = trim($parent['prefix'], '/') . '/' . trim($attributes['prefix'], '/');
            } elseif (isset($parent['prefix'])) {
                $attributes['prefix'] = $parent['prefix'];
            }

            // Merge middleware
            if (isset($parent['middleware']) && isset($attributes['middleware'])) {
                $attributes['middleware'] = array_merge($parent['middleware'], $attributes['middleware']);
            } elseif (isset($parent['middleware'])) {
                $attributes['middleware'] = $parent['middleware'];
            }
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Pop group attributes from stack
     */
    public function popGroup(): void {
        array_pop($this->groupStack);
    }

    /**
     * Get current group stack (for middleware merging)
     */
    public function getGroupStack(): array {
        return $this->groupStack;
    }

    /**
     * Update route middleware in the collection
     */
    public function updateRouteMiddleware(string $uri, array $methods, array $middleware): void {
        foreach ($this->routes as &$route) {
            if ($route['uri'] === $uri) {
                $routeMethods = is_array($route['methods']) ? $route['methods'] : [$route['methods']];
                if (array_intersect($routeMethods, $methods)) {
                    $route['middleware'] = $middleware;
                }
            }
        }
        
        // Also update named routes if they match
        foreach ($this->namedRoutes as &$namedRoute) {
            if ($namedRoute['uri'] === $uri) {
                $routeMethods = is_array($namedRoute['methods']) ? $namedRoute['methods'] : [$namedRoute['methods']];
                if (array_intersect($routeMethods, $methods)) {
                    $namedRoute['middleware'] = $middleware;
                }
            }
        }
    }

    /**
     * Generate URL from named route
     *
     * @param string $name Route name
     * @param array $params Route parameters
     * @return string|null Generated URL or null if route not found
     */
    public function generateUrl(string $name, array $params = []): ?string {
        $route = $this->findByName($name);

        if (!$route) {
            return null;
        }

        $uri = $route['uri'];

        // Replace parameters in URI - use more specific replacement to avoid conflicts
        foreach ($params as $key => $value) {
            // Replace {key} with value, but only if it's a complete parameter placeholder
            $uri = preg_replace('/\{' . preg_quote($key, '/') . '\}/', (string)$value, $uri);
            $uri = preg_replace('/\{' . preg_quote($key, '/') . '\?\}/', (string)$value, $uri);
        }

        // Remove optional parameters that weren't provided
        $uri = preg_replace('/\/\{[a-zA-Z_][a-zA-Z0-9_]*\?\}/', '', $uri);

        // Remove any remaining required parameters that weren't provided (shouldn't happen for routes without params)
        // This ensures we don't have {id} or similar in the final URL
        $uri = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '', $uri);
        // Clean up any double slashes that might result
        $uri = preg_replace('#/+#', '/', $uri);
        // Ensure leading slash
        $uri = '/' . ltrim($uri, '/');

        return $uri;
    }
}


if (!\class_exists('RouteCollection', false) && !\interface_exists('RouteCollection', false) && !\trait_exists('RouteCollection', false)) {
    \class_alias(__NAMESPACE__ . '\\RouteCollection', 'RouteCollection');
}
