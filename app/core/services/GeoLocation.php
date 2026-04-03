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

// core/services/GeoLocation.php

class GeoLocation {

    private const DEFAULT_LANG = 'en';
    private const CACHE_DURATION = 3600; // 1 hour

    /**
     * Map countries to languages
     * This maps country codes (ISO 3166-1 alpha-2) to language codes
     */
    private array $countryToLang = [
        // Serbian
        'RS' => 'sr', // Serbia
        'BA' => 'sr', // Bosnia and Herzegovina
        'ME' => 'sr', // Montenegro

        // English (default for many countries)
        'US' => 'en', 'GB' => 'en', 'CA' => 'en', 'AU' => 'en',
        'NZ' => 'en', 'IE' => 'en', 'ZA' => 'en', 'IN' => 'en',
        'SG' => 'en', 'MY' => 'en', 'PH' => 'en',

        // German
        'DE' => 'de', // Germany
        'AT' => 'de', // Austria
        'CH' => 'de', // Switzerland (also fr, it)
        'LI' => 'de', // Liechtenstein
        'LU' => 'de', // Luxembourg (also fr)

        // French
        'FR' => 'fr', // France
        'BE' => 'fr', // Belgium (also nl)
        'MC' => 'fr', // Monaco

        // Spanish
        'ES' => 'es', // Spain
        'MX' => 'es', 'AR' => 'es', 'CO' => 'es', 'CL' => 'es',
        'PE' => 'es', 'VE' => 'es', 'EC' => 'es', 'GT' => 'es',
        'CU' => 'es', 'BO' => 'es', 'DO' => 'es', 'HN' => 'es',
        'PY' => 'es', 'SV' => 'es', 'NI' => 'es', 'CR' => 'es',
        'PA' => 'es', 'UY' => 'es',

        // Italian
        'IT' => 'it', // Italy
        'SM' => 'it', // San Marino
        'VA' => 'it', // Vatican

        // Portuguese
        'PT' => 'pt', // Portugal
        'BR' => 'pt', // Brazil
        'AO' => 'pt', 'MZ' => 'pt',

        // Dutch
        'NL' => 'nl', // Netherlands

        // Polish
        'PL' => 'pl',

        // Russian
        'RU' => 'ru', // Russia
        'BY' => 'ru', // Belarus
        'KZ' => 'ru', // Kazakhstan

        // Ukrainian
        'UA' => 'uk',

        // Czech
        'CZ' => 'cs',

        // Hungarian
        'HU' => 'hu',

        // Greek
        'GR' => 'el',
        'CY' => 'el',

        // Romanian
        'RO' => 'ro',
        'MD' => 'ro',

        // Croatian
        'HR' => 'hr',

        // Bulgarian
        'BG' => 'bg',

        // Slovak
        'SK' => 'sk',

        // Swedish
        'SE' => 'sv',

        // Danish
        'DK' => 'da',

        // Norwegian
        'NO' => 'no',

        // Finnish
        'FI' => 'fi',

        // Lithuanian
        'LT' => 'lt',

        // Estonian
        'EE' => 'et',

        // Latvian
        'LV' => 'lv',

        // Slovenian
        'SI' => 'sl',

        // Chinese
        'CN' => 'zh', // China
        'TW' => 'zh', // Taiwan
        'HK' => 'zh', // Hong Kong

        // Japanese
        'JP' => 'ja',

        // Korean
        'KR' => 'ko',

        // Turkish
        'TR' => 'tr',
    ];

    /**
     * Detect country from IP address using free API
     * Uses ip-api.com which allows 45 requests per minute
     *
     * @param string|null $ip IP address (null = auto-detect)
     * @return string|null Country code or null on failure
     */
    public function detectCountry(?string $ip = null): ?string {
        // Get IP address
        if ($ip === null) {
            $ip = $this->getClientIp();
        }

        // Don't geolocate private/local IPs
        if ($this->isPrivateIp($ip)) {
            return null;
        }

        // Check session cache first
        if (isset($_SESSION['geo_country']) && isset($_SESSION['geo_timestamp'])) {
            if (time() - $_SESSION['geo_timestamp'] < self::CACHE_DURATION) {
                return $_SESSION['geo_country'];
            }
        }

        try {
            // Call free IP geolocation API
            $url = "http://ip-api.com/json/{$ip}?fields=status,countryCode";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3, // 3 second timeout
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            if (isset($data['status']) && $data['status'] === 'success' && isset($data['countryCode'])) {
                $countryCode = $data['countryCode'];

                // Cache in session
                $_SESSION['geo_country'] = $countryCode;
                $_SESSION['geo_timestamp'] = time();

                return $countryCode;
            }

        } catch (Exception $e) {
            // Silently fail - return null
            return null;
        }

        return null;
    }

    /**
     * Get client's real IP address
     * Handles proxies and load balancers
     *
     * @return string
     */
    private function getClientIp(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Get first IP in chain
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return trim($ip);
    }

    /**
     * Check if IP is private/local
     *
     * @param string $ip
     * @return bool
     */
    private function isPrivateIp(string $ip): bool {
        // Check if it's a valid IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Check if it's a private IP
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Get language code for a country
     * Returns default language if country not mapped
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return string Language code
     */
    public function getLanguageForCountry(string $countryCode): string {
        return $this->countryToLang[strtoupper($countryCode)] ?? self::DEFAULT_LANG;
    }

    /**
     * Detect language from IP address
     * This is the main public method to use
     *
     * @param string|null $ip IP address (null = auto-detect)
     * @return string Language code (falls back to default)
     */
    public function detectLanguage(?string $ip = null): string {
        $country = $this->detectCountry($ip);

        if ($country === null) {
            return self::DEFAULT_LANG;
        }

        return $this->getLanguageForCountry($country);
    }
}


if (!\class_exists('GeoLocation', false) && !\interface_exists('GeoLocation', false) && !\trait_exists('GeoLocation', false)) {
    \class_alias(__NAMESPACE__ . '\\GeoLocation', 'GeoLocation');
}
