<?php

namespace App\Core\view;


use App\Core\security\CSRF;
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
 * Form Facade - Static helper for FormBuilder
 *
 * Usage:
 *   echo Form::open('/login')
 *       ->email('email', 'Email')->required()
 *       ->password('password', 'Password')->required()
 *       ->submit('Login')
 *       ->close();
 *
 *   // Or quick form elements:
 *   <?= Form::csrf() ?>
 *   <?= Form::text('name', 'Your Name') ?>
 */
class Form
{
    /**
     * Create and open a new form
     */
    public static function open(string $action = '', string $method = 'POST'): FormBuilder
    {
        return new FormBuilder($action, $method);
    }

    /**
     * Create form for specific route
     */
    public static function route(string $routeName, string $method = 'POST', array $params = []): FormBuilder
    {
        $url = route($routeName, $params);
        return new FormBuilder($url, $method);
    }

    /**
     * Create form for model (update)
     */
    public static function model(string $action, object $model, string $method = 'PUT'): FormBuilder
    {
        $form = new FormBuilder($action, $method);

        // Convert model to array and use as old values
        $data = method_exists($model, 'toArray') ? $model->toArray() : (array) $model;
        return $form->withOld($data);
    }

    /**
     * Quick CSRF field
     */
    public static function csrf(): string
    {
        return CSRF::field();
    }

    /**
     * Quick hidden field
     */
    public static function hidden(string $name, string $value): string
    {
        return '<input type="hidden" name="' . Security::escape($name) . '" value="' . Security::escape($value) . '">';
    }

    /**
     * Method spoofing for PUT, PATCH, DELETE
     */
    public static function method(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . Security::escape(strtoupper($method)) . '">';
    }

    /**
     * Flash success message to session
     */
    public static function flashSuccess(string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_form_success'] = $message;
    }

    /**
     * Get success message from session
     */
    public static function getSuccess(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $message = $_SESSION['_form_success'] ?? null;
        unset($_SESSION['_form_success']);
        return $message;
    }

