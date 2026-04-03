<?php

namespace App\Core\security;

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

/**
 * CSRF Protection Class
 *
 * Usage:
 *   // In form:
 *   <?= CSRF::field() ?>
 *
 *   // In AJAX (meta tag):
 *   <?= CSRF::meta() ?>
 *
 *   // Verify in controller:
 *   if (!CSRF::verify()) { abort(403); }
 *
 *   // Or use middleware
 */
class CSRF
{
    private const TOKEN_NAME = '_csrf_token';
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 hour

    /**
     * Generate or get existing CSRF token
     */
    public static function token(): string
    {
        self::ensureSession();

        // Check if token exists and is still valid
        if (isset($_SESSION[self::TOKEN_NAME]) && isset($_SESSION[self::TOKEN_NAME . '_time'])) {
            $tokenTime = $_SESSION[self::TOKEN_NAME . '_time'];
            if (time() - $tokenTime < self::TOKEN_LIFETIME) {
                return $_SESSION[self::TOKEN_NAME];
            }
        }

        // Generate new token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;
        $_SESSION[self::TOKEN_NAME . '_time'] = time();

        return $token;
    }

    /**
     * Get hidden input field with CSRF token
     */
    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . Security::escape($token) . '">';
    }

    /**
     * Get meta tag for AJAX requests
     */
    public static function meta(): string
    {
        $token = self::token();
        return '<meta name="csrf-token" content="' . Security::escape($token) . '">';
    }

    /**
     * Get token name (for JavaScript)
     */
    public static function tokenName(): string
    {
        return self::TOKEN_NAME;
    }

    /**
     * Verify CSRF token from request
     *
     * @param string|null $token Token to verify (auto-detect from POST/header if null)
     * @return bool True if valid
     */
    public static function verify(?string $token = null): bool
    {
        self::ensureSession();

        // Get token from various sources if not provided
        if ($token === null) {
            $token = self::getTokenFromRequest();
        }

        if (empty($token)) {
            return false;
        }

        // Get stored token
        $storedToken = $_SESSION[self::TOKEN_NAME] ?? null;
        $tokenTime = $_SESSION[self::TOKEN_NAME . '_time'] ?? 0;

        if (empty($storedToken)) {
            return false;
        }

        // Check expiration
        if (time() - $tokenTime > self::TOKEN_LIFETIME) {
            self::regenerate();
            return false;
        }

        // Timing-safe comparison
        return hash_equals($storedToken, $token);
    }

    /**
     * Get token from various request sources
     */
    private static function getTokenFromRequest(): ?string
    {
        // Check POST data
        if (isset($_POST[self::TOKEN_NAME])) {
            return $_POST[self::TOKEN_NAME];
        }

        // Check header (for AJAX)
        $headers = [
            'HTTP_X_CSRF_TOKEN',
            'HTTP_X_XSRF_TOKEN'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }

        // Check JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (isset($json[self::TOKEN_NAME])) {
                return $json[self::TOKEN_NAME];
            }
        }

        return null;
    }

    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerate(): string
    {
        self::ensureSession();

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;
        $_SESSION[self::TOKEN_NAME . '_time'] = time();

        return $token;
    }

    /**
     * Clear CSRF token
     */
    public static function clear(): void
    {
        self::ensureSession();
        unset($_SESSION[self::TOKEN_NAME], $_SESSION[self::TOKEN_NAME . '_time']);
    }

    /**
     * Ensure session is started
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get JavaScript code for AJAX setup
     */
    public static function ajaxSetup(): string
    {
        $token = self::token();
        $name = self::TOKEN_NAME;

        return <<<JS
<script>
// CSRF Token for AJAX requests
(function() {
    const csrfToken = '{$token}';
    const csrfName = '{$name}';

    // For fetch API
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        options.headers = options.headers || {};
        if (!(options.headers instanceof Headers)) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        return originalFetch(url, options);
    };

    // For XMLHttpRequest
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        const result = originalOpen.apply(this, arguments);
        this.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        return result;
    };

    // Export for manual use
    window.CSRF = { token: csrfToken, name: csrfName };
})();
</script>
JS;
    }
}


if (!\class_exists('CSRF', false) && !\interface_exists('CSRF', false) && !\trait_exists('CSRF', false)) {
    \class_alias(__NAMESPACE__ . '\\CSRF', 'CSRF');
}
