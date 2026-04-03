<?php

namespace App\Core\middleware;


use App\Core\config\Env;
use App\Core\http\Request;use BadMethodCallException;
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
 * Security Headers Middleware
 *
 * Sets security-related HTTP headers to protect against common attacks.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Nonce for inline scripts (generated per request)
     */
    private static ?string $nonce = null;

    /**
     * Load Env class if not already loaded
     */
    private function ensureEnv(): void
    {
        if (!class_exists('Env')) {
            require_once __DIR__ . '/../config/Env.php';
        }
    }

    /**
     * Generate or get CSP nonce for inline scripts
     */
    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        // Set security headers before processing
        $this->setSecurityHeaders();

        // Continue to next middleware/handler
        return $next($request);
    }

    /**
     * Set all security headers
     */
    private function setSecurityHeaders(): void
    {
        // Generate nonce for inline scripts (must be done before CSP)
        self::$nonce = self::getNonce();

        // Prevent clickjacking - use DENY for maximum security
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS filter (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Permissions policy (disable unnecessary features)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');

        // Content Security Policy (adjust based on your needs)
        $csp = $this->buildCSP();
        header("Content-Security-Policy: {$csp}");

        // Strict Transport Security (HTTPS only)
        // Always set HSTS if request is secure OR if APP_URL in .env is HTTPS (for Cloudflare compatibility)
        if ($this->isSecure() || $this->shouldForceHSTS()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Hide server info
        header_remove('X-Powered-By');
        header_remove('Server');

        // Additional security headers
        header('X-Download-Options: noopen'); // Prevent file execution in IE
        header('X-Permitted-Cross-Domain-Policies: none'); // Prevent Flash/PDF cross-domain access
        
        // Cache control for sensitive pages
        if ($this->isSensitivePage()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    /**
     * Build Content Security Policy header
     */
    private function buildCSP(): string
    {
        $nonce = self::getNonce();
        
        $directives = [
            // Deny by default (best practice)
            "default-src 'none'",

            // Scripts: self + nonce for inline scripts + specific CDNs + Cloudflare email decode + TinyMCE CDN + hash for TinyMCE inline script
            // Note: TinyMCE requires 'unsafe-eval' for certain features, but we try to avoid it if possible
            // The hash 'sha256-MM+yFB5dSyptnFM7PYpStFQ/SDUYaszx/53skp574SA=' is for TinyMCE's inline initialization script
            "script-src 'self' http://localhost:5173 'nonce-{$nonce}' 'sha256-MM+yFB5dSyptnFM7PYpStFQ/SDUYaszx/53skp574SA=' https://unpkg.com https://cdn.jsdelivr.net https://cdn.tiny.cloud https://aleksandar.pro/cdn-cgi",

            // Styles: self + unsafe-inline (required for JavaScript-manipulated styles and libraries like Ionicons)
            // Note: HTTP Observatory prefers removing unsafe-inline, but JavaScript-set inline styles require it
            // Ionicons and other JavaScript libraries dynamically set inline styles which cannot be blocked by CSP
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdn.tiny.cloud",

            // Images: self + data URIs + https (including SVG icons from unpkg)
            "img-src 'self' data: https: blob:",

            // Fonts: self + Google Fonts
            "font-src 'self' https://fonts.gstatic.com data:",

            // Connect: self + API endpoints + unpkg for IonIcons SVG loading + TinyMCE CDN
            "connect-src 'self' ws://localhost:5173 http://localhost:5173 https://ip-api.com https://unpkg.com https://cdn.tiny.cloud",

            // Frames: none by default
            "frame-src 'none'",

            // Objects: none (no Flash, etc.)
            "object-src 'none'",

            // Base URI: self only
            "base-uri 'self'",

            // Form actions: self only
            "form-action 'self'",

            // Frame ancestors: none (prevent embedding)
            "frame-ancestors 'none'",

            // Upgrade insecure requests
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }

    /**
     * Check if request is over HTTPS
     * Handles various proxy configurations (Cloudflare, nginx, etc.)
     */
    private function isSecure(): bool
    {
        // Check HTTPS server variable (standard way)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        // Check forwarded protocol (reverse proxy like nginx, Cloudflare)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        // Check forwarded SSL (some proxies)
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        // Check server port
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        
        // Check if request was made to HTTPS URL (from request URI)
        if (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') {
            return true;
        }
        
        // Check HTTP_X_FORWARDED_SCHEME (alternative header)
        if (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME']) && $_SERVER['HTTP_X_FORWARDED_SCHEME'] === 'https') {
            return true;
        }
        
        // Check Cloudflare CF-Visitor header
        if (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"')) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if HSTS should be forced based on APP_URL configuration
     * Useful when behind Cloudflare or other reverse proxies
     */
    private function shouldForceHSTS(): bool
    {
        $this->ensureEnv();
        $appUrl = Env::get('APP_URL', '');
        
        // If APP_URL starts with https://, assume we're on HTTPS
        // This helps with Cloudflare and reverse proxy scenarios
        if (!empty($appUrl) && str_starts_with(strtolower($appUrl), 'https://')) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if current page is sensitive (login, admin, etc.)
     */
    private function isSensitivePage(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $sensitivePaths = ['/login', '/admin', '/dashboard', '/account', '/profile'];

        foreach ($sensitivePaths as $path) {
            if (str_contains($uri, $path)) {
                return true;
            }
        }

        return false;
    }
}


if (!\class_exists('SecurityHeadersMiddleware', false) && !\interface_exists('SecurityHeadersMiddleware', false) && !\trait_exists('SecurityHeadersMiddleware', false)) {
    \class_alias(__NAMESPACE__ . '\\SecurityHeadersMiddleware', 'SecurityHeadersMiddleware');
}
