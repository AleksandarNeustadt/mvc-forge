<?php

namespace App\Core\http;


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

/**
 * Input Class - Safe Request Data Handling
 *
 * Usage:
 *   $name = Input::get('name');                    // From $_GET
 *   $email = Input::post('email', '', 'email');    // From $_POST, sanitized as email
 *   $id = Input::get('id', 0, 'int');              // With default and type
 *   $all = Input::all();                            // All input data
 *   $only = Input::only(['name', 'email']);         // Only specific fields
 */
class Input
{
    /**
     * Get value from $_GET with sanitization
     *
     * @param string $key Key name
     * @param mixed $default Default value if key doesn't exist
     * @param string $sanitize Sanitization type (string, int, email, etc.)
     * @return mixed Sanitized value
     */
    public static function get(string $key, mixed $default = null, string $sanitize = 'string'): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        return Security::sanitize($_GET[$key], $sanitize);
    }

    /**
     * Get value from $_POST with sanitization
     */
    public static function post(string $key, mixed $default = null, string $sanitize = 'string'): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        return Security::sanitize($_POST[$key], $sanitize);
    }

    /**
     * Get value from $_REQUEST (GET + POST) with sanitization
     */
    public static function input(string $key, mixed $default = null, string $sanitize = 'string'): mixed
    {
        if (!isset($_REQUEST[$key])) {
            return $default;
        }
        return Security::sanitize($_REQUEST[$key], $sanitize);
    }

    /**
     * Get value from $_SERVER with sanitization
     */
    public static function server(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get value from $_COOKIE with sanitization
     */
    public static function cookie(string $key, mixed $default = null, string $sanitize = 'string'): mixed
    {
        if (!isset($_COOKIE[$key])) {
            return $default;
        }
        return Security::sanitize($_COOKIE[$key], $sanitize);
    }

    /**
     * Get uploaded file info
     *
     * @param string $key File input name
     * @return array|null File info array or null
     */
    public static function file(string $key): ?array
    {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$key];

        // Sanitize filename
        $file['safe_name'] = Security::sanitize($file['name'], 'filename');

        // Add file extension
        $file['extension'] = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        return $file;
    }

    /**
     * Check if file was uploaded
     */
    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Get all input data (GET + POST) sanitized
     *
     * @param string $sanitize Default sanitization type
     * @return array All sanitized input
     */
    public static function all(string $sanitize = 'string'): array
    {
        $data = [];

        foreach ($_GET as $key => $value) {
            $data[$key] = Security::sanitize($value, $sanitize);
        }

        foreach ($_POST as $key => $value) {
            $data[$key] = Security::sanitize($value, $sanitize);
        }

        return $data;
    }

    /**
     * Get only specific fields
     *
     * @param array $keys Keys to retrieve ['field' => 'type'] or ['field1', 'field2']
     * @return array Filtered and sanitized data
     */
    public static function only(array $keys): array
    {
        $data = [];

        foreach ($keys as $key => $type) {
            
            if (is_numeric($key)) {
                $field = $type;
                $type = 'string';
            } else {
                $field = $key;
            }

            $data[$field] = self::input($field, null, $type);
        }

        return $data;
    }

    /**
     * Get all input except specified fields
     *
     * @param array $keys Keys to exclude
     * @return array Filtered data
     */
    public static function except(array $keys): array
    {
        $data = self::all();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Check if input exists
     */
    public static function has(string $key): bool
    {
        return isset($_REQUEST[$key]);
    }

    /**
     * Check if input exists and is not empty
     */
    public static function filled(string $key): bool
    {
        return isset($_REQUEST[$key]) && $_REQUEST[$key] !== '';
    }

    /**
     * Get raw input (JSON body, etc.)
     *
     * @return string Raw input
     */
    public static function raw(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Get JSON input as array
     *
     * @param string|null $key Specific key from JSON (dot notation supported)
     * @param mixed $default Default value
     * @return mixed Decoded JSON or specific value
     */
    public static function json(?string $key = null, mixed $default = null): mixed
    {
        static $json = null;

        if ($json === null) {
            $raw = self::raw();
            $json = json_decode($raw, true) ?? [];
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
     * Get bearer token from Authorization header
     */
    public static function bearerToken(): ?string
    {
        // Try multiple possible locations for Authorization header
        $header = null;
        
        // 1. Try native PHP functions first (most reliable)
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers) {
                // Headers might be case-sensitive, check both
                $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            }
        }
        
        // 2. Try $_SERVER variables (common locations)
        if (!$header) {
            $header = self::server('HTTP_AUTHORIZATION') 
                   ?? self::server('REDIRECT_HTTP_AUTHORIZATION')
                   ?? self::server('Authorization');
        }
        
        // 3. Try parsing from all $_SERVER headers manually
        if (!$header) {
            foreach ($_SERVER as $key => $value) {
                if (strtoupper($key) === 'HTTP_AUTHORIZATION' || strtoupper($key) === 'REDIRECT_HTTP_AUTHORIZATION') {
                    $header = $value;
                    break;
                }
            }
        }

        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get client IP address
     */
    public static function ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Direct
        ];

        foreach ($headers as $header) {
            $ip = self::server($header);
            if ($ip) {
                // Handle comma-separated IPs (X-Forwarded-For)
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
    public static function userAgent(): string
    {
        return self::server('HTTP_USER_AGENT', '');
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax(): bool
    {
        return self::server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Check if request method matches
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper(self::server('REQUEST_METHOD', 'GET')) === strtoupper($method);
    }

    /**
     * Get request method
     */
    public static function method(): string
    {
        return strtoupper(self::server('REQUEST_METHOD', 'GET'));
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public static function isSecure(): bool
    {
        return self::server('HTTPS') === 'on'
            || self::server('HTTP_X_FORWARDED_PROTO') === 'https'
            || self::server('SERVER_PORT') === '443';
    }

    /**
     * Get referer URL
     */
    public static function referer(): ?string
    {
        $referer = self::server('HTTP_REFERER');
        return $referer ? Security::sanitize($referer, 'url') : null;
    }

    /**
     * Validate input and return errors
     *
     * @param array $rules Validation rules
     * @return array Errors (empty if valid)
     */
    public static function validate(array $rules): array
    {
        return Security::validateAll(self::all('raw'), $rules);
    }

    /**
     * Validate and throw exception on failure
     *
     * @param array $rules Validation rules
     * @throws InvalidArgumentException If validation fails
     */
    public static function validateOrFail(array $rules): void
    {
        $errors = self::validate($rules);
        if (!empty($errors)) {
            throw new InvalidArgumentException(json_encode($errors));
        }
    }
}


if (!\class_exists('Input', false) && !\interface_exists('Input', false) && !\trait_exists('Input', false)) {
    \class_alias(__NAMESPACE__ . '\\Input', 'Input');
}
