<?php

namespace App\Core\services;


use App\Core\database\Database;use BadMethodCallException;
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
 * IP Service Mapper
 * 
 * Centralized service detection and mapping for IP addresses.
 * Ensures consistency - same IP always has the same service.
 */
class IpServiceMapper
{
    /**
     * Ensure required classes are loaded
     */
    private static function ensureClassesLoaded(): void
    {
        if (!class_exists('Database')) {
            require_once __DIR__ . '/../database/Database.php';
        }
        if (!class_exists('QueryBuilder')) {
            require_once __DIR__ . '/../database/QueryBuilder.php';
        }
    }
    
    /**
     * Get service for IP address (from cache or detect)
     * 
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string (optional)
     * @return string|null Service name or null
     */
    public static function getService(string $ipAddress, ?string $userAgent = null): ?string
    {
        // Skip private IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        
        self::ensureClassesLoaded();
        
        // Try to get from ip_services table first
        $cached = self::getFromCache($ipAddress);
        if ($cached) {
            // Update last detected timestamp
            self::updateLastDetected($ipAddress);
            return $cached['known_service'];
        }
        
        // If not in cache, detect service
        $service = self::detectService($ipAddress, $userAgent);
        
        if ($service) {
            // Store in cache
            self::storeService($ipAddress, $service, $userAgent);
        }
        
        return $service;
    }
    
    /**
     * Get service from cache (ip_services table)
     * 
     * @param string $ipAddress IP address
     * @return array|null Service data or null
     */
    private static function getFromCache(string $ipAddress): ?array
    {
        try {
            self::ensureClassesLoaded();
            
            $result = Database::table('ip_services')
                ->where('ip_address', $ipAddress)
                ->first();
            
            return $result ?: null;
        } catch (Exception $e) {
            // Table might not exist yet
            return null;
        }
    }
    
    /**
     * Store service in cache
     * 
     * @param string $ipAddress IP address
     * @param string $service Service name
     * @param string|null $userAgent User agent (for detection method)
     * @return void
     */
    private static function storeService(string $ipAddress, string $service, ?string $userAgent = null): void
    {
        try {
            self::ensureClassesLoaded();
            
            // Determine detection method
            $detectionMethod = 'geo_api';
            if ($userAgent) {
                if (stripos($userAgent, 'Googlebot') !== false || 
                    stripos($userAgent, 'Bingbot') !== false ||
                    stripos($userAgent, 'Cloudflare') !== false) {
                    $detectionMethod = 'user_agent';
                }
            }
            
            // Check for Cloudflare headers
            if (!empty($_SERVER['HTTP_CF_RAY']) || !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $detectionMethod = 'headers';
            }
            
            // Check if record exists
            $existing = Database::table('ip_services')
                ->where('ip_address', $ipAddress)
                ->first();
            
            $now = date('Y-m-d H:i:s');
            
            if ($existing) {
            // Update existing record
            Database::execute(
                "UPDATE ip_services SET known_service = ?, detection_count = detection_count + 1, last_detected_at = ?, detection_method = ? WHERE ip_address = ?",
                [$service, $now, $detectionMethod, $ipAddress]
            );
            } else {
                // Insert new record
                Database::table('ip_services')->insert([
                    'ip_address' => $ipAddress,
                    'known_service' => $service,
                    'detection_method' => $detectionMethod,
                    'detection_count' => 1,
                    'first_detected_at' => $now,
                    'last_detected_at' => $now,
                ]);
            }
        } catch (Exception $e) {
            // Table might not exist yet, silently fail
            error_log("IpServiceMapper::storeService() error: " . $e->getMessage());
        }
    }
    