    /**
     * Check if form has success message
     */
    public static function hasSuccess(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['_form_success'] ?? null);
    }

    /**
     * Store old input values in session (call before redirect on validation failure)
     */
    public static function flashOld(array $data, array $except = ['password', 'password_confirmation']): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Remove sensitive fields
        foreach ($except as $key) {
            unset($data[$key]);
        }

        $_SESSION['_old_input'] = $data;
    }

    /**
     * Store validation errors in session (call before redirect)
     */
    public static function flashErrors(array $errors): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_form_errors'] = $errors;
    }

    /**
     * Check if form has errors
     */
    public static function hasErrors(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['_form_errors'] ?? []);
    }

    /**
     * Get form errors from session
     */
    public static function getErrors(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['_form_errors'] ?? [];
    }

    /**
     * Get old input value from session
     * Note: This reads from session before FormBuilder clears it
     */
    public static function old(string $key, mixed $default = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check session first (for validation errors)
        if (isset($_SESSION['_old_input'][$key])) {
            return $_SESSION['_old_input'][$key];
        }

        // Return default if not found in session
        return $default;
    }

    /**
     * Check if field has error
     */
    public static function hasError(string $key): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['_form_errors'][$key]);
    }

    /**
     * Get error message for field
     */
    public static function getError(string $key): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $error = $_SESSION['_form_errors'][$key] ?? null;
        if (is_array($error)) {
            return $error[0] ?? '';
        }
        return $error ?? '';
    }

    /**
     * Redirect back with errors and old input
     */
    public static function redirectBack(array $errors = [], array $input = []): never
    {
        self::flashErrors($errors);
        self::flashOld($input);

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $url = self::ensureHttpsUrl($referer);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirect to URL with errors and old input
     */
    public static function redirectTo(string $url, array $errors = [], array $input = []): never
    {
        self::flashErrors($errors);
        self::flashOld($input);

        $url = self::ensureHttpsUrl($url);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Ensure URL is HTTPS (for same-host redirects)
     */
    private static function ensureHttpsUrl(string $url): string
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
     * Validate form input and redirect back on failure
     *
     * @param array $rules Validation rules
     * @return array Validated and sanitized data
     */
    public static function validate(array $rules): array
    {
        $data = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            // Get raw value
            $value = $_POST[$field] ?? $_GET[$field] ?? null;

            // Parse rules
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            // Determine sanitization type from rules
            $sanitizeType = 'string';
            foreach ($ruleList as $rule) {
                if (str_starts_with($rule, 'type:')) {
                    $sanitizeType = substr($rule, 5);
                } elseif ($rule === 'email') {
                    $sanitizeType = 'email';
                } elseif ($rule === 'int' || $rule === 'integer') {
                    $sanitizeType = 'int';
                } elseif ($rule === 'url') {
                    $sanitizeType = 'url';
                }
            }

            // Sanitize
            $data[$field] = Security::sanitize($value, $sanitizeType);

            // Validate
            foreach ($ruleList as $rule) {
                if (str_starts_with($rule, 'type:')) {
                    continue; // Skip type hints
                }

                if (!Security::validate($value, $rule)) {
                    $errors[$field][] = self::getValidationMessage($field, $rule);
                    break; // Only first error per field
                }
            }
        }

        // If errors, redirect back
        if (!empty($errors)) {
            self::redirectBack($errors, $_POST);
        }

        return $data;
    }

    /**
     * Get validation error message
     */
    private static function getValidationMessage(string $field, string $rule): string
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = $parts[1] ?? '';

        $label = ucfirst(str_replace('_', ' ', $field));

        return match($ruleName) {
            'required' => "{$label} je obavezno polje.",
            'email' => "{$label} mora biti validna email adresa.",
            'url' => "{$label} mora biti validan URL.",
            'min' => "{$label} mora biti najmanje {$params}.",
            'max' => "{$label} ne sme biti veće od {$params}.",
            'minLength' => "{$label} mora imati najmanje {$params} karaktera.",
            'maxLength' => "{$label} ne sme imati više od {$params} karaktera.",
            'confirmed' => "{$label} potvrda se ne poklapa.",
            'unique' => "{$label} već postoji.",
            'int', 'integer' => "{$label} mora biti ceo broj.",
            'numeric' => "{$label} mora biti broj.",
            default => "{$label} nije validan.",
        };
    }

    /**
     * Handle file upload securely
     *
     * @param string $field File input name
     * @param string $destination Destination directory
     * @param array $options Options: maxSize, allowedTypes, filename
     * @return array|null Upload result or null on failure
     */
    public static function handleUpload(string $field, string $destination, array $options = []): ?array
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$field];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Greška prilikom upload-a fajla.'];
        }

        // Validate file size
        $maxSize = $options['maxSize'] ?? 5 * 1024 * 1024; // 5MB default
        if ($file['size'] > $maxSize) {
            return ['error' => 'Fajl je prevelik. Maksimalna veličina je ' . self::formatBytes($maxSize) . '.'];
        }

        // Validate file type
        $allowedTypes = $options['allowedTypes'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['error' => 'Tip fajla nije dozvoljen.'];
        }

        // Generate safe filename
        $extension = self::getExtensionFromMime($mimeType);
        $filename = $options['filename'] ?? Security::uuid();
        $filename = Security::sanitize($filename, 'filename') . '.' . $extension;

        // Ensure destination exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $fullPath = rtrim($destination, '/') . '/' . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['error' => 'Nije moguće sačuvati fajl.'];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $fullPath,
            'size' => $file['size'],
            'mime' => $mimeType,
        ];
    }

    /**
     * Get extension from MIME type
     */
    private static function getExtensionFromMime(string $mime): string
    {
        return match($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            default => 'bin',
        };
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}


if (!\class_exists('Form', false) && !\interface_exists('Form', false) && !\trait_exists('Form', false)) {
    \class_alias(__NAMESPACE__ . '\\Form', 'Form');
}
