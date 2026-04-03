<?php

namespace App\Core\exceptions;


use App\Core\config\Env;
use App\Core\http\Request;
use App\Core\logging\Logger;use BadMethodCallException;
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
 * Exception Handler
 * 
 * Centralized exception handling and error reporting
 */
class ExceptionHandler
{
    private static bool $registered = false;

    /**
     * Register exception handlers
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Register exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Register error handler
        set_error_handler([self::class, 'handleError']);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        self::logException($exception);

        // Get request if available (check if class exists first)
        $request = null;
        if (class_exists(Request::class)) {
            try {
                $request = Request::capture();
            } catch (Throwable) {
                $request = null;
            }
        }

        $isDebug = self::isDebugMode();
        $requestId = Logger::requestId();

        // Set appropriate HTTP status code (only if headers not already sent)
        $statusCode = method_exists($exception, 'getStatusCode') 
            ? $exception->getStatusCode() 
            : 500;

        if (!headers_sent()) {
            http_response_code($statusCode);
            self::sendSecurityHeaders();
        }

        // JSON response for API requests
        if ($request && ($request->wantsJson() || $request->isAjax())) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'request_id' => $requestId,
                'message' => $isDebug ? $exception->getMessage() : 'Internal Server Error',
                'error' => $isDebug ? [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ] : null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // HTML response
        if ($statusCode === 404) {
            self::renderErrorPage(404, 'Page Not Found', $exception, $isDebug, $requestId);
        } elseif ($statusCode === 403) {
            self::renderErrorPage(403, 'Forbidden', $exception, $isDebug, $requestId);
        } else {
            self::renderErrorPage(500, 'Internal Server Error', $exception, $isDebug, $requestId);
        }

        exit;
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        // Convert error to exception
        $exception = new ErrorException($message, 0, $severity, $file, $line);
        self::handleException($exception);

        return true; // Don't execute PHP internal error handler
    }

    /**
     * Handle fatal errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            self::handleException($exception);
        }
    }

    /**
     * Log exception with structured format
     */
    private static function logException(Throwable $exception): void
    {
        Logger::error('Unhandled exception', [
            'type' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    }

    /**
     * Render error page
     */
    private static function renderErrorPage(
        int $statusCode,
        string $title,
        Throwable $exception,
        bool $isDebug,
        string $requestId
    ): void {
        // Try to render custom error page
        $errorPagePath = __DIR__ . "/../../mvc/views/errors/{$statusCode}.php";
        
        if (file_exists($errorPagePath)) {
            // Get global router for language if available
            global $router;
            $lang = isset($router) && isset($router->lang) ? $router->lang : 'sr';
            
            $message = $isDebug ? $exception->getMessage() : $title;
            $exceptionData = $isDebug ? [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ] : null;

            extract([
                'statusCode' => $statusCode,
                'title' => $title,
                'message' => $message,
                'exception' => $exceptionData,
                'lang' => $lang,
                'requestId' => $requestId,
            ]);

            include $errorPagePath;
        } else {
            // Fallback to simple HTML
            self::renderSimpleErrorPage($statusCode, $title, $exception, $isDebug, $requestId);
        }
    }

    /**
     * Render simple error page (fallback)
     */
    private static function renderSimpleErrorPage(
        int $statusCode,
        string $title,
        Throwable $exception,
        bool $isDebug,
        string $requestId
    ): void {
        $message = $isDebug ? htmlspecialchars($exception->getMessage()) : $title;
        $safeRequestId = htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8');
        
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$statusCode} - {$title}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
        }
        h1 {
            font-size: 120px;
            margin: 0;
            color: #3b82f6;
            font-weight: 900;
        }
        h2 {
            font-size: 32px;
            margin: 20px 0;
            color: #e2e8f0;
        }
        p {
            font-size: 18px;
            color: #94a3b8;
            margin: 20px 0;
        }
        .request-id {
            font-size: 14px;
            color: #64748b;
        }
        .debug {
            margin-top: 40px;
            padding: 20px;
            background: #1e293b;
            border-radius: 8px;
            text-align: left;
            font-family: monospace;
            font-size: 14px;
        }
        .debug pre {
            margin: 10px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>{$statusCode}</h1>
        <h2>{$title}</h2>
        <p>{$message}</p>
        <p class='request-id'>Request ID: {$safeRequestId}</p>";
        
        if ($isDebug) {
            echo "<div class='debug'>
                <strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>
                <strong>Line:</strong> {$exception->getLine()}<br>
                <strong>Trace:</strong>
                <pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>
            </div>";
        }
        
        echo "    </div>
</body>
</html>";
    }

    private static function isDebugMode(): bool
    {
        return Env::get('APP_DEBUG', false) === true
            || Env::get('APP_DEBUG', 'false') === 'true'
            || Env::get('APP_DEBUG', '0') === '1';
    }

    private static function sendSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
    }
}


if (!\class_exists('ExceptionHandler', false) && !\interface_exists('ExceptionHandler', false) && !\trait_exists('ExceptionHandler', false)) {
    \class_alias(__NAMESPACE__ . '\\ExceptionHandler', 'ExceptionHandler');
}
