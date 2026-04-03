<?php

namespace App\Core\mvc;


use App\Core\http\Request;
use App\Core\view\ViewEngine;use BadMethodCallException;
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

// core/mvc/Controller.php

class Controller {
    protected $request;

    public function __construct() {
        $this->request = Request::capture();
    }

    /**
     * Return JSON response
     */
    protected function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Return success JSON response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Return error JSON response
     */
    protected function error(string $message = 'Error', $errors = null, int $statusCode = 400): void {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Return validation error response
     */
    protected function validationError(array $errors): void {
        $this->error('Validation failed', $errors, 422);
    }

    /**
     * Validate request
     */
    protected function validate(array $rules): bool|array {
        $errors = $this->request->validate($rules);

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }

    /**
     * Render view with layout
     * 
     * Supports both template engine (.template.php) and regular PHP views (.php)
     */
    protected function view(string $viewName, array $data = []): void {
        // Get global router for language
        global $router;
        $lang = $router->lang ?? 'sr';
        $data['lang'] = $lang;
        $data['router'] = $router;

        // Check if template file exists (.template.php)
        $templatePath = __DIR__ . "/../../mvc/views/pages/{$viewName}.template.php";
        $viewPath = __DIR__ . "/../../mvc/views/pages/{$viewName}.php";
        
        $isTemplate = file_exists($templatePath);
        $isRegularView = file_exists($viewPath);

        if ($isTemplate) {
            // Use template engine
            try {
                $viewContent = ViewEngine::render("pages/{$viewName}", $data);
                
                // Check if template extends a layout by looking for @extends directive
                $templateContent = file_get_contents($templatePath);
                $extendsLayout = preg_match('/@extends\([\'"](.+?)[\'"]\)/', $templateContent);
                
                if ($extendsLayout) {
                    // Template extends a layout, output directly (ViewEngine already compiled it)
                    echo $viewContent;
                } else {
                    // Template doesn't extend layout, use default layout wrapper
                    extract($data);
                    $data['viewContent'] = $viewContent;
                    extract($data);
                    include __DIR__ . "/../../mvc/views/layout.php";
                }
                return;
            } catch (Exception $e) {
                error_log("Template engine error: " . $e->getMessage());
                if (is_debug()) {
                    throw $e;
                }
                // Fallback to regular PHP view
            }
        }

        if ($isRegularView) {
            // Use regular PHP view with layout
            extract($data);
            $viewPath = $viewPath;
            include __DIR__ . "/../../mvc/views/layout.php";
            return;
        }

        // View not found, use 404
        error_log("Controller::view() - View not found: {$viewName}");
        if (file_exists(__DIR__ . "/../../mvc/views/pages/404.php")) {
            extract($data);
            $viewPath = __DIR__ . "/../../mvc/views/pages/404.php";
            include __DIR__ . "/../../mvc/views/layout.php";
        } else {
            http_response_code(404);
            echo "404 - Page not found";
        }
    }

    /**
     * Redirect to URL (ensures HTTPS for same-host redirects)
     */
    protected function redirect(string $url, int $statusCode = 302): void {
        // Convert relative URLs to absolute HTTPS URLs for same-host redirects
        $url = $this->ensureHttpsUrl($url);
        
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect back
     */
    protected function redirectBack(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * Ensure URL is HTTPS (for same-host redirects)
     * Converts relative URLs to absolute HTTPS URLs
     * Leaves external URLs unchanged if they're already absolute
     */
    private function ensureHttpsUrl(string $url): string
    {
        // If URL is already absolute (starts with http:// or https://), check if it's same host
        if (preg_match('/^https?:\/\//', $url)) {
            // Extract host from URL
            $parsedUrl = parse_url($url);
            $urlHost = $parsedUrl['host'] ?? '';
            $currentHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
            
            // If same host, force HTTPS
            if ($urlHost === $currentHost && str_starts_with($url, 'http://')) {
                return str_replace('http://', 'https://', $url);
            }
            
            // External URL or already HTTPS - return as is
            return $url;
        }
        
        // Relative URL - convert to absolute HTTPS
        $scheme = 'https';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // Ensure URL starts with /
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }
        
        return "{$scheme}://{$host}{$url}";
    }

    /**
     * Check if request wants JSON
     */
    protected function wantsJson(): bool {
        return $this->request->wantsJson() || $this->request->isAjax();
    }

    /**
     * Abort with error
     */
    protected function abort(int $statusCode = 404, string $message = 'Not Found'): void {
        http_response_code($statusCode);

        if ($this->wantsJson()) {
            $this->error($message, null, $statusCode);
        }

        // Render error page
        $this->view("{$statusCode}", ['message' => $message]);
        exit;
    }
}


if (!\class_exists('Controller', false) && !\interface_exists('Controller', false) && !\trait_exists('Controller', false)) {
    \class_alias(__NAMESPACE__ . '\\Controller', 'Controller');
}
