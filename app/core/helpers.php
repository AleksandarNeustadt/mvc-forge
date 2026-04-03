<?php
// core/helpers.php - Global helper functions

/**
 * Dump and Die - Dump variables and stop execution
 * Only works when APP_DEBUG=true
 *
 * @param mixed ...$vars Variables to dump
 */
function dd(mixed ...$vars): never
{
    Debug::dd(...$vars);
}

/**
 * Dump and Print - Dump variables without stopping
 * Only works when APP_DEBUG=true
 *
 * @param mixed ...$vars Variables to dump
 */
function dp(mixed ...$vars): void
{
    Debug::dp(...$vars);
}

/**
 * Escape output for HTML (XSS protection)
 *
 * @param mixed $value Value to escape
 * @return string Escaped string
 */
function e(mixed $value): string
{
    return Security::escape($value);
}

/**
 * Get sanitized input value
 *
 * @param string $key Input key
 * @param mixed $default Default value
 * @param string $type Sanitization type
 * @return mixed Sanitized value
 */
function input(string $key, mixed $default = null, string $type = 'string'): mixed
{
    return Input::input($key, $default, $type);
}

/**
 * Generate CSRF token field for forms
 *
 * @return string Hidden input with CSRF token
 */
function csrf_field(): string
{
    return CSRF::field();
}

/**
 * Get CSRF token value
 *
 * @return string CSRF token
 */
function csrf_token(): string
{
    return CSRF::token();
}

/**
 * Check if app is in debug mode
 *
 * @return bool
 */
function is_debug(): bool
{
    return Debug::isEnabled();
}

/**
 * Abort with HTTP status code
 *
 * @param int $code HTTP status code
 * @param string $message Error message
 */
function abort(int $code, string $message = ''): never
{
    http_response_code($code);

    $messages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    $message = $message ?: ($messages[$code] ?? 'Error');

    // Check if JSON response is expected
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message, 'code' => $code]);
    } else {
        echo "<h1>{$code} - {$message}</h1>";
    }

    exit;
}

/**
 * Generate SEO-friendly slug from string
 *
 * @param string $text Input text
 * @param string $separator Separator character (default: '-')
 * @return string Generated slug
 */
function str_slug(string $text, string $separator = '-'): string {
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');

    // Transliterate special characters (Serbian Cyrillic to Latin)
    $cyrillic = [
        'а', 'б', 'в', 'г', 'д', 'ђ', 'е', 'ж', 'з', 'и', 'ј', 'к', 'л', 'љ', 'м', 'н', 'њ', 'о', 'п', 'р', 'с', 'т', 'ћ', 'у', 'ф', 'х', 'ц', 'ч', 'џ', 'ш',
        'А', 'Б', 'В', 'Г', 'Д', 'Ђ', 'Е', 'Ж', 'З', 'И', 'Ј', 'К', 'Л', 'Љ', 'М', 'Н', 'Њ', 'О', 'П', 'Р', 'С', 'Т', 'Ћ', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Џ', 'Ш'
    ];
    $latin = [
        'a', 'b', 'v', 'g', 'd', 'dj', 'e', 'z', 'z', 'i', 'j', 'k', 'l', 'lj', 'm', 'n', 'nj', 'o', 'p', 'r', 's', 't', 'c', 'u', 'f', 'h', 'c', 'c', 'dz', 's',
        'a', 'b', 'v', 'g', 'd', 'dj', 'e', 'z', 'z', 'i', 'j', 'k', 'l', 'lj', 'm', 'n', 'nj', 'o', 'p', 'r', 's', 't', 'c', 'u', 'f', 'h', 'c', 'c', 'dz', 's'
    ];
    $text = str_replace($cyrillic, $latin, $text);

    // Transliterate other special characters
    $specialChars = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'č' => 'c', 'ć' => 'c', 'ž' => 'z', 'š' => 's',
        'đ' => 'dj'
    ];
    $text = str_replace(array_keys($specialChars), array_values($specialChars), $text);

    // Remove all non-alphanumeric characters except separator
    $text = preg_replace('/[^a-z0-9' . preg_quote($separator) . ']+/i', $separator, $text);

    // Remove duplicate separators
    $text = preg_replace('/' . preg_quote($separator) . '{2,}/', $separator, $text);

    // Trim separators from beginning and end
    $text = trim($text, $separator);

    return $text;
}

/**
 * Generate unique slug (for database usage)
 *
 * @param string $text Input text
 * @param string $table Table name to check uniqueness (optional)
 * @param string $column Column name (default: 'slug')
 * @return string Unique slug
 */
function unique_slug(string $text, string $table = null, string $column = 'slug'): string {
    $slug = str_slug($text);

    // If no table provided, just return the slug
    if (!$table) {
        return $slug;
    }

    // TODO: Check database for uniqueness and append number if needed
    // Example: $exists = DB::table($table)->where($column, $slug)->exists();
    // if ($exists) { $slug .= '-' . time(); }

    return $slug;
}

/**
 * Resolve the current router from the container, with legacy global fallback.
 */
function app_router(): ?Router {
    if (function_exists('app')) {
        try {
            $router = app('router');
            if ($router instanceof Router) {
                return $router;
            }
        } catch (Throwable $e) {
            // Fall through to legacy global fallback.
        }
    }

    return isset($GLOBALS['router']) && $GLOBALS['router'] instanceof Router
        ? $GLOBALS['router']
        : null;
}