    /**
     * Update last detected timestamp
     * 
     * @param string $ipAddress IP address
     * @return void
     */
    private static function updateLastDetected(string $ipAddress): void
    {
        try {
            self::ensureClassesLoaded();
            
            Database::execute(
                "UPDATE ip_services SET last_detected_at = ? WHERE ip_address = ?",
                [date('Y-m-d H:i:s'), $ipAddress]
            );
        } catch (Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Detect service for IP address
     * Uses multiple methods: User-Agent, Headers, Geo API, Reverse DNS
     * 
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @return string|null Service name or null
     */
    private static function detectService(string $ipAddress, ?string $userAgent = null): ?string
    {
        // Method 1: KnownServiceDetector (fast - User-Agent + Headers)
        if (class_exists('KnownServiceDetector')) {
            try {
                require_once __DIR__ . '/KnownServiceDetector.php';
                $service = KnownServiceDetector::detect($ipAddress, $userAgent);
                if ($service) {
                    return $service;
                }
            } catch (Exception $e) {
                // Silently fail
            }
        }
        
        // Method 2: IpGeoCache (from ISP/Organization data)
        if (class_exists('IpGeoCache')) {
            try {
                require_once __DIR__ . '/IpGeoCache.php';
                $geoData = IpGeoCache::getGeoData($ipAddress);
                if (!empty($geoData['known_service'])) {
                    return $geoData['known_service'];
                }
            } catch (Exception $e) {
                // Silently fail
            }
        }
        
        return null;
    }
    
    /**
     * Update all ip_tracking records with service from ip_services table
     * 
     * @param string $ipAddress IP address
     * @return int Number of updated records
     */
    public static function updateTrackingRecords(string $ipAddress): int
    {
        try {
            self::ensureClassesLoaded();
            
            // Get service from ip_services
            $service = self::getFromCache($ipAddress);
            if (!$service || empty($service['known_service'])) {
                return 0;
            }
            
            // Check if known_service column exists in ip_tracking
            $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
            if (empty($columns)) {
                return 0; // Column doesn't exist
            }
            
            // Update all records for this IP that don't have a service
            $updated = Database::execute(
                "UPDATE ip_tracking SET known_service = ? WHERE ip_address = ? AND (known_service IS NULL OR known_service = '')",
                [$service['known_service'], $ipAddress]
            );
            
            return $updated;
        } catch (Exception $e) {
            error_log("IpServiceMapper::updateTrackingRecords() error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Batch update all IP addresses in ip_tracking with services
     * 
     * @param int $limit Number of IPs to process
     * @param bool $allIps If true, process all IPs, not just those without service
     * @return array Statistics
     */
    public static function batchUpdateAllServices(int $limit = 100, bool $allIps = false): array
    {
        try {
            self::ensureClassesLoaded();
            
            // Get distinct IPs from ip_tracking
            // If allIps is false, only get IPs without service
            // If allIps is true, get all IPs (to ensure ip_services is populated)
            if ($allIps) {
                $sql = "SELECT DISTINCT ip_address 
                        FROM ip_tracking 
                        WHERE ip_address != '0.0.0.0'
                        AND ip_address NOT LIKE '127.%'
                        AND ip_address NOT LIKE '192.168.%'
                        AND ip_address NOT LIKE '10.%'
                        AND ip_address NOT LIKE '172.%'
                        LIMIT ?";
            } else {
                $sql = "SELECT DISTINCT ip_address 
                        FROM ip_tracking 
                        WHERE (known_service IS NULL OR known_service = '')
                        AND ip_address != '0.0.0.0'
                        AND ip_address NOT LIKE '127.%'
                        AND ip_address NOT LIKE '192.168.%'
                        AND ip_address NOT LIKE '10.%'
                        AND ip_address NOT LIKE '172.%'
                        LIMIT ?";
            }
            
            $ips = Database::select($sql, [$limit]);
            
            $stats = [
                'processed' => 0,
                'updated' => 0,
                'services_found' => 0,
            ];
            
            foreach ($ips as $row) {
                $ipAddress = $row['ip_address'];
                $stats['processed']++;
                
                // Get or detect service (this will check ip_services first, then detect)
                $service = self::getService($ipAddress);
                
                if ($service) {
                    $stats['services_found']++;
                    // Update tracking records
                    $updated = self::updateTrackingRecords($ipAddress);
                    $stats['updated'] += $updated;
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("IpServiceMapper::batchUpdateAllServices() error: " . $e->getMessage());
            return ['processed' => 0, 'updated' => 0, 'services_found' => 0];
        }
    }
}


if (!\class_exists('IpServiceMapper', false) && !\interface_exists('IpServiceMapper', false) && !\trait_exists('IpServiceMapper', false)) {
    \class_alias(__NAMESPACE__ . '\\IpServiceMapper', 'IpServiceMapper');
}
