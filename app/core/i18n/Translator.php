<?php

namespace App\Core\i18n;

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

// core/i18n/Translator.php

class Translator
{
    private static $translations = [];
    private static $currentLang = 'sr';
    private static $fallbackLang = 'en';

    // Supported languages (30 languages)
    private static $supportedLanguages = [
        'sr', 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'pl', 'ru',
        'uk', 'cs', 'hu', 'el', 'ro', 'hr', 'bg', 'sk', 'sv', 'da',
        'no', 'fi', 'lt', 'et', 'lv', 'sl', 'zh', 'ja', 'ko', 'tr'
    ];

    /**
     * Initialize translator with language
     */
    public static function init($lang = 'sr')
    {
        self::$currentLang = in_array($lang, self::$supportedLanguages) ? $lang : 'sr';
        self::loadLanguage(self::$currentLang);
    }

    /**
     * Load language file
     */
    private static function loadLanguage($lang)
    {
        // Construct proper path from Translator.php location
        // Translator.php is in core/i18n/ so we need to go up 2 levels to root, then into resources/translations/
        $filePath = __DIR__ . '/../../resources/translations/' . $lang . '.jsonc';

        if (!file_exists($filePath)) {
            $filePath = __DIR__ . '/../../resources/translations/en.jsonc';
        }

        $content = file_get_contents($filePath);

        // Remove JSONC comments
        // Remove /* */ style comments
        $content = preg_replace('#/\*.*?\*/#s', '', $content);
        // Remove // style comments (but be careful about URLs and such)
        $content = preg_replace('#\s*//.*?$#m', '', $content);
        // Remove trailing commas before closing braces
        $content = preg_replace('#,(\s*[}\]])#', '$1', $content);

        $decoded = json_decode($content, true);
        self::$translations[$lang] = $decoded ?? [];
    }

    /**
     * Get translation by key
     */
    public static function get($key, $default = null)
    {
        // Load fallback if not loaded
        if (!isset(self::$translations[self::$currentLang])) {
            self::loadLanguage(self::$currentLang);
        }

        // Try to get from current language
        $value = self::getNestedValue(self::$translations[self::$currentLang], $key);

        if ($value !== null) {
            return $value;
        }

        // Try fallback language
        if (self::$currentLang !== self::$fallbackLang) {
            if (!isset(self::$translations[self::$fallbackLang])) {
                self::loadLanguage(self::$fallbackLang);
            }
            $value = self::getNestedValue(self::$translations[self::$fallbackLang], $key);
            if ($value !== null) {
                return $value;
            }
        }

        return $default ?? $key;
    }

    /**
     * Get nested array value using dot notation
     */
    private static function getNestedValue($array, $key)
    {
        // First try direct key lookup (flat structure with dots in keys)
        if (isset($array[$key])) {
            return $array[$key];
        }

        // Then try nested lookup with dot notation
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Set current language
     */
    public static function setLanguage($lang)
    {
        if (in_array($lang, self::$supportedLanguages)) {
            self::$currentLang = $lang;
            if (!isset(self::$translations[$lang])) {
                self::loadLanguage($lang);
            }
        }
    }

    /**
     * Get current language
     */
    public static function getCurrentLanguage()
    {
        return self::$currentLang;
    }

    /**
     * Get all supported languages
     */
    public static function getSupportedLanguages()
    {
        return self::$supportedLanguages;
    }

    /**
     * Check if language is supported
     */
    public static function isSupported($lang)
    {
        return in_array($lang, self::$supportedLanguages);
    }
}


if (!\class_exists('Translator', false) && !\interface_exists('Translator', false) && !\trait_exists('Translator', false)) {
    \class_alias(__NAMESPACE__ . '\\Translator', 'Translator');
}
