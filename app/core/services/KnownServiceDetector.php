<?php

namespace App\Core\services;

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
 * Known Service Detector
 * 
 * Identifies known services/organizations from IP addresses
 * (Google, Cloudflare, AWS, Bing, etc.)
 */
class KnownServiceDetector
{
    /**
     * Known service patterns for reverse DNS lookup
     */
    private const KNOWN_SERVICE_PATTERNS = [
        'googlebot.com' => 'Google Bot',
        'google.com' => 'Google',
        'crawl.yahoo.net' => 'Yahoo Bot',
        'search.msn.com' => 'Bing Bot',
        'amazonaws.com' => 'Amazon AWS',
        'cloudflare.com' => 'Cloudflare',
        'cloudflare.net' => 'Cloudflare',
        'fastly.com' => 'Fastly CDN',
        'maxcdn.com' => 'MaxCDN',
        'akamai.net' => 'Akamai CDN',
        'facebook.com' => 'Facebook',
        'facebook.net' => 'Facebook',
        'twitter.com' => 'Twitter',
        'linkedin.com' => 'LinkedIn',
        'apple.com' => 'Apple',
        'microsoft.com' => 'Microsoft',
        'github.com' => 'GitHub',
        'github.io' => 'GitHub Pages',
        'azure.com' => 'Microsoft Azure',
        'azurewebsites.net' => 'Microsoft Azure',
    ];

    /**
     * Known organization names from ISP/Org data
     */
    private const KNOWN_ORG_PATTERNS = [
        'google' => 'Google',
        'cloudflare' => 'Cloudflare',
        'amazon' => 'Amazon AWS',
        'amazon web services' => 'Amazon AWS',
        'microsoft' => 'Microsoft',
        'azure' => 'Microsoft Azure',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
        'apple' => 'Apple',
        'fastly' => 'Fastly CDN',
        'akamai' => 'Akamai CDN',
        'maxcdn' => 'MaxCDN',
        'yahoo' => 'Yahoo',
        'bing' => 'Microsoft Bing',
        'baidu' => 'Baidu',
        'yandex' => 'Yandex',
        'github' => 'GitHub',
        'digitalocean' => 'DigitalOcean',
        'linode' => 'Linode',
        'vultr' => 'Vultr',
        'ovh' => 'OVH',
        'hetzner' => 'Hetzner',
    ];

    /**
     * Detect known service from IP address
     * 
     * PERFORMANCE NOTE: This method uses fast, non-blocking detection methods:
     * - User-Agent string analysis (instant)
     * - HTTP header detection (Cloudflare, etc.) (instant)
     * - IP address range detection (instant, first octet check only)
     * - No network calls, no DNS lookups, no HTTP requests
     * 
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string (optional, for additional detection)
     * @return string|null Known service name or null
     */
    public static function detect(string $ipAddress, ?string $userAgent = null): ?string
    {
        // Skip private IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        // Method 1: User-Agent based detection (fastest, no network calls)
        if ($userAgent) {
            $service = self::detectFromUserAgent($userAgent);
            if ($service) {
                return $service;
            }
        }

        // Method 2: HTTP header detection (Cloudflare, etc.) - also instant
        $service = self::detectFromHeaders();
        if ($service) {
            return $service;
        }

        // Method 3: IP address range detection (first octet check only - fast but approximate)
        $service = self::detectFromIpRange($ipAddress);
        if ($service) {
            return $service;
        }

        return null;
    }

    /**
     * Perform reverse DNS lookup (with caching to avoid repeated lookups)
     * 
     * @param string $ipAddress IP address
     * @return string|null Reverse DNS hostname or null
     */
    private static function reverseDnsLookup(string $ipAddress): ?string
    {
        // Use static cache to avoid repeated lookups in same request
        static $cache = [];
        
        if (isset($cache[$ipAddress])) {
            return $cache[$ipAddress];
        }
        
        try {
            // Use set_error_handler to suppress warnings and set timeout
            set_error_handler(function() { return true; }, E_WARNING);
            
            // Perform reverse DNS lookup (this can be slow, but we cache results)
            // Note: gethostbyaddr doesn't support timeout, but we cache to minimize calls
            $hostname = gethostbyaddr($ipAddress);
            
            restore_error_handler();
            
            // gethostbyaddr returns IP if lookup fails
            if ($hostname && $hostname !== $ipAddress) {
                $cache[$ipAddress] = strtolower($hostname);
                return $cache[$ipAddress];
            }
        } catch (Exception $e) {
            restore_error_handler();
            // Silently fail
        }
        
        $cache[$ipAddress] = null;
        return null;
    }

    /**
     * Match reverse DNS hostname against known patterns
     * 
     * @param string $hostname Reverse DNS hostname
     * @return string|null Known service name or null
     */
    private static function matchReverseDns(string $hostname): ?string
    {
        foreach (self::KNOWN_SERVICE_PATTERNS as $pattern => $serviceName) {
            if (strpos($hostname, $pattern) !== false) {
                return $serviceName;
            }
        }
        
        return null;
    }

    // REMOVED: getOrgFromApi() and matchOrgData() methods
    // These methods were causing performance issues due to blocking HTTP requests to ip-api.com
    // We now rely only on User-Agent detection and reverse DNS lookup (which is cached per request)

