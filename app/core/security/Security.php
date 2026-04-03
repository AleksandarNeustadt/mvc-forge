<?php

namespace App\Core\security;

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
 * Security Class - Validation, Sanitization, Escaping
 *
 * Usage:
 *   $clean = Security::sanitize($input, 'string');
 *   $safe = Security::escape($output);
 *   $valid = Security::validate($email, 'email');
 *   $errors = Security::validateAll($data, $rules);
 */
class Security
{
    /**
     * Sanitize input based on type
     *
     * @param mixed $value Input value
     * @param string $type Type: string, int, float, email, url, html, alphanumeric
     * @return mixed Sanitized value
     */
    public static function sanitize(mixed $value, string $type = 'string'): mixed
    {
        if ($value === null) {
            return null;
        }

        return match($type) {
            'string' => self::sanitizeString($value),
            'int', 'integer' => self::sanitizeInt($value),
            'float', 'double' => self::sanitizeFloat($value),
            'email' => self::sanitizeEmail($value),
            'url' => self::sanitizeUrl($value),
            'html' => self::sanitizeHtml($value),
            'alphanumeric' => self::sanitizeAlphanumeric($value),
            'slug' => self::sanitizeSlug($value),
            'filename' => self::sanitizeFilename($value),
            'raw' => $value, // No sanitization (use with caution!)
            default => self::sanitizeString($value),
        };
    }

    /**
     * Sanitize string - trim, remove null bytes, normalize whitespace
     */
    public static function sanitizeString(mixed $value): string
    {
        $value = (string) $value;
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        // Trim whitespace
        $value = trim($value);
        // Normalize multiple spaces to single space
        $value = preg_replace('/\s+/', ' ', $value);
        return $value;
    }