/**
 * Generate route URL with language prefix
 *
 * @param string $name Route name
 * @param array $params Route parameters
 * @param string|null $lang Language code (null = use current)
 * @return string Generated URL
 */
function route(string $name, array $params = [], ?string $lang = null): string {
    $router = app_router();

    // Get current language if not specified
    if ($lang === null) {
        $lang = $router->lang ?? 'sr';
    }

    // Get route collection and generate URL
    $url = Route::url($name, $params);

    if ($url === null) {
        return '#';
    }

    // Prepend language prefix
    return '/' . $lang . $url;
}

/**
 * Get current language
 *
 * @return string Current language code
 */
function current_lang(): string {
    return app_router()?->lang ?? 'sr';
}

/**
 * Check if current user has permission
 *
 * @param string $permission Permission slug
 * @return bool True if user has permission
 */
function can(string $permission): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if (!class_exists('User')) {
        return false;
    }
    
    $user = User::find($_SESSION['user_id']);
    if (!$user) {
        return false;
    }
    
    return $user->hasPermission($permission);
}

/**
 * Get current authenticated user
 *
 * @return User|null Current user or null if not authenticated
 */
function current_user(): ?User {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    if (!class_exists('User')) {
        return null;
    }
    
    return User::find($_SESSION['user_id']);
}

/**
 * Get CSP nonce for inline scripts
 *
 * @return string Nonce value
 */
function csp_nonce(): string {
    if (class_exists('SecurityHeadersMiddleware')) {
        return SecurityHeadersMiddleware::getNonce();
    }
    return '';
}

/**
 * Create FormBuilder instance with automatic language prefix
 * Helper function for easier form creation in templates
 *
 * @param string $path Form action path (without language prefix, e.g., '/dashboard/users')
 * @param string $method HTTP method (default: 'POST')
 * @param array $data Optional data to populate form (for edit forms)
 * @return FormBuilder FormBuilder instance
 */
function form(string $path, string $method = 'POST', array $data = []): FormBuilder {
    $lang = current_lang();
    
    // Add language prefix to path
    $fullPath = '/' . $lang . $path;
    
    $form = new FormBuilder($fullPath, $method);
    
    // If data provided, use it as old values (for edit forms)
    if (!empty($data)) {
        $form->withOld($data);
    }
    
    // Auto-load errors if they exist
    if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
        $form->errors(Form::getErrors());
    }
    
    return $form;
}

/**
 * Get flag code for a language code (dynamic from database or fallback map)
 * 
 * This function first checks if the language exists in the database and has a flag set.
 * If not, it uses a fallback mapping. This allows dynamic language addition without code changes.
 *
 * @param string $langCode Language code (e.g., 'mk', 'sr', 'en')
 * @param array|null $languageData Optional language data array with 'flag' key (to avoid DB query)
 * @return string Flag code for flag-icons library (e.g., 'mk', 'rs', 'gb')
 */
function get_flag_code(string $langCode, ?array $languageData = null): string {
    static $flagCache = [];
    static $fallbackMap = [
        'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
        'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
        'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
        'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
        'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
        'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr'
    ];
    
    $langCode = strtolower(trim($langCode));
    
    if (empty($langCode)) {
        return 'xx';
    }
    
    // Check cache first
    if (isset($flagCache[$langCode])) {
        return $flagCache[$langCode];
    }
    
    // If language data is provided, use it directly
    if ($languageData !== null && !empty($languageData['flag'])) {
        $flag = trim($languageData['flag']);
        // Remove emoji if present, extract country code
        $flag = preg_replace('/[\x{1F1E6}-\x{1F1FF}]{2}/u', '', $flag); // Remove flag emojis
        $flag = strtolower(trim($flag));
        if (!empty($flag) && strlen($flag) <= 2) {
            $flagCache[$langCode] = $flag;
            return $flag;
        }
    }
    
    // Try to get from database if Language model exists
    if (class_exists('Language')) {
        try {
            $language = Language::findByCode($langCode);
            if ($language && !empty($language->flag)) {
                $flag = trim($language->flag);
                // Remove emoji if present, extract country code
                $flag = preg_replace('/[\x{1F1E6}-\x{1F1FF}]{2}/u', '', $flag); // Remove flag emojis
                $flag = strtolower(trim($flag));
                if (!empty($flag) && strlen($flag) <= 2) {
                    $flagCache[$langCode] = $flag;
                    return $flag;
                }
            }
        } catch (Exception $e) {
            // Silently fall back to map if DB query fails
        }
    }
    
    // Use fallback map
    if (isset($fallbackMap[$langCode])) {
        $flagCache[$langCode] = $fallbackMap[$langCode];
        return $fallbackMap[$langCode];
    }
    
    // Last resort: use language code as flag code (works for many cases like 'mk', 'pl', etc.)
    $flagCache[$langCode] = $langCode;
    return $langCode;
}

if (!function_exists('app')) {
    /**
     * Resolve the application container or one of its bindings.
     */
    function app(?string $key = null): mixed
    {
        $container = Container::getInstance();

        return $key === null ? $container : $container->make($key);
    }
}
