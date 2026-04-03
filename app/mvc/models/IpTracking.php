<?php

namespace App\Models;


use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;
use App\Core\logging\Logger;
use App\Core\services\GeoLocation;
use App\Core\services\IpGeoCache;
use App\Core\services\IpServiceMapper;use BadMethodCallException;
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
 * IpTracking Model
 * 
 * Tracks visitor IP addresses and their activities
 */
class IpTracking extends Model
{
    protected string $table = 'ip_tracking';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'ip_address',
        'user_id',
        'username',
        'request_method',
        'request_path',
        'user_agent',
        'country_code',
        'country_name',
        'language_id',
        'known_service',
        'is_suspicious',
        'request_count',
    ];
    
    protected array $casts = [
        'user_id' => 'int',
        'language_id' => 'int',
        'request_count' => 'int',
        'is_suspicious' => 'bool',
        'created_at' => 'datetime'
    ];

    /**
     * Log an IP request
     * 
     * @param string $ipAddress IP address
     * @param string $requestMethod HTTP method (GET, POST, etc.)
     * @param string $requestPath Request path
     * @param string|null $userAgent User agent string
     * @param int|null $userId User ID if logged in
     * @param string|null $username Username if logged in
     * @return void
     */
    public static function logRequest(
        string $ipAddress,
        string $requestMethod,
        string $requestPath,
        ?string $userAgent = null,
        ?int $userId = null,
        ?string $username = null
    ): void {
        Logger::debug("IP Tracking::logRequest() called - IP: {$ipAddress}, Path: {$requestPath}");
        try {
            // Check if table exists
            if (!class_exists('DatabaseBuilder')) {
                require_once __DIR__ . '/../../core/database/DatabaseBuilder.php';
            }
            
            $tables = DatabaseBuilder::getTables();
            Logger::debug("IP Tracking: Available tables: " . implode(', ', array_slice($tables, 0, 10)));
            if (!in_array('ip_tracking', $tables)) {
                // Table doesn't exist yet, skip logging
                Logger::debug("IP Tracking: Table 'ip_tracking' does not exist");
                return;
            }
            
            Logger::debug("IP Tracking: About to create entry using direct INSERT");
            // Use direct INSERT instead of Model::create() to avoid any issues
            require_once __DIR__ . '/../../core/database/Database.php';
            
            // Get geo data from cache (fast - checks database first, only calls API if needed)
            $geoData = [];
            $knownService = null;
            
            try {
                // Use IpServiceMapper for centralized service detection
                if (class_exists('IpServiceMapper')) {
                    require_once __DIR__ . '/../../core/services/IpServiceMapper.php';
                    $knownService = IpServiceMapper::getService($ipAddress, $userAgent);
                }
            } catch (Exception $e) {
                // Silently fail - will use fallback methods
                Logger::debug("IpTracking: IpServiceMapper error: " . $e->getMessage());
            }
            
            // Get geo data (for country info)
            $geoData = [];
            try {
                if (class_exists('IpGeoCache')) {
                    require_once __DIR__ . '/../../core/services/IpGeoCache.php';
                    $geoData = IpGeoCache::getGeoData($ipAddress);
                    Logger::debug("IP Tracking: GeoData for IP {$ipAddress}: " . json_encode($geoData));
                } else {
                    Logger::debug("IP Tracking: IpGeoCache class not found");
                }
            } catch (Exception $e) {
                Logger::debug("IP Tracking: Error getting geo data: " . $e->getMessage());
            }
            
            // Get or create language based on country code
            $languageId = null;
            if (!empty($geoData) && !empty($geoData['country_code'])) {
                $languageId = self::getOrCreateLanguageForCountry($geoData['country_code']);
                Logger::debug("IP Tracking: Country {$geoData['country_code']} -> Language ID: " . ($languageId ?? 'NULL'));
            } else {
                if (empty($geoData)) {
                    Logger::debug("IP Tracking: geoData is empty for IP: {$ipAddress}");
                } else {
                    Logger::debug("IP Tracking: No country_code in geoData for IP: {$ipAddress}, geoData: " . json_encode($geoData));
                }
            }
            
            $insertData = [
                'ip_address' => $ipAddress,
                'user_id' => $userId,
                'username' => $username,
                'request_method' => $requestMethod,
                'request_path' => $requestPath,
                'user_agent' => $userAgent,
                'country_code' => $geoData['country_code'] ?? null,
                'country_name' => $geoData['country_name'] ?? null,
                'is_suspicious' => 0,
                'request_count' => 1,
            ];
            
            // Add language_id if column exists and we have a language_id
            $hasLanguageIdColumn = false;
            try {
                $driver = Database::getDriver();
                if ($driver === 'mysql') {
                    $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'language_id'");
                    $hasLanguageIdColumn = !empty($columns);
                } elseif ($driver === 'pgsql') {
                    $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ip_tracking' AND column_name = 'language_id'");
                    $hasLanguageIdColumn = !empty($columns);
                } else {
                    // For other drivers, try to check if column exists by attempting to select it
                    $hasLanguageIdColumn = true; // Assume it exists, will fail gracefully if not
                }
            } catch (Exception $e) {
                Logger::debug("IP Tracking: Error checking language_id column: " . $e->getMessage());
                $hasLanguageIdColumn = false;
            }
            
            if ($hasLanguageIdColumn && $languageId !== null) {
                $insertData['language_id'] = $languageId;
                Logger::debug("IP Tracking: Adding language_id {$languageId} to insert data");
            } else {
                if (!$hasLanguageIdColumn) {
                    Logger::debug("IP Tracking: language_id column does not exist");
                }
                if ($languageId === null) {
                    Logger::debug("IP Tracking: language_id is null, not adding to insert data");
                }
            }
            
            // Add known_service if column exists
            try {
                $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'");
                if (!empty($columns)) {
                    $insertData['known_service'] = $knownService;
                }
            } catch (Exception $e) {
                // Column doesn't exist, skip it
            }
            
            // Insert data directly
            Logger::debug("IP Tracking: Insert data: " . json_encode($insertData));
            $result = Database::table('ip_tracking')->insert($insertData);
            Logger::debug("IP Tracking: Direct INSERT returned: " . ($result ? 'true' : 'false'));
            
            if (!$result) {
                Logger::debug("IP Tracking: Failed to insert log entry for IP: {$ipAddress}");
            } else {
                $insertedId = Database::lastInsertId();
                Logger::debug("IP Tracking: Successfully inserted log entry for IP: {$ipAddress}, ID: {$insertedId}");
                
                // Verify what was actually inserted
                if ($insertedId) {
                    $inserted = Database::selectOne(
                        "SELECT country_code, country_name, language_id FROM ip_tracking WHERE id = ?",
                        [$insertedId]
                    );
                    Logger::debug("IP Tracking: Verified inserted data: " . json_encode($inserted));
                }
            }
            
        } catch (Exception $e) {
            // Log error for debugging
            Logger::debug("IP Tracking error: " . $e->getMessage());
            Logger::debug("IP Tracking trace: " . $e->getTraceAsString());
        } catch (Throwable $e) {
            Logger::debug("IP Tracking fatal error: " . $e->getMessage());
            Logger::debug("IP Tracking fatal trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Get geo-location for IP address
     * 
     * @param string $ipAddress IP address
     * @return array Country code and name
     */
    private static function getGeoLocation(string $ipAddress): array
    {
        // Skip private/local IPs
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }
        
        try {
            // Use ip-api.com (free, no API key needed)
            $url = "http://ip-api.com/json/{$ipAddress}?fields=status,countryCode,country";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2, // 2 second timeout
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return [];
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country_code' => $data['countryCode'] ?? null,
                    'country_name' => $data['country'] ?? null,
                ];
            }
            
        } catch (Exception $e) {
            // Silently fail
        }
        
        return [];
    }

    /**
     * Get or create language entry for a country code
     * 
     * This method:
     * 1. Maps country code to language code using GeoLocation service
     * 2. Finds existing language in database by code
     * 3. If not found, creates a new language entry (is_site_language = false)
     * 4. Returns the language ID
     * 
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return int|null Language ID or null on failure
     */
    private static function getOrCreateLanguageForCountry(string $countryCode): ?int
    {
        try {
            // Map country code to language code
            $languageCode = null;
            if (class_exists('GeoLocation')) {
                require_once __DIR__ . '/../../core/services/GeoLocation.php';
                $geo = new GeoLocation();
                $languageCode = $geo->getLanguageForCountry($countryCode);
            } else {
                // Fallback: use country code as language code (not ideal, but works)
                $languageCode = strtolower($countryCode);
            }
            
            if (!$languageCode) {
                return null;
            }
            
            // Check if languages table exists
            if (!class_exists('DatabaseBuilder')) {
                require_once __DIR__ . '/../../core/database/DatabaseBuilder.php';
            }
            
            $tables = DatabaseBuilder::getTables();
            if (!in_array('languages', $tables)) {
                return null;
            }
            
            // Try to find existing language by code
            $language = Database::selectOne(
                "SELECT id FROM languages WHERE code = ? LIMIT 1",
                [$languageCode]
            );
            
            if ($language && isset($language['id'])) {
                return (int)$language['id'];
            }
            
            // Language doesn't exist, create it
            // Get country name for language name
            $countryName = null;
            try {
                if (class_exists('IpGeoCache')) {
                    require_once __DIR__ . '/../../core/services/IpGeoCache.php';
                    // We can't get country name from just country code here, so use a simple mapping
                }
            } catch (Exception $e) {
                // Silently fail
            }
            
            // Map country codes to language names (for new languages)
            $countryToLanguageName = [
                'RS' => 'Serbian', 'BA' => 'Serbian', 'ME' => 'Serbian',
                'US' => 'English', 'GB' => 'English', 'CA' => 'English',
                'DE' => 'German', 'AT' => 'German',
                'FR' => 'French', 'BE' => 'French',
                'ES' => 'Spanish', 'MX' => 'Spanish',
                'IT' => 'Italian',
                'PT' => 'Portuguese', 'BR' => 'Portuguese',
                'NL' => 'Dutch',
                'PL' => 'Polish',
                'RU' => 'Russian', 'BY' => 'Russian',
                'UA' => 'Ukrainian',
                'CZ' => 'Czech',
                'HU' => 'Hungarian',
                'GR' => 'Greek', 'CY' => 'Greek',
                'RO' => 'Romanian', 'MD' => 'Romanian',
                'HR' => 'Croatian',
                'BG' => 'Bulgarian',
                'SK' => 'Slovak',
                'SE' => 'Swedish',
                'DK' => 'Danish',
                'NO' => 'Norwegian',
                'FI' => 'Finnish',
                'LT' => 'Lithuanian',
                'EE' => 'Estonian',
                'LV' => 'Latvian',
                'SI' => 'Slovenian',
                'CN' => 'Chinese', 'TW' => 'Chinese', 'HK' => 'Chinese',
                'JP' => 'Japanese',
                'KR' => 'Korean',
                'TR' => 'Turkish',
            ];
            
            $languageName = $countryToLanguageName[strtoupper($countryCode)] ?? ucfirst($languageCode);
            
            // Get flag code for country (lowercase country code for flag-icons library)
            $flagCode = strtolower($countryCode);
            
            // Insert new language (is_site_language = false, since we don't use it on site yet)
            $now = date('Y-m-d H:i:s');
            $result = Database::execute(
                "INSERT INTO languages (code, name, native_name, flag, country_code, is_active, is_site_language, is_default, sort_order, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, 1, 0, 0, 999, ?, ?)",
                [$languageCode, $languageName, $languageName, '', $countryCode, $now, $now]
            );
            
            if ($result) {
                $newLanguageId = Database::lastInsertId();
                Logger::debug("IP Tracking: Created new language entry: {$languageCode} (ID: {$newLanguageId}) for country: {$countryCode}");
                return (int)$newLanguageId;
            }
            
        } catch (Exception $e) {
            Logger::debug("IP Tracking: Error getting/creating language for country {$countryCode}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Detect suspicious activity patterns
     * 
     * @param string $ipAddress IP address
     * @param string $requestPath Request path
     * @return bool True if suspicious
     */
    private static function detectSuspiciousActivity(string $ipAddress, string $requestPath): bool
    {
        // Check for common attack patterns in request path
        $suspiciousPatterns = [
            '/wp-admin',
            '/wp-login',
            '/admin',
            '/phpmyadmin',
            '/.env',
            '/config.php',
            '/shell.php',
            '/c99.php',
            'union select',
            'script>',
            '<script',
            '../',
            '..\\',
        ];
        
        $lowerPath = strtolower($requestPath);
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($lowerPath, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for high request rate from same IP (basic check)
        // This is a simple check - in production, you'd want more sophisticated rate limiting
        try {
            $recentRequests = Database::table('ip_tracking')
                ->where('ip_address', $ipAddress)
                ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 60)) // Last 60 seconds
                ->count();
            
            if ($recentRequests > 50) { // More than 50 requests per minute
                return true;
            }
        } catch (Exception $e) {
            // Silently fail
        }
        
        return false;
    }

    /**
     * Get recent IP tracking entries
     * 
     * @param int $limit Number of entries to return
     * @return array Array of tracking entries
     */
    public static function getRecent(int $limit = 100): array
    {
        try {
            // Check if ip_services table exists
            if (!class_exists('DatabaseBuilder')) {
                require_once __DIR__ . '/../../core/database/DatabaseBuilder.php';
            }
            
            $tables = DatabaseBuilder::getTables();
            $hasIpServices = in_array('ip_services', $tables);
            $hasLanguages = in_array('languages', $tables);
            
            // Check if language_id column exists in ip_tracking
            $hasLanguageId = false;
            try {
                if (Database::getDriver() === 'mysql') {
                    $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'language_id'", []);
                    $hasLanguageId = !empty($columns);
                } elseif (Database::getDriver() === 'pgsql') {
                    $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ip_tracking' AND column_name = 'language_id'", []);
                    $hasLanguageId = !empty($columns);
                } else {
                    // For other drivers, assume it exists if languages table exists
                    $hasLanguageId = $hasLanguages;
                }
            } catch (Exception $e) {
                // Silently fail
            }
            
            if ($hasIpServices && $hasLanguages && $hasLanguageId) {
                // Use JOIN to get consistent service data and language data
                $sql = "SELECT 
                            t.*,
                            s.known_service as known_service,
                            l.country_code as language_country_code,
                            l.code as language_code,
                            l.name as language_name
                        FROM ip_tracking t
                        LEFT JOIN ip_services s ON t.ip_address = s.ip_address
                        LEFT JOIN languages l ON t.language_id = l.id
                        ORDER BY t.created_at DESC
                        LIMIT ?";
                return Database::select($sql, [$limit]);
            } elseif ($hasIpServices) {
                // Use JOIN to get consistent service data from ip_services table
                $sql = "SELECT 
                            t.*,
                            s.known_service as known_service
                        FROM ip_tracking t
                        LEFT JOIN ip_services s ON t.ip_address = s.ip_address
                        ORDER BY t.created_at DESC
                        LIMIT ?";
                return Database::select($sql, [$limit]);
            } else {
                // Fallback to simple query
                return Database::table('ip_tracking')
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get();
            }
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get IP statistics (grouped by IP)
     * 
     * @param int $limit Number of IPs to return
     * @return array Array of IP statistics
     */
    public static function getIpStats(int $limit = 50): array
    {
        try {
            // Check if table exists first
            if (!class_exists('DatabaseBuilder')) {
                require_once __DIR__ . '/../../core/database/DatabaseBuilder.php';
            }
            
            $tables = DatabaseBuilder::getTables();
            if (!in_array('ip_tracking', $tables)) {
                Logger::debug("IpTracking::getIpStats() - Table ip_tracking does not exist");
                return [];
            }
            
            // Check if ip_services table exists
            $tables = DatabaseBuilder::getTables();
            $hasIpServices = in_array('ip_services', $tables);
            
            // Check if known_service column exists in ip_tracking
            $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'known_service'", []);
            $hasKnownService = !empty($columns);
            
            // Build SQL query - use JOIN with ip_services if available, otherwise fallback to MAX
            if ($hasIpServices) {
                // Use JOIN with ip_services for consistent service data
                // Always prioritize ip_services.known_service (source of truth)
                $sql = "SELECT 
                            t.ip_address,
                            COUNT(*) as request_count,
                            MAX(t.created_at) as last_seen,
                            MIN(t.created_at) as first_seen,
                            MAX(t.country_code) as country_code,
                            MAX(t.country_name) as country_name,
                            MAX(t.username) as username,
                            MAX(t.user_id) as user_id,
                            s.known_service as known_service,
                            SUM(CASE WHEN t.is_suspicious = 1 THEN 1 ELSE 0 END) as suspicious_count
                        FROM ip_tracking t
                        LEFT JOIN ip_services s ON t.ip_address = s.ip_address
                        GROUP BY t.ip_address, s.known_service
                        ORDER BY COUNT(*) DESC
                        LIMIT ?";
            } else {
                // Fallback to MAX if ip_services table doesn't exist
                $knownServiceSelect = $hasKnownService ? "MAX(known_service) as known_service," : "NULL as known_service,";
                $sql = "SELECT 
                            ip_address,
                            COUNT(*) as request_count,
                            MAX(created_at) as last_seen,
                            MIN(created_at) as first_seen,
                            MAX(country_code) as country_code,
                            MAX(country_name) as country_name,
                            MAX(username) as username,
                            MAX(user_id) as user_id,
                            {$knownServiceSelect}
                            SUM(CASE WHEN is_suspicious = 1 THEN 1 ELSE 0 END) as suspicious_count
                        FROM ip_tracking
                        GROUP BY ip_address
                        ORDER BY COUNT(*) DESC
                        LIMIT ?";
            }
            
            $results = Database::select($sql, [$limit]);
            return $results ?: [];
        } catch (Exception $e) {
            Logger::debug("IpTracking::getIpStats() error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get requests by IP address
     * 
     * @param string $ipAddress IP address
     * @param int $limit Number of requests to return
     * @return array Array of requests
     */
    public static function getByIp(string $ipAddress, int $limit = 100): array
    {
        try {
            return Database::table('ip_tracking')
                ->where('ip_address', $ipAddress)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get requests by user ID
     * 
     * @param int $userId User ID
     * @param int $limit Number of requests to return
     * @return array Array of requests
     */
    public static function getByUserId(int $userId, int $limit = 100): array
    {
        try {
            return Database::table('ip_tracking')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get hourly request statistics for chart
     * 
     * @param int $hours Number of hours to look back (default: 24)
     * @return array Array with hour and count
     */
    public static function getHourlyStats(int $hours = 24): array
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                        COUNT(*) as count
                    FROM ip_tracking
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                    GROUP BY hour
                    ORDER BY hour ASC";
            
            return Database::select($sql, [$hours]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get country statistics for chart
     * 
     * @param int $limit Number of countries to return
     * @return array Array with country and count
     */
    public static function getCountryStats(int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        COALESCE(country_name, 'Unknown') as country,
                        COUNT(*) as count
                    FROM ip_tracking
                    GROUP BY country_name
                    ORDER BY count DESC
                    LIMIT ?";
            
            return Database::select($sql, [$limit]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get suspicious activity entries
     * 
     * @param int $limit Number of entries to return
     * @return array Array of suspicious entries
     */
    public static function getSuspicious(int $limit = 100): array
    {
        try {
            return Database::table('ip_tracking')
                ->where('is_suspicious', 1)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            return [];
        }
    }
}


if (!\class_exists('IpTracking', false) && !\interface_exists('IpTracking', false) && !\trait_exists('IpTracking', false)) {
    \class_alias(__NAMESPACE__ . '\\IpTracking', 'IpTracking');
}
