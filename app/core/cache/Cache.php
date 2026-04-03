<?php

namespace App\Core\cache;

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
 * Cache Class
 * 
 * Simple file-based caching system with TTL support
 * 
 * Usage:
 *   Cache::set('key', $value, 3600); // Cache for 1 hour
 *   $value = Cache::get('key');
 *   Cache::forget('key');
 *   Cache::flush();
 */
class Cache
{
    private static string $cachePath = '';
    private static int $defaultTtl = 3600; // 1 hour

    /**
     * Initialize cache directory
     */
    private static function init(): void
    {
        if (empty(self::$cachePath)) {
            self::$cachePath = dirname(__DIR__, 2) . '/storage/cache';

            if (!is_dir(self::$cachePath)) {
                // Try to create directory, but don't fail if permissions are missing
                @mkdir(self::$cachePath, 0755, true);
                
                // If still doesn't exist, disable caching silently
                if (!is_dir(self::$cachePath)) {
                    // Set to a temp directory that should exist, or disable caching
                    self::$cachePath = sys_get_temp_dir() . '/app_cache';
                    @mkdir(self::$cachePath, 0755, true);
                }
            }
        }
    }

    /**
     * Get cache file path
     */
    private static function getCacheFile(string $key): string
    {
        try {
            self::init();
        } catch (Exception $e) {
            // If init fails, use temp directory
            self::$cachePath = sys_get_temp_dir() . '/app_cache';
        }
        $hashedKey = md5($key);
        return self::$cachePath . '/' . $hashedKey . '.cache';
    }

    /**
     * Set cache value
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = default)
     * @return bool Success
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            self::init();
            
            // Check if cache directory is writable
            if (!is_writable(self::$cachePath)) {
                return false;
            }
            
            $ttl = $ttl ?? self::$defaultTtl;
            $expiresAt = time() + $ttl;
            
            $data = [
                'value' => $value,
                'expires_at' => $expiresAt,
                'created_at' => time()
            ];
            
            $file = self::getCacheFile($key);
            $content = serialize($data);

            return self::writeAtomic($file, $content);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cache value
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if not found or expired
     * @return mixed Cached value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            self::init();
            
            $file = self::getCacheFile($key);
            
            if (!file_exists($file)) {
                return $default;
            }
            
            $content = @file_get_contents($file);
            if ($content === false) {
                return $default;
            }
            
            $data = @unserialize($content);
            if ($data === false || !is_array($data)) {
                @unlink($file);
                return $default;
            }
            
            // Check expiration
            if (isset($data['expires_at']) && time() > $data['expires_at']) {
                @self::forget($key);
                return $default;
            }
            
            return $data['value'] ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Check if cache key exists and is valid
     * 
     * @param string $key Cache key
     * @return bool True if exists and valid
     */
    public static function has(string $key): bool
    {
        return self::get($key, '__CACHE_MISS__') !== '__CACHE_MISS__';
    }

    /**
     * Delete cache key
     * 
     * @param string $key Cache key
     * @return bool Success
     */
    public static function forget(string $key): bool
    {
        self::init();
        
        $file = self::getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    /**
     * Clear all cache
     * 
     * @return int Number of files deleted
     */
    public static function flush(): int
    {
        self::init();
        
        $files = glob(self::$cachePath . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Remember value (get or set)
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int|null $ttl Time to live in seconds
     * @return mixed Cached or generated value
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            $value = self::get($key);
            
            if ($value !== null) {
                return $value;
            }
        } catch (Exception $e) {
            // Cache get failed, continue with callback
        }
        
        $value = $callback();
        
        try {
            self::set($key, $value, $ttl);
        } catch (Exception $e) {
            // Cache set failed, but we have the value so continue
        }
        
        return $value;
    }

    /**
     * Get or set cache with tags (for cache invalidation)
     * 
     * @param string $key Cache key
     * @param array $tags Tags for this cache entry
     * @param callable $callback Callback to generate value
     * @param int|null $ttl Time to live
     * @return mixed Cached or generated value
     */
    public static function tagged(string $key, array $tags, callable $callback, ?int $ttl = null): mixed
    {
        // Store tags metadata
        $tagKey = 'tags:' . $key;
        self::set($tagKey, $tags, $ttl);
        
        return self::remember($key, $callback, $ttl);
    }

    /**
     * Invalidate cache by tags
     * 
     * @param array $tags Tags to invalidate
     * @return int Number of cache entries invalidated
     */
    public static function invalidateTags(array $tags): int
    {
        self::init();
        
        $files = glob(self::$cachePath . '/*.cache');
        $invalidated = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);
            
            if (is_array($data) && isset($data['tags'])) {
                $entryTags = $data['tags'];
                if (array_intersect($tags, $entryTags)) {
                    unlink($file);
                    $invalidated++;
                }
            }
        }
        
        return $invalidated;
    }

    /**
     * Clean expired cache entries
     * 
     * @return int Number of files deleted
     */
    public static function clean(): int
    {
        self::init();
        
        $files = glob(self::$cachePath . '/*.cache');
        $deleted = 0;
        $now = time();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);
            
            if (is_array($data) && isset($data['expires_at']) && $now > $data['expires_at']) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    private static function writeAtomic(string $file, string $content): bool
    {
        $tempFile = $file . '.tmp.' . bin2hex(random_bytes(8));

        try {
            if (@file_put_contents($tempFile, $content, LOCK_EX) === false) {
                return false;
            }

            if (!@rename($tempFile, $file)) {
                @unlink($tempFile);
                return false;
            }

            @chmod($file, 0644);
            return true;
        } catch (Throwable) {
            @unlink($tempFile);
            return false;
        }
    }
}


if (!\class_exists('Cache', false) && !\interface_exists('Cache', false) && !\trait_exists('Cache', false)) {
    \class_alias(__NAMESPACE__ . '\\Cache', 'Cache');
}