    /**
     * Detect known service from User-Agent string
     * Fast, non-blocking detection based on User-Agent patterns
     * 
     * @param string $userAgent User agent string
     * @return string|null Known service name or null
     */
    private static function detectFromUserAgent(string $userAgent): ?string
    {
        $userAgentLower = strtolower($userAgent);
        
        // Bot detection (most common)
        if (stripos($userAgent, 'Googlebot') !== false) {
            return 'Google Bot';
        }
        if (stripos($userAgent, 'Bingbot') !== false || stripos($userAgent, 'bingbot') !== false) {
            return 'Microsoft Bing Bot';
        }
        if (stripos($userAgent, 'Slurp') !== false || stripos($userAgent, 'Yahoo') !== false) {
            return 'Yahoo Bot';
        }
        if (stripos($userAgent, 'facebookexternalhit') !== false || stripos($userAgent, 'Facebot') !== false) {
            return 'Facebook Bot';
        }
        if (stripos($userAgent, 'Twitterbot') !== false) {
            return 'Twitter Bot';
        }
        if (stripos($userAgent, 'LinkedInBot') !== false) {
            return 'LinkedIn Bot';
        }
        if (stripos($userAgent, 'Applebot') !== false) {
            return 'Apple Bot';
        }
        if (stripos($userAgent, 'Baiduspider') !== false) {
            return 'Baidu Bot';
        }
        if (stripos($userAgent, 'YandexBot') !== false || stripos($userAgent, 'Yandex') !== false) {
            return 'Yandex Bot';
        }
        
        // CDN/Proxy detection from User-Agent
        if (stripos($userAgent, 'Cloudflare') !== false) {
            return 'Cloudflare';
        }
        if (stripos($userAgent, 'Fastly') !== false) {
            return 'Fastly CDN';
        }
        if (stripos($userAgent, 'Akamai') !== false) {
            return 'Akamai CDN';
        }
        
        return null;
    }

    /**
     * Detect known service from HTTP headers
     * Fast, non-blocking detection based on HTTP headers (Cloudflare, etc.)
     * 
     * @return string|null Known service name or null
     */
    private static function detectFromHeaders(): ?string
    {
        // Cloudflare detection (very common)
        if (!empty($_SERVER['HTTP_CF_RAY']) || !empty($_SERVER['HTTP_CF_CONNECTING_IP']) || !empty($_SERVER['HTTP_CF_VISITOR'])) {
            return 'Cloudflare';
        }
        
        // Fastly detection
        if (!empty($_SERVER['HTTP_FASTLY_CLIENT_IP']) || !empty($_SERVER['HTTP_X_FASTLY_REQUEST_ID'])) {
            return 'Fastly CDN';
        }
        
        // AWS CloudFront detection
        if (!empty($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']) || !empty($_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'])) {
            return 'Amazon CloudFront';
        }
        
        // Akamai detection
        if (!empty($_SERVER['HTTP_X_AKAMAI_EDGESCAPE']) || !empty($_SERVER['HTTP_X_AKAMAI_REQUEST_ID'])) {
            return 'Akamai CDN';
        }
        
        return null;
    }

    /**
     * Detect known service from IP address range (first octet only - fast but approximate)
     * This is a simple heuristic that checks the first octet of the IP address
     * against known ranges used by major cloud providers.
     * 
     * NOTE: This is approximate and may have false positives, but it's very fast.
     * 
     * @param string $ipAddress IP address
     * @return string|null Known service name or null
     */
    private static function detectFromIpRange(string $ipAddress): ?string
    {
        // Extract first octet for IPv4
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $firstOctet = (int)($parts[0] ?? 0);
            
            // Google Cloud Platform (approximate - common ranges)
            // Note: 34.x.x.x is often Google Cloud, but not exclusively
            if ($firstOctet >= 34 && $firstOctet <= 35) {
                return 'Google Cloud';
            }
            
            // AWS (approximate - common ranges)
            if ($firstOctet >= 52 && $firstOctet <= 54) {
                return 'Amazon AWS';
            }
            if ($firstOctet == 18 || $firstOctet == 54 || $firstOctet == 13 || $firstOctet == 52) {
                return 'Amazon AWS';
            }
            
            // Microsoft Azure (approximate - common ranges)
            if ($firstOctet >= 40 && $firstOctet <= 51) {
                // Too broad, skip
            }
            if ($firstOctet == 20 || $firstOctet == 40 || $firstOctet == 51 || $firstOctet == 52) {
                return 'Microsoft Azure';
            }
            
            // DigitalOcean
            if ($firstOctet == 159 || $firstOctet == 161 || $firstOctet == 167 || $firstOctet == 138 || $firstOctet == 178) {
                return 'DigitalOcean';
            }
        }
        
        // For IPv6, we could check prefixes, but that's more complex and less common
        // For now, skip IPv6 range detection
        
        return null;
    }

    /**
     * Check if IP should be excluded from tracking
     * 
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @return bool True if should be excluded
     */
    public static function shouldExclude(string $ipAddress, ?string $userAgent = null): bool
    {
        $knownService = self::detect($ipAddress, $userAgent);
        
        // Exclude known bots and CDN services
        $excludedServices = [
            'Google Bot',
            'Microsoft Bing Bot',
            'Yahoo Bot',
            'Facebook Bot',
            'Twitter Bot',
            'LinkedIn Bot',
            'Apple Bot',
            'Baidu Bot',
            'Yandex Bot',
            'Cloudflare',
            'Fastly CDN',
            'MaxCDN',
            'Akamai CDN',
        ];
        
        return $knownService && in_array($knownService, $excludedServices);
    }
}


if (!\class_exists('KnownServiceDetector', false) && !\interface_exists('KnownServiceDetector', false) && !\trait_exists('KnownServiceDetector', false)) {
    \class_alias(__NAMESPACE__ . '\\KnownServiceDetector', 'KnownServiceDetector');
}
