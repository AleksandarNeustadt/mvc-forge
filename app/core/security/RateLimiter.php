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
 * Rate Limiter Class
 *
 * Simple file-based rate limiting for API and form submissions.
 *
 * Usage:
 *   // Check rate limit
 *   if (!RateLimiter::attempt('login', 5, 60)) {
 *       die('Too many attempts. Try again later.');
 *   }
 *
 *   // Custom key (per user)
 *   RateLimiter::attempt('api:' . $userId, 100, 3600);
 *
 *   // Check remaining attempts
 *   $remaining = RateLimiter::remaining('login');
 */
class RateLimiter
{
    private static string $storagePath = '';

    /**
     * Initialize storage path
     */
    private static function init(): void
    {
        if (empty(self::$storagePath)) {
            self::$storagePath = dirname(__DIR__, 2) . '/storage/rate_limits';

            if (!is_dir(self::$storagePath)) {
                // Attempt to create the directory
                if (!@mkdir(self::$storagePath, 0755, true)) {
                    // If creation fails, fall back to system temp directory
                    self::$storagePath = sys_get_temp_dir() . '/app_rate_limits';
                    if (!is_dir(self::$storagePath)) {
                        if (!@mkdir(self::$storagePath, 0755, true)) {
                            // If temp directory creation also fails, log error and disable rate limiting
                            error_log('CRITICAL: Rate limit directory could not be created or is not writable: ' . dirname(__DIR__, 2) . '/storage/rate_limits' . ' or ' . sys_get_temp_dir() . '/app_rate_limits');
                            self::$storagePath = ''; // Disable rate limiting
                        } else {
                            error_log('WARNING: Could not create rate limit directory in storage, falling back to system temp: ' . self::$storagePath);
                        }
                    } else {
                        error_log('WARNING: Could not create rate limit directory in storage, falling back to system temp: ' . self::$storagePath);
                    }
                }
            }
        }
    }

    /**
     * Attempt an action with rate limiting
     *
     * @param string $key Unique identifier (e.g., 'login', 'api:user123')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public static function attempt(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
    {
        self::init();

        $identifier = self::getIdentifier($key);
        $data = self::getData($identifier);
        $now = time();

        // Clean old entries
        $data = array_filter($data, fn($timestamp) => $timestamp > $now - $decaySeconds);

        // Check if over limit
        if (count($data) >= $maxAttempts) {
            return false;
        }

        // Add new attempt
        $data[] = $now;
        self::saveData($identifier, $data);

        return true;
    }

    /**
     * Check if rate limited without incrementing
     */
    public static function tooManyAttempts(string $key, int $maxAttempts = 60, int $decaySeconds = 60): bool
    {
        self::init();

        $identifier = self::getIdentifier($key);
        $data = self::getData($identifier);
        $now = time();

        // Clean old entries
        $data = array_filter($data, fn($timestamp) => $timestamp > $now - $decaySeconds);

        return count($data) >= $maxAttempts;
    }

    /**
     * Get remaining attempts
     */
    public static function remaining(string $key, int $maxAttempts = 60, int $decaySeconds = 60): int
    {
        self::init();

        $identifier = self::getIdentifier($key);
        $data = self::getData($identifier);
        $now = time();

        // Clean old entries
        $data = array_filter($data, fn($timestamp) => $timestamp > $now - $decaySeconds);

        return max(0, $maxAttempts - count($data));
    }

    /**
     * Get seconds until rate limit resets
     */
    public static function availableIn(string $key, int $decaySeconds = 60): int
    {
        self::init();

        $identifier = self::getIdentifier($key);
        $data = self::getData($identifier);

        if (empty($data)) {
            return 0;
        }

        $oldest = min($data);
        $availableAt = $oldest + $decaySeconds;

        return max(0, $availableAt - time());
    }

    /**
     * Clear rate limit for a key
     */
    public static function clear(string $key): void
    {
        self::init();

        // If storage path is empty (disabled), silently fail
        if (empty(self::$storagePath)) {
            return;
        }

        $identifier = self::getIdentifier($key);
        $file = self::$storagePath . '/' . $identifier . '.json';

        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Hit the rate limiter (increment without checking)
     */
    public static function hit(string $key): void
    {
        self::init();

        $identifier = self::getIdentifier($key);
        $data = self::getData($identifier);
        $data[] = time();
        self::saveData($identifier, $data);
    }

    /**
     * Get unique identifier including IP
     */
    private static function getIdentifier(string $key): string
    {
        $ip = self::getClientIp();
        return md5($key . ':' . $ip);
    }

    /**
     * Get rate limit data from file
     */
    private static function getData(string $identifier): array
    {
        // If storage path is empty (disabled), return empty array
        if (empty(self::$storagePath)) {
            return [];
        }
        
        $file = self::$storagePath . '/' . $identifier . '.json';

        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return [];
        }
        
        return json_decode($content, true) ?? [];
    }

    /**
     * Save rate limit data to file
     */
    private static function saveData(string $identifier, array $data): void
    {
        // If storage path is empty (disabled), silently fail
        if (empty(self::$storagePath)) {
            return;
        }
        
        $file = self::$storagePath . '/' . $identifier . '.json';
        
        // Check if directory is writable
        if (!is_writable(self::$storagePath)) {
            error_log('WARNING: Rate limit directory is not writable: ' . self::$storagePath);
            return;
        }
        
        // Attempt to write, catch potential errors
        if (@file_put_contents($file, json_encode(array_values($data)), LOCK_EX) === false) {
            // Fallback to error log if file_put_contents fails
            error_log("RateLimiter failed to write to {$file}. Rate limiting may not work correctly.");
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
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
     * Clean up old rate limit files (run periodically)
     */
    public static function cleanup(int $maxAge = 86400): int
    {
        self::init();

        // If storage path is empty (disabled), return 0
        if (empty(self::$storagePath)) {
            return 0;
        }

        $deleted = 0;
        $files = @glob(self::$storagePath . '/*.json');
        
        if ($files === false) {
            return 0;
        }
        
        $now = time();

        foreach ($files as $file) {
            if (file_exists($file) && ($now - filemtime($file) > $maxAge)) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}


if (!\class_exists('RateLimiter', false) && !\interface_exists('RateLimiter', false) && !\trait_exists('RateLimiter', false)) {
    \class_alias(__NAMESPACE__ . '\\RateLimiter', 'RateLimiter');
}