    /**
     * Sanitize to integer
     */
    public static function sanitizeInt(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize to float
     */
    public static function sanitizeFloat(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail(mixed $value): string
    {
        return (string) filter_var(trim((string) $value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(mixed $value): string
    {
        return (string) filter_var(trim((string) $value), FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize HTML - strip dangerous tags, keep safe ones
     * Allows common rich text editor tags and attributes
     */
    public static function sanitizeHtml(mixed $value): string
    {
        $value = (string) $value;
        
        // Allow common rich text editor tags
        $allowedTags = '<p><br><br/><strong><b><em><i><u><s><strike><del><ins><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><div><span><img><table><thead><tbody><tr><td><th><hr><sub><sup>';
        
        // First, strip all tags except allowed ones
        $value = strip_tags($value, $allowedTags);
        
        // Sanitize attributes for allowed tags
        // Remove dangerous attributes like onclick, onerror, etc.
        $value = preg_replace_callback('/<([a-z][a-z0-9]*)(\s+[^>]*)?>/i', function($matches) {
            $tag = strtolower($matches[1]);
            $attrs = $matches[2] ?? '';
            
            // Allowed attributes per tag
            $allowedAttrs = [
                'a' => ['href', 'title', 'target', 'rel'],
                'img' => ['src', 'alt', 'title', 'width', 'height', 'class'],
                'div' => ['class', 'id'],
                'span' => ['class', 'id'],
                'p' => ['class', 'id'],
                'h1' => ['class', 'id'],
                'h2' => ['class', 'id'],
                'h3' => ['class', 'id'],
                'h4' => ['class', 'id'],
                'h5' => ['class', 'id'],
                'h6' => ['class', 'id'],
                'table' => ['class', 'id'],
                'td' => ['class', 'colspan', 'rowspan'],
                'th' => ['class', 'colspan', 'rowspan'],
            ];
            
            $tagAllowed = $allowedAttrs[$tag] ?? ['class', 'id'];
            
            // If no attributes, return tag as-is
            if (empty(trim($attrs))) {
                return '<' . $tag . '>';
            }
            
            // Extract and sanitize attributes
            preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attrs, $attrMatches, PREG_SET_ORDER);
            $safeAttrs = [];
            
            foreach ($attrMatches as $attrMatch) {
                $attrName = strtolower($attrMatch[1]);
                $attrValue = htmlspecialchars($attrMatch[2], ENT_QUOTES, 'UTF-8');
                
                // Skip dangerous attributes
                if (strpos($attrName, 'on') === 0 || $attrName === 'style' || $attrName === 'script') {
                    continue;
                }
                
                // Only allow whitelisted attributes
                if (in_array($attrName, $tagAllowed)) {
                    $safeAttrs[] = $attrName . '="' . $attrValue . '"';
                }
            }
            
            $safeAttrsStr = !empty($safeAttrs) ? ' ' . implode(' ', $safeAttrs) : '';
            return '<' . $tag . $safeAttrsStr . '>';
        }, $value);
        
        return $value;
    }

    /**
     * Sanitize to alphanumeric only
     */
    public static function sanitizeAlphanumeric(mixed $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', (string) $value);
    }

    /**
     * Sanitize to URL-safe slug
     */
    public static function sanitizeSlug(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        $value = preg_replace('/[^a-z0-9\-]/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * Sanitize filename - remove path traversal and dangerous characters
     */
    public static function sanitizeFilename(mixed $value): string
    {
        $value = (string) $value;
        // Remove path components
        $value = basename($value);
        // Remove dangerous characters
        $value = preg_replace('/[^a-zA-Z0-9._-]/', '', $value);
        // Prevent hidden files
        $value = ltrim($value, '.');
        return $value ?: 'unnamed';
    }

    /**
     * Escape output for HTML context (XSS protection)
     *
     * @param mixed $value Value to escape
     * @return string Escaped string safe for HTML output
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Alias for escape()
     */
    public static function e(mixed $value): string
    {
        return self::escape($value);
    }

    /**
     * Escape for JavaScript context
     */
    public static function escapeJs(mixed $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Escape for CSS context
     */
    public static function escapeCss(mixed $value): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_.]/', '', (string) $value);
    }

    /**
     * Escape for URL parameter
     */
    public static function escapeUrl(mixed $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * Validate a single value against a rule
     *
     * @param mixed $value Value to validate
     * @param string $rule Rule: required, email, url, int, float, min:n, max:n, between:n,m, regex:pattern, in:a,b,c
     * @return bool True if valid
     */
    public static function validate(mixed $value, string $rule): bool
    {
        // Parse rule and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        return match($ruleName) {
            'required' => self::validateRequired($value),
            'email' => self::validateEmail($value),
            'url' => self::validateUrl($value),
            'int', 'integer' => self::validateInt($value),
            'float', 'numeric' => self::validateFloat($value),
            'bool', 'boolean' => self::validateBool($value),
            'alpha' => self::validateAlpha($value),
            'alphanumeric' => self::validateAlphanumeric($value),
            'slug' => self::validateSlug($value),
            'min' => self::validateMin($value, $params[0] ?? 0),
            'max' => self::validateMax($value, $params[0] ?? PHP_INT_MAX),
            'between' => self::validateBetween($value, $params[0] ?? 0, $params[1] ?? PHP_INT_MAX),
            'length' => self::validateLength($value, $params[0] ?? 0),
            'minLength' => self::validateMinLength($value, $params[0] ?? 0),
            'maxLength' => self::validateMaxLength($value, $params[0] ?? PHP_INT_MAX),
            'regex' => self::validateRegex($value, $params[0] ?? '/.*/'),
            'in' => self::validateIn($value, $params),
            'notIn' => self::validateNotIn($value, $params),
            'date' => self::validateDate($value),
            'ip' => self::validateIp($value),
            'json' => self::validateJson($value),
            'uuid' => self::validateUuid($value),
            default => true,
        };
    }

    /**
     * Validate multiple fields with multiple rules
     *
     * @param array $data Data to validate
     * @param array $rules Rules array ['field' => 'rule1|rule2', ...]
     * @return array Errors array (empty if valid)
     */
    public static function validateAll(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            foreach ($ruleList as $rule) {
                if (!self::validate($value, $rule)) {
                    $errors[$field][] = self::getErrorMessage($field, $rule);
                }
            }
        }

        return $errors;
    }

    /**
     * Check if validation passes
     */
    public static function isValid(array $data, array $rules): bool
    {
        return empty(self::validateAll($data, $rules));
    }

    // Individual validation methods
    private static function validateRequired(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }
        return true;
    }

    private static function validateEmail(mixed $value): bool
    {
        if (empty($value)) return true; // Use 'required' for required fields
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function validateUrl(mixed $value): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private static function validateInt(mixed $value): bool
    {
        if ($value === null || $value === '') return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private static function validateFloat(mixed $value): bool
    {
        if ($value === null || $value === '') return true;
        return is_numeric($value);
    }

    private static function validateBool(mixed $value): bool
    {
        if ($value === null || $value === '') return true; // Allow null/empty for optional checkboxes
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no'], true);
    }

    private static function validateAlpha(mixed $value): bool
    {
        if (empty($value)) return true;
        return ctype_alpha((string) $value);
    }

    private static function validateAlphanumeric(mixed $value): bool
    {
        if (empty($value)) return true;
        return ctype_alnum((string) $value);
    }

    private static function validateSlug(mixed $value): bool
    {
        if (empty($value)) return true;
        return preg_match('/^[a-z0-9\-]+$/', (string) $value) === 1;
    }

    private static function validateMin(mixed $value, mixed $min): bool
    {
        if ($value === null || $value === '') return true;
        return (float) $value >= (float) $min;
    }

    private static function validateMax(mixed $value, mixed $max): bool
    {
        if ($value === null || $value === '') return true;
        return (float) $value <= (float) $max;
    }

    private static function validateBetween(mixed $value, mixed $min, mixed $max): bool
    {
        if ($value === null || $value === '') return true;
        $val = (float) $value;
        return $val >= (float) $min && $val <= (float) $max;
    }

    private static function validateLength(mixed $value, int $length): bool
    {
        if (empty($value)) return true;
        return mb_strlen((string) $value) === (int) $length;
    }

    private static function validateMinLength(mixed $value, int $min): bool
    {
        if (empty($value)) return true;
        return mb_strlen((string) $value) >= (int) $min;
    }

    private static function validateMaxLength(mixed $value, int $max): bool
    {
        if (empty($value)) return true;
        return mb_strlen((string) $value) <= (int) $max;
    }

    private static function validateRegex(mixed $value, string $pattern): bool
    {
        if (empty($value)) return true;
        return preg_match($pattern, (string) $value) === 1;
    }

    private static function validateIn(mixed $value, array $allowed): bool
    {
        if (empty($value)) return true;
        return in_array($value, $allowed, true);
    }

    private static function validateNotIn(mixed $value, array $forbidden): bool
    {
        return !in_array($value, $forbidden, true);
    }

    private static function validateDate(mixed $value): bool
    {
        if (empty($value)) return true;
        return strtotime((string) $value) !== false;
    }

    private static function validateIp(mixed $value): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private static function validateJson(mixed $value): bool
    {
        if (empty($value)) return true;
        json_decode((string) $value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private static function validateUuid(mixed $value): bool
    {
        if (empty($value)) return true;
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $value) === 1;
    }

    /**
     * Get error message for failed validation
     */
    private static function getErrorMessage(string $field, string $rule): string
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = $parts[1] ?? '';

        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'url' => "The {$field} must be a valid URL.",
            'int' => "The {$field} must be an integer.",
            'float' => "The {$field} must be a number.",
            'alpha' => "The {$field} must contain only letters.",
            'alphanumeric' => "The {$field} must contain only letters and numbers.",
            'min' => "The {$field} must be at least {$params}.",
            'max' => "The {$field} must not exceed {$params}.",
            'minLength' => "The {$field} must be at least {$params} characters.",
            'maxLength' => "The {$field} must not exceed {$params} characters.",
            'in' => "The {$field} must be one of: {$params}.",
            'date' => "The {$field} must be a valid date.",
            'ip' => "The {$field} must be a valid IP address.",
            'json' => "The {$field} must be valid JSON.",
            'uuid' => "The {$field} must be a valid UUID.",
        ];

        return $messages[$ruleName] ?? "The {$field} field is invalid.";
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @param int $minLength Minimum length (default: 8)
     * @return array Array of error messages (empty if valid)
     */
    public static function validatePasswordStrength(string $password, int $minLength = 8): array
    {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check for common weak passwords
        $commonPasswords = ['password', '12345678', 'qwerty', 'abc123', 'password123'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password is too common. Please choose a stronger password';
        }
        
        return $errors;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate UUID v4
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Check if email domain is disposable/temporary
     * 
     * @param string $email Email address to check
     * @return bool True if disposable email
     */
    public static function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        
        // Common disposable email domains
        $disposableDomains = [
            '10minutemail.com', '10minutemail.de', '20minutemail.com', '33mail.com',
            'guerrillamail.com', 'guerrillamailblock.com', 'mailinator.com', 'tempmail.com',
            'tempmail.org', 'throwaway.email', 'tmpmail.org', 'yopmail.com',
            'mohmal.com', 'getnada.com', 'maildrop.cc', 'sharklasers.com',
            'trashmail.com', 'tempr.email', 'dispostable.com', 'mintemail.com',
            'mailcatch.com', 'fakeinbox.com', 'spamgourmet.com', 'mailnesia.com',
            'mytrashmail.com', 'temp-mail.org', 'getairmail.com',
        ];
        
        return in_array($domain, $disposableDomains);
    }

    /**
     * Validate form submission timing (anti-spam)
     * Checks if form was filled too quickly (likely a bot)
     * 
     * @param string $formKey Unique key for the form
     * @param int $minSeconds Minimum seconds to fill form (default: 3)
     * @return bool True if timing is valid
     */
    public static function validateFormTiming(string $formKey, int $minSeconds = 3): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false; // Session not started
        }
        
        if (!isset($_SESSION['form_start_time'][$formKey])) {
            return false; // No start time recorded
        }
        
        $startTime = $_SESSION['form_start_time'][$formKey];
        $elapsed = time() - $startTime;
        
        // Clear the start time after validation
        unset($_SESSION['form_start_time'][$formKey]);
        
        // Form must take at least minSeconds to fill
        return $elapsed >= $minSeconds;
    }

    /**
     * Set form start time (call when displaying form)
     * 
     * @param string $formKey Unique key for the form
     */
    public static function setFormStartTime(string $formKey): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['form_start_time'])) {
            $_SESSION['form_start_time'] = [];
        }
        $_SESSION['form_start_time'][$formKey] = time();
    }

    /**
     * Check honeypot field (should be empty)
     * 
     * @param mixed $value Honeypot field value
     * @return bool True if valid (empty)
     */
    public static function validateHoneypot($value): bool
    {
        return empty($value);
    }

    /**
     * Check if username appears to be a random string (anti-spam)
     * Detects patterns that indicate randomly generated usernames
     * 
     * @param string $username Username to check
     * @return bool True if username appears to be random
     */
    public static function isRandomUsername(string $username): bool
    {
        if (empty($username)) {
            return false; // Empty is handled by 'required' validation
        }

        $username = trim($username);
        $length = mb_strlen($username);

        // Too short to analyze effectively
        if ($length < 6) {
            return false;
        }

        // Count vowels (A, E, I, O, U) - random strings typically have few vowels
        $vowels = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
        $vowelCount = 0;
        $upperCount = 0;
        $lowerCount = 0;
        $numberCount = 0;
        $charFrequency = [];
        
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($username, $i, 1);
            
            if (in_array($char, $vowels, true)) {
                $vowelCount++;
            }
            
            if (ctype_upper($char)) {
                $upperCount++;
            } elseif (ctype_lower($char)) {
                $lowerCount++;
            } elseif (ctype_digit($char)) {
                $numberCount++;
            }
            
            // Track character frequency
            $charLower = mb_strtolower($char);
            $charFrequency[$charLower] = ($charFrequency[$charLower] ?? 0) + 1;
        }

        // Test 1: Vowel ratio - random strings typically have < 20% vowels
        $vowelRatio = $vowelCount / $length;
        if ($vowelRatio < 0.15 && $length >= 10) {
            return true; // Very few vowels indicates random string
        }

        // Test 2: Excessive case alternation (random mixing of upper/lower)
        // Count case changes (excluding first character)
        $caseChanges = 0;
        $prevIsUpper = ctype_upper(mb_substr($username, 0, 1));
        for ($i = 1; $i < $length; $i++) {
            $char = mb_substr($username, $i, 1);
            if (ctype_alpha($char)) {
                $currIsUpper = ctype_upper($char);
                if ($currIsUpper !== $prevIsUpper) {
                    $caseChanges++;
                }
                $prevIsUpper = $currIsUpper;
            }
        }
        // If more than 40% of characters cause case changes, it's suspicious
        if ($caseChanges > ($length * 0.4)) {
            return true;
        }

        // Test 3: High character diversity with low repetition (high entropy)
        // Random strings have many unique characters with low frequency
        $uniqueChars = count($charFrequency);
        $entropyRatio = $uniqueChars / $length;
        // If more than 70% of characters are unique and string is long, it's suspicious
        if ($entropyRatio > 0.70 && $length >= 12) {
            // Additional check: if no character appears more than twice, very suspicious
            $maxFrequency = max($charFrequency);
            if ($maxFrequency <= 2 && $length >= 15) {
                return true;
            }
        }

        // Test 4: Excessive uppercase after first character (random capitalization)
        // If more than 50% of letters are uppercase (excluding first char) in a long string
        $alphaCount = $upperCount + $lowerCount;
        if ($alphaCount > 0) {
            $upperRatio = $upperCount / $alphaCount;
            // If more than 60% uppercase and string is long, suspicious
            if ($upperRatio > 0.60 && $length >= 10 && $upperCount > 5) {
                return true;
            }
        }

        // Test 5: Pattern detection - sequences that look random
        // Check for alternating case pattern (aBcDeF) which is common in random strings
        if ($length >= 8) {
            $alternatingPattern = 0;
            for ($i = 0; $i < min(8, $length - 1); $i++) {
                $char1 = mb_substr($username, $i, 1);
                $char2 = mb_substr($username, $i + 1, 1);
                if (ctype_alpha($char1) && ctype_alpha($char2)) {
                    $case1 = ctype_upper($char1);
                    $case2 = ctype_upper($char2);
                    if ($case1 !== $case2) {
                        $alternatingPattern++;
                    }
                }
            }
            // If first 8 chars alternate case frequently, suspicious
            if ($alternatingPattern >= 5) {
                return true;
            }
        }

        return false; // Doesn't appear to be random
    }
}


if (!\class_exists('Security', false) && !\interface_exists('Security', false) && !\trait_exists('Security', false)) {
    \class_alias(__NAMESPACE__ . '\\Security', 'Security');
}
