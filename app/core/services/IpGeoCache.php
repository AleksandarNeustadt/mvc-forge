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
 * IP Geo Cache Service
 * 
 * Manages caching of IP geolocation data to avoid repeated API calls
 * and improve site performance.
 */
class IpGeoCache
{
    private const CACHE_DURATION = 86400 * 30; // 30 days
    private const API_TIMEOUT = 2; // 2 seconds max
    
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
     * Get geo data for IP address (from cache or API)
     * 
     * @param string $ipAddress IP address
     * @param bool $forceRefresh Force refresh from API
     * @return array Geo data array
     */
    public static function getGeoData(string $ipAddress, bool $forceRefresh = false): array
    {
        // Skip private IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }
        
        // Try to get from cache first (unless forced refresh)
        if (!$forceRefresh) {
            $cached = self::getFromCache($ipAddress);
            if ($cached) {
                // Update lookup count and timestamp
                self::updateLookupStats($ipAddress);
                return $cached;
            }
        }
        
        // If not in cache or forced refresh, fetch from API
        $geoData = self::fetchFromApi($ipAddress);
        
        if (!empty($geoData)) {
            // Store in cache
            self::storeInCache($ipAddress, $geoData);
        }
        
        return $geoData;
    }
    
    /**
     * Get geo data from cache
     * 
     * @param string $ipAddress IP address
     * @return array|null Geo data or null if not found
     */
    private static function getFromCache(string $ipAddress): ?array
    {
        try {
            self::ensureClassesLoaded();
            
            $result = Database::table('ip_geo_cache')
                ->where('ip_address', $ipAddress)
                ->first();
            
            if ($result) {
                // Check if cache is still valid (optional - we keep data for 30 days)
                $lastLookup = strtotime($result['last_lookup_at'] ?? $result['created_at']);
                if (time() - $lastLookup < self::CACHE_DURATION) {
                    return [
                        'country_code' => $result['country_code'],
                        'country_name' => $result['country_name'],
                        'region' => $result['region'],
                        'city' => $result['city'],
                        'isp' => $result['isp'],
                        'organization' => $result['organization'],
                        'is_proxy' => (bool)($result['is_proxy'] ?? 0),
                        'is_vpn' => (bool)($result['is_vpn'] ?? 0),
                        'is_hosting' => (bool)($result['is_hosting'] ?? 0),
                        'known_service' => $result['known_service'],
                    ];
                }
            }
        } catch (Exception $e) {
            // Table might not exist yet, silently fail
            error_log("IpGeoCache::getFromCache() error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Store geo data in cache
     * 
     * @param string $ipAddress IP address
     * @param array $geoData Geo data array
     * @return void
     */
    private static function storeInCache(string $ipAddress, array $geoData): void
    {
        try {
            self::ensureClassesLoaded();
            
            // Check if record exists
            $existing = Database::table('ip_geo_cache')
                ->where('ip_address', $ipAddress)
                ->first();
            
            $data = [
                'ip_address' => $ipAddress,
                'country_code' => $geoData['country_code'] ?? null,
                'country_name' => $geoData['country_name'] ?? null,
                'region' => $geoData['region'] ?? null,
                'city' => $geoData['city'] ?? null,
                'isp' => $geoData['isp'] ?? null,
                'organization' => $geoData['organization'] ?? null,
                'is_proxy' => isset($geoData['is_proxy']) ? ($geoData['is_proxy'] ? 1 : 0) : 0,
                'is_vpn' => isset($geoData['is_vpn']) ? ($geoData['is_vpn'] ? 1 : 0) : 0,
                'is_hosting' => isset($geoData['is_hosting']) ? ($geoData['is_hosting'] ? 1 : 0) : 0,
                'known_service' => $geoData['known_service'] ?? null,
                'last_lookup_at' => date('Y-m-d H:i:s'),
            ];
            
            if ($existing) {
                // Update existing record (increment lookup_count)
                $data['lookup_count'] = ($existing['lookup_count'] ?? 1) + 1;
                Database::table('ip_geo_cache')
                    ->where('ip_address', $ipAddress)
                    ->update($data);
            } else {
                // Insert new record
                $data['lookup_count'] = 1;
                Database::table('ip_geo_cache')->insert($data);
            }
        } catch (Exception $e) {
            // Table might not exist yet, silently fail
            error_log("IpGeoCache::storeInCache() error: " . $e->getMessage());
        }
    }
    
    /**
     * Update lookup statistics
     * 
     * @param string $ipAddress IP address
     * @return void
     */
    private static function updateLookupStats(string $ipAddress): void
    {
        try {
            self::ensureClassesLoaded();
            
            // Use direct SQL for increment
            Database::execute(
                "UPDATE ip_geo_cache SET lookup_count = lookup_count + 1, last_lookup_at = ? WHERE ip_address = ?",
                [date('Y-m-d H:i:s'), $ipAddress]
            );
        } catch (Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Fetch geo data from API
     * 
     * @param string $ipAddress IP address
     * @return array Geo data array
     */
    private static function fetchFromApi(string $ipAddress): array
    {
        // Skip private IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }
        
        try {
            // Use ip-api.com (free, no API key needed, 45 requests/minute limit)
            // Fields: status,country,countryCode,region,regionName,city,isp,org,proxy,hosting
            $url = "http://ip-api.com/json/{$ipAddress}?fields=status,country,countryCode,region,regionName,city,isp,org,proxy,hosting";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => self::API_TIMEOUT,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return [];
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'success') {
                // Detect known service from organization/ISP
                $knownService = self::detectServiceFromOrg($data['org'] ?? '', $data['isp'] ?? '');
                
                return [
                    'country_code' => $data['countryCode'] ?? null,
                    'country_name' => $data['country'] ?? null,
                    'region' => $data['regionName'] ?? $data['region'] ?? null,
                    'city' => $data['city'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'organization' => $data['org'] ?? null,
                    'is_proxy' => isset($data['proxy']) ? (bool)$data['proxy'] : false,
                    'is_vpn' => false, // ip-api.com doesn't provide VPN detection in free tier
                    'is_hosting' => isset($data['hosting']) ? (bool)$data['hosting'] : false,
                    'known_service' => $knownService,
                ];
            }
        } catch (Exception $e) {
            // Silently fail
            error_log("IpGeoCache::fetchFromApi() error: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Detect known service from organization/ISP name
     * 
     * @param string $org Organization name
     * @param string $isp ISP name
     * @return string|null Service name or null
     */
    private static function detectServiceFromOrg(string $org, string $isp): ?string
    {
        $combined = strtolower($org . ' ' . $isp);
        
        $patterns = [
            'google' => 'Google',
            'cloudflare' => 'Cloudflare',
            'amazon' => 'Amazon AWS',
            'amazon web services' => 'Amazon AWS',
            'aws' => 'Amazon AWS',
            'microsoft' => 'Microsoft',
            'azure' => 'Microsoft Azure',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'apple' => 'Apple',
            'fastly' => 'Fastly CDN',
            'akamai' => 'Akamai CDN',
            'maxcdn' => 'MaxCDN',
            'digitalocean' => 'DigitalOcean',
            'linode' => 'Linode',
            'vultr' => 'Vultr',
            'ovh' => 'OVH',
            'hetzner' => 'Hetzner',
            'github' => 'GitHub',
            'oracle cloud' => 'Oracle Cloud',
            'alibaba' => 'Alibaba Cloud',
            'tencent' => 'Tencent Cloud',
        ];
        
        foreach ($patterns as $pattern => $serviceName) {
            if (strpos($combined, $pattern) !== false) {
                return $serviceName;
            }
        }
        
        return null;
    }
    
    /**
     * Get cached geo data for multiple IPs at once
     * 
     * @param array $ipAddresses Array of IP addresses
     * @return array Array of geo data indexed by IP
     */
    public static function getBatchGeoData(array $ipAddresses): array
    {
        $results = [];
        
        try {
            self::ensureClassesLoaded();
            
            $cached = Database::table('ip_geo_cache')
                ->whereIn('ip_address', $ipAddresses)
                ->get();
            
            foreach ($cached as $record) {
                $results[$record['ip_address']] = [
                    'country_code' => $record['country_code'],
                    'country_name' => $record['country_name'],
                    'region' => $record['region'],
                    'city' => $record['city'],
                    'isp' => $record['isp'],
                    'organization' => $record['organization'],
                    'is_proxy' => (bool)($record['is_proxy'] ?? 0),
                    'is_vpn' => (bool)($record['is_vpn'] ?? 0),
                    'is_hosting' => (bool)($record['is_hosting'] ?? 0),
                    'known_service' => $record['known_service'],
                ];
            }
        } catch (Exception $e) {
            // Silently fail
        }
        
        return $results;
    }
    
    /**
     * Clear old cache entries (older than specified days)
     * 
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public static function clearOldCache(int $days = 90): int
    {
        try {
            self::ensureClassesLoaded();
            
            $deleted = Database::table('ip_geo_cache')
                ->where('last_lookup_at', '<', date('Y-m-d H:i:s', time() - ($days * 86400)))
                ->delete();
            
            return $deleted;
        } catch (Exception $e) {
            return 0;
        }
    }
}


if (!\class_exists('IpGeoCache', false) && !\interface_exists('IpGeoCache', false) && !\trait_exists('IpGeoCache', false)) {
    \class_alias(__NAMESPACE__ . '\\IpGeoCache', 'IpGeoCache');
}
