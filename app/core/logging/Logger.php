<?php

namespace App\Core\logging;


use App\Core\config\Env;use BadMethodCallException;
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
 * Logger Class
 * 
 * Structured logging system with different log levels
 * 
 * Usage:
 *   Logger::info('User created', ['user_id' => 123]);
 *   Logger::error('Failed to save', ['error' => $e->getMessage()]);
 *   Logger::warning('Rate limit exceeded', ['ip' => '192.168.1.1']);
 */
class Logger
{
    private const LOG_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    private static string $logPath = '';
    private static string $minLevel = 'INFO';
    private static string $requestId = '';
    private static ?int $criticalAlertThrottleSeconds = null;

    /**
     * Initialize logger
     */
    private static function init(): void
    {
        if (empty(self::$logPath)) {
            $preferredPath = dirname(__DIR__, 2) . '/storage/logs';
            
            // Try to use preferred path
            if (!is_dir($preferredPath)) {
                @mkdir($preferredPath, 0755, true);
            }
            
            // Check if directory is writable
            if (is_dir($preferredPath) && is_writable($preferredPath)) {
                self::$logPath = $preferredPath;
            } else {
                // Fallback to system temp directory
                $fallbackPath = sys_get_temp_dir() . '/app_logs';
                if (!is_dir($fallbackPath)) {
                    @mkdir($fallbackPath, 0755, true);
                }
                
                if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
                    self::$logPath = $fallbackPath;
                    error_log('WARNING: Could not write to storage/logs, using fallback: ' . $fallbackPath);
                } else {
                    // Last resort: disable file logging
                    self::$logPath = '';
                    error_log('CRITICAL: Could not create or write to log directory. Logging disabled.');
                }
            }
        }
        
        self::$minLevel = self::resolveMinLevel();
        self::ensureRequestId();
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Log message with level
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        self::init();
        
        // Check if level should be logged
        if (self::LOG_LEVELS[$level] < self::LOG_LEVELS[self::$minLevel]) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'request_id' => self::$requestId,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to log file (with error handling)
        if (empty(self::$logPath)) {
            // Logging is disabled, only use error_log for critical messages
            if (in_array($level, ['ERROR', 'CRITICAL'])) {
                error_log("[{$level}] {$message} " . json_encode($context));
            }
            return;
        }
        
        $logFile = self::$logPath . '/app-' . date('Y-m-d') . '.log';
        
        try {
            // Check if directory is writable
            if (!is_writable(self::$logPath)) {
                // Try to make it writable
                @chmod(self::$logPath, 0755);
            }
            
            // Try to write to log file
            $result = @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
            if ($result === false) {
                // If writing fails, try to use system temp directory as fallback
                $fallbackPath = sys_get_temp_dir() . '/app_logs';
                if (!is_dir($fallbackPath)) {
                    @mkdir($fallbackPath, 0755, true);
                }
                
                if (is_writable($fallbackPath)) {
                    $fallbackFile = $fallbackPath . '/app-' . date('Y-m-d') . '.log';
                    @file_put_contents($fallbackFile, $logLine, FILE_APPEND | LOCK_EX);
                }
            }
        } catch (Exception $e) {
            // Silently fail - don't break the application if logging fails
            // Just log to PHP error log as fallback
        }
        
        // Also write to error log if ERROR or CRITICAL
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            error_log("[{$level}] {$message} " . json_encode($context));
        }

        if ($level === 'CRITICAL') {
            self::sendCriticalAlert($message, $context);
        }
    }

    public static function requestId(): string
    {
        self::init();

        return self::$requestId;
    }

    private static function resolveMinLevel(): string
    {
        $configuredLevel = strtoupper((string) Env::get('APP_LOG_LEVEL', ''));
        if ($configuredLevel === '') {
            $configuredLevel = strtoupper((string) Env::get('LOG_LEVEL', ''));
        }

        if ($configuredLevel === '') {
            $appEnv = strtolower((string) Env::get('APP_ENV', 'local'));
            $configuredLevel = in_array($appEnv, ['local', 'development', 'testing'], true)
                ? 'DEBUG'
                : 'INFO';
        }

        return array_key_exists($configuredLevel, self::LOG_LEVELS)
            ? $configuredLevel
            : 'INFO';
    }

    private static function ensureRequestId(): void
    {
        if (self::$requestId !== '') {
            return;
        }

        $incomingRequestId = trim((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($incomingRequestId !== '' && preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $incomingRequestId)) {
            self::$requestId = $incomingRequestId;
        } else {
            try {
                self::$requestId = bin2hex(random_bytes(16));
            } catch (Exception) {
                self::$requestId = uniqid('req-', true);
            }
        }

        $_SERVER['HTTP_X_REQUEST_ID'] = self::$requestId;

        if (!headers_sent() && PHP_SAPI !== 'cli') {
            header('X-Request-Id: ' . self::$requestId);
        }
    }

    private static function sendCriticalAlert(string $message, array $context): void
    {
        $alertEmail = trim((string) Env::get('APP_LOG_ALERT_EMAIL', ''));
        if ($alertEmail === '' || !filter_var($alertEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $throttleSeconds = self::criticalAlertThrottleSeconds();
        $throttleFile = (self::$logPath !== '' ? self::$logPath : sys_get_temp_dir())
            . '/critical-alert-' . sha1($alertEmail . '|' . $message) . '.throttle';

        $lastSentAt = is_file($throttleFile) ? (int) @file_get_contents($throttleFile) : 0;
        if ($lastSentAt > 0 && (time() - $lastSentAt) < $throttleSeconds) {
            return;
        }

        $subject = '[AP CRITICAL] ' . mb_substr($message, 0, 120);
        $body = json_encode([
            'timestamp' => date('c'),
            'request_id' => self::$requestId,
            'message' => $message,
            'context' => $context,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($body !== false && @mail($alertEmail, $subject, $body)) {
            @file_put_contents($throttleFile, (string) time(), LOCK_EX);
        }
    }

    private static function criticalAlertThrottleSeconds(): int
    {
        if (self::$criticalAlertThrottleSeconds !== null) {
            return self::$criticalAlertThrottleSeconds;
        }

        $value = (int) Env::get('APP_LOG_ALERT_THROTTLE_SECONDS', 300);
        self::$criticalAlertThrottleSeconds = max(60, $value);

        return self::$criticalAlertThrottleSeconds;
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
}


if (!\class_exists('Logger', false) && !\interface_exists('Logger', false) && !\trait_exists('Logger', false)) {
    \class_alias(__NAMESPACE__ . '\\Logger', 'Logger');
}
