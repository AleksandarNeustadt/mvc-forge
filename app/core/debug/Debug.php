<?php

namespace App\Core\debug;


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
 * Debug Class - Development debugging utilities
 *
 * Provides dd() and dp() functions similar to Laravel.
 * Only works when APP_DEBUG=true in .env
 */
class Debug
{
    private static bool $enabled = false;
    private static bool $initialized = false;

    /**
     * Initialize debug mode from environment
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$enabled = Env::get('APP_DEBUG', false) === true
            || Env::get('APP_DEBUG', 'false') === 'true'
            || Env::get('APP_DEBUG', '0') === '1';

        self::$initialized = true;
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isEnabled(): bool
    {
        self::init();
        return self::$enabled;
    }

    /**
     * Dump and die - dump variables and stop execution
     *
     * @param mixed ...$vars Variables to dump
     */
    public static function dd(mixed ...$vars): never
    {
        self::init();

        if (!self::$enabled) {
            http_response_code(500);
            echo '<h1>500 Internal Server Error</h1>';
            exit;
        }

        // Get caller info
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0];

        self::renderOutput($vars, $caller, true);
        exit;
    }

    /**
     * Dump and print - dump variables without stopping
     *
     * @param mixed ...$vars Variables to dump
     */
    public static function dp(mixed ...$vars): void
    {
        self::init();

        if (!self::$enabled) {
            return;
        }

        // Get caller info
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0];

        self::renderOutput($vars, $caller, false);
    }

    /**
     * Log to file (works in production too)
     */
    public static function log(mixed $data, string $label = 'DEBUG'): void
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/debug.log';
        $timestamp = date('Y-m-d H:i:s');

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $trace[0];
        $location = ($caller['file'] ?? 'unknown') . ':' . ($caller['line'] ?? 0);

        $output = "[{$timestamp}] [{$label}] {$location}\n";
        $output .= print_r($data, true) . "\n";
        $output .= str_repeat('-', 80) . "\n";

        // Ensure directory exists
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($logFile, $output, FILE_APPEND | LOCK_EX);
    }

    /**
     * Render debug output
     */
    private static function renderOutput(array $vars, array $caller, bool $isDie): void
    {
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;
        $function = $caller['function'] ?? '';

        // Check if it's an AJAX/JSON request
        $isJson = self::wantsJson();

        if ($isJson) {
            self::renderJson($vars, $file, $line, $isDie);
        } else {
            self::renderHtml($vars, $file, $line, $isDie);
        }
    }

    /**
     * Render JSON output
     */
    private static function renderJson(array $vars, string $file, int $line, bool $isDie): void
    {
        header('Content-Type: application/json');

        $output = [
            '_debug' => [
                'file' => $file,
                'line' => $line,
                'type' => $isDie ? 'dd' : 'dp',
                'time' => date('Y-m-d H:i:s')
            ],
            'data' => count($vars) === 1 ? $vars[0] : $vars
        ];

        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Render HTML output
     */
    private static function renderHtml(array $vars, string $file, int $line, bool $isDie): void
    {
        $typeLabel = $isDie ? 'dd()' : 'dp()';
        $relativeFile = str_replace(dirname(__DIR__, 2) . '/', '', $file);

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Debug Output - <?= htmlspecialchars($typeLabel) ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
                    background: #1e1e1e;
                    color: #d4d4d4;
                    padding: 20px;
                    line-height: 1.6;
                }

                .debug-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: #252526;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                }

                .debug-header {
                    background: <?= $isDie ? '#c72525' : '#007acc' ?>;
                    color: white;
                    padding: 15px 20px;
                    font-weight: bold;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .debug-header .type {
                    font-size: 18px;
                }

                .debug-header .location {
                    font-size: 12px;
                    opacity: 0.9;
                }

                .debug-content {
                    padding: 20px;
                }

                .debug-item {
                    margin-bottom: 20px;
                    background: #1e1e1e;
                    border-radius: 4px;
                    padding: 15px;
                    border-left: 3px solid <?= $isDie ? '#c72525' : '#007acc' ?>;
                }

                .debug-item:last-child {
                    margin-bottom: 0;
                }

                .debug-item-label {
                    color: #569cd6;
                    font-weight: bold;
                    margin-bottom: 10px;
                    font-size: 14px;
                }

                pre {
                    background: #1e1e1e;
                    padding: 15px;
                    border-radius: 4px;
                    overflow-x: auto;
                    font-size: 13px;
                    line-height: 1.5;
                }

                .type-string { color: #ce9178; }
                .type-number { color: #b5cea8; }
                .type-boolean { color: #569cd6; }
                .type-null { color: #808080; }
                .type-array { color: #4ec9b0; }
                .type-object { color: #4ec9b0; }

                .array-key { color: #9cdcfe; }
                .array-bracket { color: #808080; }

                .footer {
                    text-align: center;
                    padding: 15px;
                    color: #808080;
                    font-size: 12px;
                    border-top: 1px solid #3e3e42;
                }
            </style>
        </head>
        <body>
            <div class="debug-container">
                <div class="debug-header">
                    <span class="type"><?= htmlspecialchars($typeLabel) ?></span>
                    <span class="location"><?= htmlspecialchars($relativeFile) ?>:<?= $line ?></span>
                </div>
                <div class="debug-content">
                    <?php foreach ($vars as $index => $var): ?>
                        <div class="debug-item">
                            <div class="debug-item-label">Variable #<?= $index + 1 ?></div>
                            <pre><?= self::formatVar($var) ?></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="footer">
                    Debug output generated at <?= date('Y-m-d H:i:s') ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Format variable for display
     */
    private static function formatVar(mixed $var, int $depth = 0): string
    {
        if ($depth > 10) {
            return '<span class="type-null">... (max depth reached)</span>';
        }

        $type = gettype($var);

        switch ($type) {
            case 'string':
                $escaped = htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
                $length = strlen($var);
                return "<span class=\"type-string\">\"{$escaped}\"</span> <span style=\"color: #808080;\">(length: {$length})</span>";

            case 'integer':
            case 'double':
                return "<span class=\"type-number\">{$var}</span>";

            case 'boolean':
                $value = $var ? 'true' : 'false';
                return "<span class=\"type-boolean\">{$value}</span>";

            case 'NULL':
                return "<span class=\"type-null\">null</span>";

            case 'array':
                if (empty($var)) {
                    return "<span class=\"type-array\">array()</span> <span style=\"color: #808080;\">(empty)</span>";
                }

                $output = "<span class=\"array-bracket\">array</span> <span class=\"array-bracket\">(</span>\n";
                $indent = str_repeat('    ', $depth + 1);

                foreach ($var as $key => $value) {
                    $keyStr = is_string($key) ? "\"{$key}\"" : $key;
                    $output .= "{$indent}<span class=\"array-key\">[{$keyStr}]</span> => " . self::formatVar($value, $depth + 1) . ",\n";
                }

                $indent = str_repeat('    ', $depth);
                $output .= "{$indent}<span class=\"array-bracket\">)</span> <span style=\"color: #808080;\">(count: " . count($var) . ")</span>";
                return $output;

            case 'object':
                $className = get_class($var);
                $output = "<span class=\"type-object\">object({$className})</span> <span class=\"array-bracket\">{</span>\n";
                $indent = str_repeat('    ', $depth + 1);

                $reflection = new ReflectionObject($var);
                $properties = $reflection->getProperties();

                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    $key = $property->getName();
                    $value = $property->getValue($var);
                    $output .= "{$indent}<span class=\"array-key\">{$key}</span> => " . self::formatVar($value, $depth + 1) . ",\n";
                }

                $indent = str_repeat('    ', $depth);
                $output .= "{$indent}<span class=\"array-bracket\">}</span>";
                return $output;

            default:
                return "<span style=\"color: #808080;\">({$type})</span> " . htmlspecialchars(print_r($var, true));
        }
    }

    /**
     * Check if request wants JSON
     */
    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false
            || strpos($accept, 'text/json') !== false
            || isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Measure execution time and memory
     */
    public static function measure(callable $callback, string $label = 'Execution'): mixed
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();

        $result = $callback();

        $time = (microtime(true) - $start) * 1000;
        $memory = memory_get_usage() - $startMemory;

        self::dp([
            'label' => $label,
            'time_ms' => round($time, 4),
            'memory' => self::formatBytes($memory),
            'result' => $result
        ]);

        return $result;
    }

    /**
     * Dump SQL query with bindings
     */
    public static function sql(string $query, array $bindings = []): void
    {
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $query = preg_replace('/\?/', (string) $value, $query, 1);
        }

        self::dp(['SQL' => $query, 'Bindings' => $bindings]);
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}


if (!\class_exists('Debug', false) && !\interface_exists('Debug', false) && !\trait_exists('Debug', false)) {
    \class_alias(__NAMESPACE__ . '\\Debug', 'Debug');
}
