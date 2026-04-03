<?php

namespace App\Core\routing;


use App\Core\container\Container;
use App\Core\http\Request;
use App\Core\logging\Logger;
use App\Core\middleware\IpTrackingMiddleware;
use App\Core\middleware\SecurityHeadersMiddleware;
use App\Core\services\GeoLocation;use BadMethodCallException;
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

// core/routing/Router.php

class Router {
    public string $lang = 'sr';
    private RouteCollection $routes;
    private array $middlewareMap = [];
    private string $uri;
    private string $method;

    // 30 supported languages
    private array $supportedLangs = [
        'sr', 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'pl', 'ru',
        'uk', 'cs', 'hu', 'el', 'ro', 'hr', 'bg', 'sk', 'sv', 'da',
        'no', 'fi', 'lt', 'et', 'lv', 'sl', 'zh', 'ja', 'ko', 'tr'
    ];

    public function __construct(RouteCollection $routes) {
        $this->routes = $routes;
        $this->extractLanguage();
        
        // Support method spoofing (for PUT, PATCH, DELETE via POST with _method parameter)
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        Logger::debug('Router bootstrap request metadata', [
            'request_method' => $requestMethod,
            'https' => $_SERVER['HTTPS'] ?? 'not set',
            'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'not set',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'not set',
        ]);
        
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $spoofedMethod = strtoupper($_POST['_method']);
            if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                $this->method = $spoofedMethod;
                Logger::debug('Router method spoofed', ['method' => $this->method]);
            } else {
                $this->method = $requestMethod;
            }
        } else {
            $this->method = $requestMethod;
        }
        
        Logger::debug('Router final request method resolved', ['method' => $this->method]);
    }

    /**
     * Extract language from URI and normalize URI for routing
     * If no language prefix exists, detect from IP and redirect
     * API routes (starting with /api/) skip language prefix
     */
    private function extractLanguage(): void {
        // Get REQUEST_URI safely
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($requestUri, PHP_URL_PATH);
        $queryString = parse_url($requestUri, PHP_URL_QUERY);
        $parts = array_filter(explode('/', trim($uri, '/')));

        Logger::debug('Router language extraction started', [
            'uri' => $uri,
            'parts' => array_values($parts),
        ]);

        // API routes should NOT have language prefix - skip language detection
        // Check if first part is 'api' OR if second part is 'api' (when language prefix exists)
        $isApiRoute = false;
        if (!empty($parts)) {
            $firstPart = reset($parts);
            // Direct API route: /api/...
            if ($firstPart === 'api') {
                $isApiRoute = true;
                Logger::debug('Router detected direct API route');
            }
            // API route with language prefix: /de/api/... or /sr/api/...
            elseif (count($parts) >= 2 && in_array($firstPart, $this->supportedLangs) && $parts[1] === 'api') {
                $isApiRoute = true;
                Logger::debug('Router detected API route with language prefix', ['language' => $firstPart]);
                // Remove language prefix for API routes
                array_shift($parts);
            }
        }

        if ($isApiRoute) {
            // This is an API route, use it as-is without language prefix
            $this->lang = 'sr'; // Default language for API (not used in API responses)
            $this->uri = '/' . implode('/', $parts); // Keep URI with /api but without language
            if ($this->uri !== '/') {
                $this->uri = rtrim($this->uri, '/');
            }
            Logger::debug('Router normalized API URI', ['uri' => $this->uri]);
            return;
        }

        // Check if first part is a supported language
        $hasLangPrefix = !empty($parts) && in_array(reset($parts), $this->supportedLangs);

        // If NO language prefix, detect and redirect
        if (!$hasLangPrefix) {
            $this->redirectToLanguage($uri, $queryString);
        }

        // Extract language from URL (we know it exists after redirect check)
        if (!empty($parts) && in_array(reset($parts), $this->supportedLangs)) {
            $this->lang = array_shift($parts);
        }

        // Store normalized URI without language prefix
        $this->uri = '/' . implode('/', $parts);
        if ($this->uri !== '/') {
            $this->uri = rtrim($this->uri, '/');
        }
    }

    /**
     * Redirect to URL with detected language prefix
     *
     * @param string $uri Current URI path
     * @param string|null $queryString Query string if present
     */
    private function redirectToLanguage(string $uri, ?string $queryString): void {
        // Detect language from IP
        $geoLocation = new GeoLocation();
        $detectedLang = $geoLocation->detectLanguage();

        // Ensure detected language is supported, fallback to 'en'
        if (!in_array($detectedLang, $this->supportedLangs)) {
            $detectedLang = 'en';
        }

        // Build redirect URL
        $redirectPath = '/' . $detectedLang . $uri;

        // Add query string if present
        if ($queryString) {
            $redirectPath .= '?' . $queryString;
        }

        // Convert to absolute HTTPS URL
        $scheme = 'https';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $absoluteUrl = "{$scheme}://{$host}{$redirectPath}";

        // Perform 302 redirect (temporary, as language can change)
        header('Location: ' . $absoluteUrl, true, 302);
        exit;
    }

    /**
     * Register middleware
     *
     * @param string $name Middleware name
     * @param string $class Middleware class name
     */
    public function registerMiddleware(string $name, string $class): void {
        $this->middlewareMap[$name] = $class;
    }

    /**
     * Dispatch the request
     */
    public function dispatch(): void {
        $this->debugLog("Router::dispatch() - Method: {$this->method}, URI: {$this->uri}");
        $this->debugLog("Router::dispatch() - Full REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        
        // Match route from static routes first
        $matchedRoute = $this->routes->match($this->method, $this->uri);
        
        if ($matchedRoute) {
            $this->debugLog("Router::dispatch() - ✅ Matched route: " . json_encode([
                'uri' => $matchedRoute['uri'],
                'methods' => $matchedRoute['methods'],
                'action' => is_array($matchedRoute['action']) ? $matchedRoute['action'][0] . '::' . $matchedRoute['action'][1] : 'closure',
                'middleware' => array_map(function($mw) {
                    return is_object($mw) ? get_class($mw) : (is_string($mw) ? $mw : 'unknown');
                }, $matchedRoute['middleware'] ?? [])
            ]));
        } else {
            $this->debugLog("Router::dispatch() - ❌ No route matched for {$this->method} {$this->uri}");
            // Log all registered routes for debugging
            $allRoutes = $this->routes->getRoutes();
            $this->debugLog("Router::dispatch() - Available routes count: " . count($allRoutes));
            $apiRoutes = array_filter($allRoutes, function($route) {
                return isset($route['uri']) && strpos($route['uri'], '/api') === 0;
            });
            $this->debugLog("Router::dispatch() - API routes found: " . count($apiRoutes));
            foreach (array_slice($apiRoutes, 0, 20) as $route) { // Limit to first 20 for readability
                $methods = is_array($route['methods']) ? implode('|', $route['methods']) : $route['methods'];
                $this->debugLog("Router::dispatch() - API route: {$methods} {$route['uri']}");
            }
        }

        // If no static route found, check dynamic routes
        // BUT: Skip dynamic routes for API endpoints (they should only use static routes)
        if (!$matchedRoute && class_exists('DynamicRouteRegistry') && strpos($this->uri, '/api/') !== 0) {
            $this->debugLog("Router::dispatch() - Checking dynamic routes for URI: {$this->uri}");
            $dynamicRoute = DynamicRouteRegistry::findRoute($this->uri);
            
            if ($dynamicRoute && in_array($this->method, $dynamicRoute['methods'])) {
                $this->debugLog("Router::dispatch() - Found dynamic route: {$this->uri}");
                // Convert dynamic route to matched route format
                $matchedRoute = [
                    'action' => $dynamicRoute['handler'],
                    'middleware' => $dynamicRoute['middleware'] ?? [],
                    'params' => [],
                    'uri' => $dynamicRoute['uri'],
                ];
            }
        } elseif (!$matchedRoute && strpos($this->uri, '/api/') === 0) {
            $this->debugLog("Router::dispatch() - ⚠️ API route not matched, skipping dynamic routes (API routes must be static)");
        }

        if (!$matchedRoute) {
            // No route found - show 404
            $this->handle404();
            return;
        }

        // Get route middleware
        $middlewareClasses = $this->resolveMiddleware($matchedRoute['middleware'] ?? []);
        
        // Apply security headers globally (before route-specific middleware)
        if (isset($this->middlewareMap['security'])) {
            array_unshift($middlewareClasses, new SecurityHeadersMiddleware());
        }
        
        // Apply IP tracking globally (after security headers, before other middleware)
        array_unshift($middlewareClasses, new IpTrackingMiddleware());
        
        // Debug: Log middleware for dashboard route
        if ($matchedRoute['uri'] === '/dashboard') {
            $this->debugLog("Router::dispatch() - Dashboard route middleware: " . json_encode(array_map(function($mw) {
                return is_object($mw) ? get_class($mw) : (is_string($mw) ? $mw : 'unknown');
            }, $middlewareClasses)));
        }

        // Build and execute middleware pipeline
        $this->executeMiddlewarePipeline($middlewareClasses, function() use ($matchedRoute) {
            return $this->invokeController($matchedRoute['action'], $matchedRoute['params'] ?? []);
        });
    }

    /**
     * Resolve middleware names to class instances
     *
     * @param array $middlewareItems Middleware names or instances
     * @return array Middleware class instances
     */
    private function resolveMiddleware(array $middlewareItems): array {
        $middleware = [];

        foreach ($middlewareItems as $item) {
            // If it's already a middleware instance (object with handle method), use it directly
            if (is_object($item) && method_exists($item, 'handle')) {
                $middleware[] = $item;
                $this->debugLog("Router::resolveMiddleware() - Added middleware instance: " . get_class($item));
                continue;
            }
            
            // If it's a string, resolve it from the middleware map
            if (is_string($item) && isset($this->middlewareMap[$item])) {
                $className = $this->middlewareMap[$item];
                $middleware[] = new $className();
                $this->debugLog("Router::resolveMiddleware() - Resolved middleware from map: {$item} -> {$className}");
            } else if (is_string($item)) {
                $this->debugLog("Router::resolveMiddleware() - WARNING: Middleware '{$item}' not found in middleware map!");
            }
        }

        return $middleware;
    }

    /**
     * Execute middleware pipeline (Russian Doll model)
     *
     * @param array $middleware Array of middleware instances
     * @param Closure $destination Final destination (controller action)
     */
    private function executeMiddlewarePipeline(array $middleware, Closure $destination): void {
        // Get request instance
        $request = Request::capture();
        
        // Debug: Log middleware pipeline execution
        $this->debugLog("Router::executeMiddlewarePipeline() - Executing " . count($middleware) . " middleware(s): " . json_encode(array_map(function($mw) {
            return is_object($mw) ? get_class($mw) : (is_string($mw) ? $mw : 'unknown');
        }, $middleware)));

        // Build pipeline from innermost to outermost
        $pipeline = $destination;

        // Wrap each middleware around the pipeline
        foreach (array_reverse($middleware) as $mw) {
            $pipeline = function($request) use ($mw, $pipeline) {
                $this->debugLog("Router::executeMiddlewarePipeline() - Executing middleware: " . (is_object($mw) ? get_class($mw) : 'unknown'));
                return $mw->handle($request, $pipeline);
            };
        }

        // Execute the pipeline
        $pipeline($request);
    }

    /**
     * Invoke controller action
     *
     * @param mixed $action Controller action [Class, 'method'] or Closure
     * @param array $params Route parameters
     * @return mixed
     */
    private function invokeController($action, array $params = []) {
        $this->debugLog("Router::invokeController() - Action: " . (is_array($action) ? $action[0] . '::' . $action[1] : 'closure'));
        $this->debugLog("Router::invokeController() - Params received: " . json_encode($params));
        
        // Handle closure
        if ($action instanceof Closure) {
            return $action(...array_values($params));
        }

        // Handle controller action array [Controller::class, 'method']
        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;

            // Instantiate controller
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class {$controllerClass} not found");
            }

            if (class_exists('Container')) {
                $container = Container::getInstance();
                $controller = $container->has($controllerClass)
                    ? $container->make($controllerClass)
                    : new $controllerClass();
            } else {
                $controller = new $controllerClass();
            }

            // Check if method exists
            if (!method_exists($controller, $method)) {
                throw new Exception("Method {$method} not found in controller {$controllerClass}");
            }

            // Use reflection to inspect method parameters
            $reflectionMethod = new ReflectionMethod($controller, $method);
            $methodParams = $reflectionMethod->getParameters();

            $args = [];

            foreach ($methodParams as $param) {
                $paramName = $param->getName();
                $paramType = $param->getType();

                $this->debugLog("Router::invokeController() - Processing parameter: {$paramName}, type: " . ($paramType ? $paramType->getName() : 'none'));

                // Check if parameter is Request type
                if ($paramType && $paramType->getName() === 'Request') {
                    $args[] = Request::capture();
                }
                // Check if parameter exists in route params
                elseif (isset($params[$paramName])) {
                    // Type cast based on type hint
                    $value = $params[$paramName];
                    $this->debugLog("Router::invokeController() - Found param '{$paramName}' in params: {$value}");

                    if ($paramType) {
                        $typeName = $paramType->getName();
                        if ($typeName === 'int') {
                            $value = (int) $value;
                        } elseif ($typeName === 'float') {
                            $value = (float) $value;
                        } elseif ($typeName === 'bool') {
                            $value = (bool) $value;
                        }
                    }

                    $this->debugLog("Router::invokeController() - Casted value for '{$paramName}': {$value}");
                    $args[] = $value;
                    unset($params[$paramName]); // Remove used param
                }
                // Check if parameter has default value
                elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                }
                // Parameter is required but not provided
                else {
                    throw new Exception("Required parameter '{$paramName}' not provided for {$controllerClass}::{$method}");
                }
            }

            // Add any remaining route params that weren't matched by name
            foreach ($params as $value) {
                $args[] = $value;
            }

            // Call controller method with parameters
            return $controller->$method(...$args);
        }

        throw new Exception("Invalid route action format");
    }

    /**
     * Handle 404 Not Found
     */
    private function handle404(): void {
        http_response_code(404);

        $request = Request::capture();

        // Check if JSON response is expected
        if ($request->wantsJson() || $request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Not Found',
                'error' => 'The requested resource was not found'
            ]);
            exit;
        }

        // Render 404 view
        // Router.php is in core/routing/, views are now in mvc/views/ at root level
        $viewPath = __DIR__ . '/../../mvc/views/pages/404.php';
        if (file_exists($viewPath)) {
            include __DIR__ . '/../../mvc/views/layout.php';
        } else {
            echo "<h1>404 - Not Found</h1>";
            echo "<p>The page you are looking for could not be found.</p>";
        }
        exit;
    }

    /**
     * Get current language
     */
    public function getLanguage(): string {
        return $this->lang;
    }

    /**
     * Get current URI (without language prefix)
     */
    public function getUri(): string {
        return $this->uri;
    }

    /**
     * Get current HTTP method
     */
    public function getMethod(): string {
        return $this->method;
    }

    private function debugLog(string $message): void
    {
        Logger::debug($message);
    }
}


if (!\class_exists('Router', false) && !\interface_exists('Router', false) && !\trait_exists('Router', false)) {
    \class_alias(__NAMESPACE__ . '\\Router', 'Router');
}
