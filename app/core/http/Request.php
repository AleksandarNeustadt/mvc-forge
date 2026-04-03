<?php

namespace App\Core\http;


use App\Core\logging\Logger;
use App\Core\security\Security;use BadMethodCallException;
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

// core/http/Request.php

class Request {
    private $method;
    private $uri;
    private $query = [];
    private $post = [];
    private $files = [];
    private $headers = [];
    private $body = null;
    private $input = [];

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        
        // For POST requests, PHP automatically parses multipart/form-data and populates $_POST
        // However, if $_POST is empty, it might be a server configuration issue
        $this->post = $_POST;
        $this->files = $_FILES;
        
        // Debug: Log if POST is empty but we have multipart/form-data
        if ($this->method === 'POST' && empty($this->post) && !empty($_SERVER['CONTENT_TYPE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'];
            if (strpos($contentType, 'multipart/form-data') !== false) {
                Logger::warning('Multipart POST parsed with empty $_POST payload', [
                    'content_type' => $contentType,
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'NOT SET',
                    'post_keys' => array_keys($_POST),
                    'file_keys' => array_keys($_FILES),
                ]);
            }
        }
        
        $this->headers = $this->parseHeaders();
        
        // For multipart/form-data, PHP automatically populates $_POST and $_FILES
        // Don't read php://input for multipart/form-data as it can interfere with parsing
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;
        
        if (!$isMultipart) {
            $this->parseBody();
        } else {
            // For multipart/form-data, body should be empty (data is in $_POST and $_FILES)
            $this->body = null;
        }

        // Merge all input sources for easy access
        // Ensure body is always an array
        $bodyArray = is_array($this->body) ? $this->body : [];
        $this->input = array_merge($this->query, $this->post, $bodyArray);
    }

    /**
     * Get HTTP method
     */
    public function method(): string {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function uri(): string {
        return $this->uri;
    }

    /**
     * Get request path
     */
    public function path(): string {
        return $this->uri;
    }

    /**
     * Get query parameters
     */
    public function query(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    public function post(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all input (query + post + body)
     */
    public function input(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->input;
        }
        return $this->input[$key] ?? $default;
    }

    /**
     * Get all input data (alias for input() without key)
     */
    public function all(): array {
        return $this->input ?? [];
    }

    /**
     * Check if input key exists
     */
    public function has(string $key): bool {
        return isset($this->input[$key]);
    }

    /**
     * Get files
     */
    public function files(?string $key = null): mixed {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    /**
     * Get headers
     */
    public function headers(?string $key = null): mixed {
        if ($key === null) {
            return $this->headers;
        }
        $key = strtolower($key);
        return $this->headers[$key] ?? null;
    }

    /**
     * Get header value
     */
    public function header(string $key, mixed $default = null): mixed {
        return $this->headers($key) ?? $default;
    }

    /**
     * Get request body
     */
    public function body(): ?string {
        return $this->body;
    }

    /**
     * Get JSON body
     */
    public function json(?string $key = null, mixed $default = null): mixed {
        $json = json_decode($this->body, true);
        
        if ($json === null) {
            return $default;
        }

        if ($key === null) {
            return $json;
        }

        // Support dot notation
        $keys = explode('.', $key);
        $value = $json;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Check if request wants JSON
     */
    public function wantsJson(): bool {
        $accept = $this->header('accept', '');
        return strpos($accept, 'application/json') !== false
            || strpos($accept, 'text/json') !== false;
    }

    /**
     * Check if request method matches
     */
    public function isMethod(string $method): bool {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is PUT
     */
    public function isPut(): bool {
        return $this->isMethod('PUT');
    }

    /**
     * Check if request is DELETE
     */
    public function isDelete(): bool {
        return $this->isMethod('DELETE');
    }

    /**
     * Check if request is PATCH
     */
    public function isPatch(): bool {
        return $this->isMethod('PATCH');
    }

    /**
     * Get client IP
     */
    public function ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? null;
            if ($ip) {
                $ip = trim(explode(',', $ip)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get referer
     */
    public function referer(): ?string {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Get full URL
     */
    public function fullUrl(): string {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Get URL without query string
     */
    public function url(): string {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        return "{$scheme}://{$host}{$this->uri}";
    }

    /**
     * Parse headers from $_SERVER
     */
    private function parseHeaders(): array {
        $headers = [];

        // First, try to get all headers using native PHP functions (if available)
        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            foreach ($apacheHeaders as $key => $value) {
                $headers[strtolower($key)] = $value;
            }
        } elseif (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if ($allHeaders) {
                foreach ($allHeaders as $key => $value) {
                    $headers[strtolower($key)] = $value;
                }
            }
        }

        // Also parse from $_SERVER (for compatibility)
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headerKey = strtolower($header);
                // Only add if not already set (native functions take precedence)
                if (!isset($headers[$headerKey])) {
                    $headers[$headerKey] = $value;
                }
            }
        }

        // Special handling for Authorization header (often not prefixed with HTTP_)
        // Check multiple possible locations
        if (!isset($headers['authorization'])) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['Authorization'])) {
                $headers['authorization'] = $_SERVER['Authorization'];
            }
        }

        return $headers;
    }

    /**
     * Parse request body
     */
    private function parseBody(): void {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $rawBody = file_get_contents('php://input');

        if (strpos($contentType, 'application/json') !== false && !empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            // Store as array if valid JSON, otherwise as string
            $this->body = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $rawBody;
        } else {
            // For other content types, store as string
            $this->body = $rawBody;
        }
    }

    /**
     * Validate request data
     *
     * @param array $rules Validation rules ['field' => 'rule1|rule2', ...]
     * @return array Empty array if valid, otherwise array of errors ['field' => ['error1', 'error2'], ...]
     */
    public function validate(array $rules): array {
        return Security::validateAll($this->input, $rules);
    }

    /**
     * Capture request (factory method)
     */
    public static function capture(): Request {
        return new self();
    }
}


if (!\class_exists('Request', false) && !\interface_exists('Request', false) && !\trait_exists('Request', false)) {
    \class_alias(__NAMESPACE__ . '\\Request', 'Request');
}
