<?php

namespace App\Core\config;

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

// core/config/Env.php

class Env {
    private static array $variables = [];
    private static bool $loaded = false;

    /**
     * Load environment variables from .env file.
     * If the file is missing or unreadable, continue with getenv / $_ENV only.
     */
    public static function load(string $path): void {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            self::$loaded = true;
            error_log("Env::load - no file at {$path}; using server environment variables only. Add app/.env or set vars in the panel.");
            return;
        }

        if (!is_readable($path)) {
            self::$loaded = true;
            error_log("Env::load - file exists but is not readable at {$path}; using server environment variables only.");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            error_log("Env::load - file() failed for {$path}; using server environment variables only.");
            return;
        }

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                // Convert string booleans to actual booleans
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                } elseif (strtolower($value) === 'null') {
                    $value = null;
                }

                // Store in array and $_ENV
                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Check if an environment variable exists
     *
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool {
        return isset(self::$variables[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Get all environment variables
     *
     * @return array
     */
    public static function all(): array {
        return self::$variables;
    }
}


if (!\class_exists('Env', false) && !\interface_exists('Env', false) && !\trait_exists('Env', false)) {
    \class_alias(__NAMESPACE__ . '\\Env', 'Env');
}
