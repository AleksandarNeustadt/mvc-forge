<!-- Header Component - Global Sticky Navigation -->
<?php
// Ensure User class is available
if (!class_exists('User')) {
    require_once __DIR__ . '/../../core/mvc/Model.php';
    require_once __DIR__ . '/../../mvc/models/User.php';
}

// Check if this is a dashboard page
$isDashboard = false;
if (isset($viewPath) && strpos($viewPath, '/dashboard/') !== false) {
    $isDashboard = true;
} else {
    // Fallback: check URL path
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $currentPath = parse_url($currentPath, PHP_URL_PATH);
    if (strpos($currentPath, '/dashboard') !== false) {
        $isDashboard = true;
    }
}

// Get current language from multiple sources with fallbacks
global $router;
$currentLang = 'sr'; // Default fallback

// Try router first (most reliable)
if (isset($router) && is_object($router) && property_exists($router, 'lang') && !empty($router->lang) && is_string($router->lang)) {
    $currentLang = (string)$router->lang;
} elseif (isset($lang) && !empty($lang) && is_string($lang)) {
    $currentLang = (string)$lang;
} else {
    // Extract from URL as last resort
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $uriPath = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = array_filter(explode('/', trim($uriPath, '/')));
    if (!empty($pathParts) && is_string($pathParts[0])) {
        $langFromUrl = $pathParts[0];
        $supportedLangs = ['sr', 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'uk', 'cs', 'hu', 'el', 'ro', 'hr', 'bg', 'sk', 'sv', 'da', 'no', 'fi', 'lt', 'et', 'lv', 'sl', 'zh', 'ja', 'ko', 'tr'];
        if (in_array($langFromUrl, $supportedLangs)) {
            $currentLang = (string)$langFromUrl;
        }
    }
}
// Final safety check - ensure it's always a valid string
$currentLang = (string)$currentLang;
if (empty($currentLang)) {
    $currentLang = 'sr';
}

$flagCodes = [
    'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
    'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
    'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
    'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
    'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
    'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr'
];
$currentFlagCode = (isset($currentLang) && isset($flagCodes[$currentLang])) ? $flagCodes[$currentLang] : 'rs';

function langLink($code, $flagCode, $name, $currentLang) {
    // Ensure currentLang is a string for comparison
    $currentLang = (string)($currentLang ?? 'sr');
    $isActive = $currentLang === $code;
    $activeClass = $isActive ? 'bg-slate-800/80 border-l-2 border-theme-primary' : '';
    $textClass = $isActive ? 'text-white' : 'text-slate-300 group-hover:text-white';
    $checkmark = $isActive ? '<svg class="w-4 h-4 text-theme-primary ml-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>' : '';

    return '<a href="' . htmlspecialchars(translated_content_path($code, null, $currentLang), ENT_QUOTES, 'UTF-8') . '" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/60 rounded-lg transition-colors group ' . $activeClass . '">
        <span class="fi fi-' . $flagCode . '" style="font-size: 1.25rem;"></span>
        <span class="' . $textClass . ' font-medium">' . htmlspecialchars($name) . '</span>
        ' . $checkmark . '
    </a>';
}

function renderLanguageDropdown($prefix = '') {
    if (!site_is_multilingual()) {
        return;
    }

    global $currentLang, $currentFlagCode, $flagCodes, $lang, $router;
    
    // Get router if not already available
    if (!isset($router)) {
        global $router;
    }
    
    // Ensure currentLang is set with proper fallbacks - try router first, then other sources
    $localCurrentLang = $currentLang ?? null;
    
    // Always validate and set a default
    if (empty($localCurrentLang) || !is_string($localCurrentLang)) {
        // Try router first (most reliable source)
        if (isset($router) && is_object($router) && property_exists($router, 'lang') && !empty($router->lang) && is_string($router->lang)) {
            $localCurrentLang = (string)$router->lang;
        } elseif (isset($lang) && !empty($lang) && is_string($lang)) {
            $localCurrentLang = (string)$lang;
        } else {
            // Extract from URL as last resort
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $uriPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
            $pathParts = array_values(array_filter(explode('/', trim($uriPath, '/'))));
            $langFromUrl = (!empty($pathParts) && isset($pathParts[0]) && is_string($pathParts[0])) ? $pathParts[0] : 'sr';
            $supportedLangs = ['sr', 'en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'uk', 'cs', 'hu', 'el', 'ro', 'hr', 'bg', 'sk', 'sv', 'da', 'no', 'fi', 'lt', 'et', 'lv', 'sl', 'zh', 'ja', 'ko', 'tr'];
            $localCurrentLang = in_array($langFromUrl, $supportedLangs) ? (string)$langFromUrl : 'sr';
        }
    }
    
    // Final safety check - ensure it's always a valid string
    $localCurrentLang = (string)($localCurrentLang ?? 'sr');
    if (empty($localCurrentLang) || !is_string($localCurrentLang)) {
        $localCurrentLang = 'sr';
    }
    // Update global for langLink calls and use local variable
    $currentLang = $localCurrentLang;
    $localCurrentLang = $currentLang; // Use local variable consistently
    
    // Ensure flagCodes array exists
    if (!isset($flagCodes) || !is_array($flagCodes)) {
        $flagCodes = [
            'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
            'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
            'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
            'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
            'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
            'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr'
        ];
    }
    
    // Ensure currentFlagCode is set
    if (empty($currentFlagCode) || !is_string($currentFlagCode)) {
        $currentFlagCode = isset($flagCodes[$currentLang]) ? (string)$flagCodes[$currentLang] : 'rs';
    }
    $currentFlagCode = (string)$currentFlagCode;
    
    $toggleId = $prefix ? $prefix . '-language-toggle' : 'language-toggle';
    $dropdownId = $prefix ? $prefix . '-language-dropdown' : 'language-dropdown';
    $arrowId = $prefix ? $prefix . '-dropdown-arrow' : 'dropdown-arrow';
    
    // Standalone version (for when user is not logged in)
    echo '<div class="relative">';
    echo '<button type="button" id="' . htmlspecialchars($toggleId) . '" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 transition-all h-[40px]" aria-expanded="false">';
    echo '<span class="fi fi-' . htmlspecialchars($currentFlagCode) . ' text-xl"></span>';
    // Double-check currentLang is valid before strtoupper - use local variable that's guaranteed to be valid
    $langForDisplay = (!empty($localCurrentLang) && is_string($localCurrentLang)) ? trim($localCurrentLang) : 'sr';
    if (empty($langForDisplay) || !is_string($langForDisplay)) {
        $langForDisplay = 'sr';
    }
    echo '<span class="hidden sm:inline text-slate-300 font-medium">' . htmlspecialchars(strtoupper($langForDisplay)) . '</span>';
    echo '<svg id="' . $arrowId . '" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
    echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>';
    echo '</svg>';
    echo '</button>';
    
    echo '<div id="' . htmlspecialchars($dropdownId) . '" class="hidden absolute right-0 mt-2 w-72 sm:w-80 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl" style="z-index: 60;">';
    echo '<div class="p-2 border-b border-slate-800/50">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Balkan</div>';
    echo langLink('sr', 'rs', 'Srpski', $localCurrentLang);
    echo langLink('hr', 'hr', 'Hrvatski', $localCurrentLang);
    echo langLink('bg', 'bg', 'Български', $localCurrentLang);
    echo langLink('ro', 'ro', 'Română', $localCurrentLang);
    echo langLink('sl', 'si', 'Slovenščina', $localCurrentLang);
    echo langLink('el', 'gr', 'Ελληνικά', $localCurrentLang);
    echo '</div>';
    echo '<div class="p-2 border-b border-slate-800/50">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Western Europe</div>';
    echo langLink('en', 'gb', 'English', $localCurrentLang);
    echo langLink('de', 'de', 'Deutsch', $localCurrentLang);
    echo langLink('fr', 'fr', 'Français', $localCurrentLang);
    echo langLink('es', 'es', 'Español', $localCurrentLang);
    echo langLink('it', 'it', 'Italiano', $localCurrentLang);
    echo langLink('pt', 'pt', 'Português', $localCurrentLang);
    echo langLink('nl', 'nl', 'Nederlands', $localCurrentLang);
    echo '</div>';
    echo '<div class="p-2 border-b border-slate-800/50">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Eastern Europe</div>';
    echo langLink('pl', 'pl', 'Polski', $localCurrentLang);
    echo langLink('ru', 'ru', 'Русский', $localCurrentLang);
    echo langLink('uk', 'ua', 'Українська', $localCurrentLang);
    echo langLink('cs', 'cz', 'Čeština', $localCurrentLang);
    echo langLink('sk', 'sk', 'Slovenčina', $localCurrentLang);
    echo langLink('hu', 'hu', 'Magyar', $localCurrentLang);
    echo '</div>';
    echo '<div class="p-2 border-b border-slate-800/50">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Northern Europe</div>';
    echo langLink('sv', 'se', 'Svenska', $localCurrentLang);
    echo langLink('da', 'dk', 'Dansk', $localCurrentLang);
    echo langLink('no', 'no', 'Norsk', $localCurrentLang);
    echo langLink('fi', 'fi', 'Suomi', $localCurrentLang);
    echo '</div>';
    echo '<div class="p-2 border-b border-slate-800/50">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Baltic</div>';
    echo langLink('lt', 'lt', 'Lietuvių', $localCurrentLang);
    echo langLink('et', 'ee', 'Eesti', $localCurrentLang);
    echo langLink('lv', 'lv', 'Latviešu', $localCurrentLang);
    echo '</div>';
    echo '<div class="p-2">';
    echo '<div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Asia & Other</div>';
    echo langLink('zh', 'cn', '中文', $localCurrentLang);
    echo langLink('ja', 'jp', '日本語', $localCurrentLang);
    echo langLink('ko', 'kr', '한국어', $localCurrentLang);
    echo langLink('tr', 'tr', 'Türkçe', $localCurrentLang);
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>
<header class="fixed top-0 left-0 right-0 z-50 bg-slate-950 backdrop-blur-md border-b border-slate-800/50">
    <nav class="<?= $isDashboard ? '' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' ?>">
        <?php if ($isDashboard): ?>
            <!-- Dashboard Header Layout with Breadcrumb -->
            <div class="flex items-center h-20">
                <!-- LEFT: Brand (same width as sidebar - w-64) -->
                <div class="w-64 flex-shrink-0 flex items-center px-4 border-r border-slate-700/50">
                    <a href="<?= htmlspecialchars(localized_path('/dashboard', $currentLang)) ?>" class="text-xl sm:text-2xl font-bold text-white tracking-tight hover:scale-105 transition-transform">
                        <?= Env::get('BRAND_NAME', 'aleksandar.pro') ?>
                    </a>
                </div>
                
                <!-- RIGHT: Breadcrumb + Auth Controls (aligned with content) -->
                <div class="flex-1 flex items-center justify-between px-6 min-w-0">
                    <div class="flex items-center gap-6 flex-1 min-w-0">
                        <?php
                        // Load breadcrumb helper for dashboard
                        require_once __DIR__ . '/../helpers/breadcrumb.php';
                        renderBreadcrumb(null, true); // inline = true for header
                        ?>
                    </div>
                    
                    <!-- Auth Controls (Language + User) -->
                    <div class="flex items-center space-x-3 flex-shrink-0 ml-4">
                        <?php
                        // Check if user is logged in - use session data only (no DB calls in header)
                        $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
                        $user = null;
                        
                        if ($isLoggedIn) {
                            // Use session data directly - avoid database calls in header for performance
                            $user = (object) [
                                'id' => $_SESSION['user_id'] ?? null,
                                'username' => $_SESSION['user_username'] ?? 'User',
                                'email' => $_SESSION['user_email'] ?? '',
                                'first_name' => $_SESSION['user_first_name'] ?? null,
                                'last_name' => $_SESSION['user_last_name'] ?? null,
                                'avatar' => $_SESSION['user_avatar'] ?? null
                            ];
                        }
                        ?>

                        <?php if (!$isLoggedIn): ?>
                            <!-- Language Selector (visible only when not logged in) -->
                            <?php renderLanguageDropdown(); ?>
                        <?php endif; ?>

                        <?php if (!$isLoggedIn): ?>
                            <!-- Login / Register Links (when not logged in) -->
                            <div class="flex items-center space-x-2">
                                <a href="<?= htmlspecialchars(localized_path('/login', $currentLang)) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 text-slate-300 hover:text-white font-medium transition-all h-[40px]">
                                    Login
                                </a>
                                <a href="<?= htmlspecialchars(localized_path('/register', $currentLang)) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-theme-primary hover:bg-theme-primary/90 text-white font-medium transition-all border border-transparent h-[40px]">
                                    Register
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- User Profile Dropdown (when logged in) -->
                            <div class="relative">
                                <button
                                    type="button"
                                    id="user-menu-toggle"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 transition-all h-[40px]"
                                    aria-expanded="false"
                                >
                                    <?php if ($user && !empty($user->avatar)): ?>
                                        <img src="<?= htmlspecialchars($user->avatar) ?>" alt="<?= htmlspecialchars($user->username) ?>" class="w-8 h-8 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center">
                                            <span class="text-theme-primary font-semibold text-sm">
                                                <?= strtoupper(substr($user->username ?? 'U', 0, 1)) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="hidden sm:inline text-slate-300 font-medium"><?= htmlspecialchars($user->username ?? 'User') ?></span>
                                    <svg id="user-dropdown-arrow" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <!-- User Dropdown Menu -->
                                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl overflow-visible" style="z-index: 60;">
                                    <div class="p-2 overflow-visible">
                                        <div class="px-3 py-2 border-b border-slate-800/50">
                                            <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></p>
                                            <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($user->email ?? '') ?></p>
                                        </div>
                                        <?php
                                        // Only show dashboard link if user has permission
                                        $currentUserForPermission = null;
                                        try {
                                            $currentUserForPermission = isset($_SESSION['user_id']) ? User::find($_SESSION['user_id']) : null;
                                        } catch (Exception $e) {
                                            // User not found or error, continue without dashboard link
                                        }
                                        if ($currentUserForPermission && ($currentUserForPermission->hasPermission('system.dashboard') || $currentUserForPermission->isSuperAdmin())):
                                        ?>
                                            <a href="<?= htmlspecialchars(localized_path('/dashboard', $currentLang)) ?>" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/60 rounded-lg transition-colors group">
                                                <ion-icon name="grid-outline" class="w-5 h-5 text-slate-400 group-hover:text-theme-primary flex-shrink-0"></ion-icon>
                                                <span class="text-slate-300 group-hover:text-white font-medium">Dashboard</span>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Language Selector (integrated in user menu) -->
                                        <div class="border-t border-slate-800/50 my-2"></div>
                                        <div class="relative w-full group/language">
                                            <button type="button" id="user-menu-language-toggle" class="w-full flex items-center justify-between gap-3 px-3 py-2 hover:bg-slate-800/60 rounded-lg transition-colors group">
                                                <div class="flex items-center gap-3">
                                                    <ion-icon name="globe-outline" class="w-5 h-5 text-slate-400 group-hover:text-theme-primary flex-shrink-0"></ion-icon>
                                                    <span class="text-slate-300 group-hover:text-white font-medium">Language</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="fi fi-<?= $currentFlagCode ?> text-base"></span>
                                                    <svg id="user-menu-language-arrow" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </div>
                                            </button>
                                            
                                            <!-- Language Dropdown (appears on the side) -->
                                            <div id="user-menu-language-dropdown" class="hidden fixed w-72 sm:w-80 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl" style="z-index: 70; max-height: 80vh; overflow-y: auto;">
                                                <div class="p-2 border-b border-slate-800/50">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Balkan</div>
                                                    <?= langLink('sr', 'rs', 'Srpski', $currentLang) ?>
                                                    <?= langLink('hr', 'hr', 'Hrvatski', $currentLang) ?>
                                                    <?= langLink('bg', 'bg', 'Български', $currentLang) ?>
                                                    <?= langLink('ro', 'ro', 'Română', $currentLang) ?>
                                                    <?= langLink('sl', 'si', 'Slovenščina', $currentLang) ?>
                                                    <?= langLink('el', 'gr', 'Ελληνικά', $currentLang) ?>
                                                </div>
                                                <div class="p-2 border-b border-slate-800/50">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Western Europe</div>
                                                    <?= langLink('en', 'gb', 'English', $currentLang) ?>
                                                    <?= langLink('de', 'de', 'Deutsch', $currentLang) ?>
                                                    <?= langLink('fr', 'fr', 'Français', $currentLang) ?>
                                                    <?= langLink('es', 'es', 'Español', $currentLang) ?>
                                                    <?= langLink('it', 'it', 'Italiano', $currentLang) ?>
                                                    <?= langLink('pt', 'pt', 'Português', $currentLang) ?>
                                                    <?= langLink('nl', 'nl', 'Nederlands', $currentLang) ?>
                                                </div>
                                                <div class="p-2 border-b border-slate-800/50">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Eastern Europe</div>
                                                    <?= langLink('pl', 'pl', 'Polski', $currentLang) ?>
                                                    <?= langLink('ru', 'ru', 'Русский', $currentLang) ?>
                                                    <?= langLink('uk', 'ua', 'Українська', $currentLang) ?>
                                                    <?= langLink('cs', 'cz', 'Čeština', $currentLang) ?>
                                                    <?= langLink('sk', 'sk', 'Slovenčina', $currentLang) ?>
                                                    <?= langLink('hu', 'hu', 'Magyar', $currentLang) ?>
                                                </div>
                                                <div class="p-2 border-b border-slate-800/50">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Northern Europe</div>
                                                    <?= langLink('sv', 'se', 'Svenska', $currentLang) ?>
                                                    <?= langLink('da', 'dk', 'Dansk', $currentLang) ?>
                                                    <?= langLink('no', 'no', 'Norsk', $currentLang) ?>
                                                    <?= langLink('fi', 'fi', 'Suomi', $currentLang) ?>
                                                </div>
                                                <div class="p-2 border-b border-slate-800/50">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Baltic</div>
                                                    <?= langLink('lt', 'lt', 'Lietuvių', $currentLang) ?>
                                                    <?= langLink('et', 'ee', 'Eesti', $currentLang) ?>
                                                    <?= langLink('lv', 'lv', 'Latviešu', $currentLang) ?>
                                                </div>
                                                <div class="p-2">
                                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Asia & Other</div>
                                                    <?= langLink('zh', 'cn', '中文', $currentLang) ?>
                                                    <?= langLink('ja', 'jp', '日本語', $currentLang) ?>
                                                    <?= langLink('ko', 'kr', '한국어', $currentLang) ?>
                                                    <?= langLink('tr', 'tr', 'Türkçe', $currentLang) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="border-t border-slate-800/50 my-2"></div>
                                        <form method="POST" action="<?= htmlspecialchars(localized_path('/logout', $currentLang)) ?>" class="p-0 m-0">
                                            <?php
                                            // Generate CSRF token
                                            if (class_exists('CSRF')) {
                                                echo CSRF::field();
                                            }
                                            ?>
                                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 hover:bg-red-900/20 rounded-lg transition-colors group text-left">
                                                <ion-icon name="log-out-outline" class="w-5 h-5 text-slate-400 group-hover:text-red-400 flex-shrink-0"></ion-icon>
                                                <span class="text-slate-300 group-hover:text-red-400 font-medium">Logout</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Regular Header Layout -->
            <div class="flex items-center justify-between h-16">
                <!-- LEFT: Brand -->
                <div class="flex-shrink-0">
                    <a href="<?= htmlspecialchars(localized_path('/', $currentLang)) ?>" class="text-xl sm:text-2xl font-bold text-white tracking-tight hover:scale-105 transition-transform">
                        <?= Env::get('BRAND_NAME', 'aleksandar.pro') ?>
                    </a>
                </div>

                <!-- CENTER: Desktop Menu (visible on lg+) -->
                <div class="hidden lg:flex items-center space-x-8">
                    <?php
                    // Get current language ID for filtering
                    $currentLanguageId = null;
                    if (class_exists('Language')) {
                        $currentLanguage = Language::findByCode($currentLang);
                        if ($currentLanguage) {
                            $currentLanguageId = $currentLanguage->id;
                        }
                    }
                    
                    // Get navigation menus for header position filtered by language
                    if (class_exists('NavigationMenu')) {
                        $headerMenus = NavigationMenu::getByPosition('header', $currentLanguageId);
                        foreach ($headerMenus as $menu) {
                            $menuItems = $menu->getMenuItems();
                            foreach ($menuItems as $menuPage) {
                                $url = localized_path($menuPage->route, $currentLang);
                                $title = htmlspecialchars($menuPage->title ?? '');
                                echo '<a href="' . $url . '" class="text-slate-300 hover:text-theme-primary transition-colors font-medium">' . $title . '</a>';
                            }
                        }
                    } elseif (class_exists('Page')) {
                        // Fallback to old behavior if NavigationMenu doesn't exist yet
                        $menuItems = Page::getMenuItems();
                        foreach ($menuItems as $menuPage) {
                            $url = localized_path($menuPage->route, $currentLang);
                            $title = htmlspecialchars($menuPage->title ?? '');
                            echo '<a href="' . $url . '" class="text-slate-300 hover:text-theme-primary transition-colors font-medium">' . $title . '</a>';
                        }
                    }
                    ?>
                </div>

                <!-- RIGHT: Mobile Menu Toggle + Language Selector + Auth -->
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <!-- Mobile Menu Toggle (visible < lg) -->
                    <button
                        type="button"
                        id="mobile-menu-toggle"
                        class="lg:hidden flex items-center justify-center px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 transition-all"
                        aria-label="Menu"
                        aria-expanded="false"
                    >
                        <ion-icon name="menu" class="text-white text-xl" id="menu-icon-open"></ion-icon>
                        <ion-icon name="close" class="hidden text-white text-xl" id="menu-icon-close"></ion-icon>
                    </button>

                    <?php
                    // Check if user is logged in - use session data only (no DB calls in header)
                    $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
                    $user = null;
                    
                    if ($isLoggedIn) {
                        // Use session data directly - avoid database calls in header for performance
                        $user = (object) [
                            'id' => $_SESSION['user_id'] ?? null,
                            'username' => $_SESSION['user_username'] ?? 'User',
                            'email' => $_SESSION['user_email'] ?? '',
                            'first_name' => $_SESSION['user_first_name'] ?? null,
                            'last_name' => $_SESSION['user_last_name'] ?? null,
                            'avatar' => $_SESSION['user_avatar'] ?? null
                        ];
                    }
                    ?>

                    <?php if (!$isLoggedIn): ?>
                        <!-- Language Selector (visible only when not logged in) -->
                        <?php renderLanguageDropdown('regular'); ?>
                    <?php endif; ?>

                    <?php if (!$isLoggedIn): ?>
                        <!-- Login / Register Links (when not logged in) -->
                        <div class="flex items-center space-x-2">
                            <a href="<?= htmlspecialchars(localized_path('/login', $currentLang)) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 text-slate-300 hover:text-white font-medium transition-all h-[40px]">
                                Login
                            </a>
                            <a href="<?= htmlspecialchars(localized_path('/register', $currentLang)) ?>" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-theme-primary hover:bg-theme-primary/90 text-white font-medium transition-all border border-transparent h-[40px]">
                                Register
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- User Profile Dropdown (when logged in) -->
                        <div class="relative">
                            <button
                                type="button"
                                id="user-menu-toggle"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:border-theme-primary/70 transition-all h-[40px]"
                                aria-expanded="false"
                            >
                                <?php if ($user && !empty($user->avatar)): ?>
                                    <img src="<?= htmlspecialchars($user->avatar) ?>" alt="<?= htmlspecialchars($user->username) ?>" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center">
                                        <span class="text-theme-primary font-semibold text-sm">
                                            <?= strtoupper(substr($user->username ?? 'U', 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <span class="hidden sm:inline text-slate-300 font-medium"><?= htmlspecialchars($user->username ?? 'User') ?></span>
                                <svg id="user-dropdown-arrow" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- User Dropdown Menu -->
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl overflow-visible" style="z-index: 60;">
                                <div class="p-2 overflow-visible">
                                    <div class="px-3 py-2 border-b border-slate-800/50">
                                        <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?></p>
                                        <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($user->email ?? '') ?></p>
                                    </div>
                                    <?php
                                    // Only show dashboard link if user has permission
                                    $currentUserForPermission = null;
                                    try {
                                        $currentUserForPermission = isset($_SESSION['user_id']) ? User::find($_SESSION['user_id']) : null;
                                    } catch (Exception $e) {
                                        // User not found or error, continue without dashboard link
                                    }
                                    if ($currentUserForPermission && ($currentUserForPermission->hasPermission('system.dashboard') || $currentUserForPermission->isSuperAdmin())):
                                    ?>
                                        <a href="<?= htmlspecialchars(localized_path('/dashboard', $currentLang)) ?>" class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/60 rounded-lg transition-colors group">
                                            <ion-icon name="grid-outline" class="w-5 h-5 text-slate-400 group-hover:text-theme-primary flex-shrink-0"></ion-icon>
                                            <span class="text-slate-300 group-hover:text-white font-medium">Dashboard</span>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Language Selector (integrated in user menu) -->
                                    <div class="border-t border-slate-800/50 my-2"></div>
                                    <div class="relative group/language">
                                        <button type="button" id="regular-user-menu-language-toggle" class="w-full flex items-center justify-between gap-3 px-3 py-2 hover:bg-slate-800/60 rounded-lg transition-colors group">
                                            <div class="flex items-center gap-3">
                                                <ion-icon name="globe-outline" class="w-5 h-5 text-slate-400 group-hover:text-theme-primary flex-shrink-0"></ion-icon>
                                                <span class="text-slate-300 group-hover:text-white font-medium">Language</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="fi fi-<?= $currentFlagCode ?> text-base"></span>
                                                <svg id="regular-user-menu-language-arrow" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </button>
                                        
                                        <!-- Language Dropdown (appears on the side) -->
                                        <div id="regular-user-menu-language-dropdown" class="hidden fixed w-72 sm:w-80 bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl" style="z-index: 70; max-height: 80vh; overflow-y: auto;">
                                            <div class="p-2 border-b border-slate-800/50">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Balkan</div>
                                                <?= langLink('sr', 'rs', 'Srpski', $currentLang) ?>
                                                <?= langLink('hr', 'hr', 'Hrvatski', $currentLang) ?>
                                                <?= langLink('bg', 'bg', 'Български', $currentLang) ?>
                                                <?= langLink('ro', 'ro', 'Română', $currentLang) ?>
                                                <?= langLink('sl', 'si', 'Slovenščina', $currentLang) ?>
                                                <?= langLink('el', 'gr', 'Ελληνικά', $currentLang) ?>
                                            </div>
                                            <div class="p-2 border-b border-slate-800/50">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Western Europe</div>
                                                <?= langLink('en', 'gb', 'English', $currentLang) ?>
                                                <?= langLink('de', 'de', 'Deutsch', $currentLang) ?>
                                                <?= langLink('fr', 'fr', 'Français', $currentLang) ?>
                                                <?= langLink('es', 'es', 'Español', $currentLang) ?>
                                                <?= langLink('it', 'it', 'Italiano', $currentLang) ?>
                                                <?= langLink('pt', 'pt', 'Português', $currentLang) ?>
                                                <?= langLink('nl', 'nl', 'Nederlands', $currentLang) ?>
                                            </div>
                                            <div class="p-2 border-b border-slate-800/50">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Eastern Europe</div>
                                                <?= langLink('pl', 'pl', 'Polski', $currentLang) ?>
                                                <?= langLink('ru', 'ru', 'Русский', $currentLang) ?>
                                                <?= langLink('uk', 'ua', 'Українська', $currentLang) ?>
                                                <?= langLink('cs', 'cz', 'Čeština', $currentLang) ?>
                                                <?= langLink('sk', 'sk', 'Slovenčina', $currentLang) ?>
                                                <?= langLink('hu', 'hu', 'Magyar', $currentLang) ?>
                                            </div>
                                            <div class="p-2 border-b border-slate-800/50">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Northern Europe</div>
                                                <?= langLink('sv', 'se', 'Svenska', $currentLang) ?>
                                                <?= langLink('da', 'dk', 'Dansk', $currentLang) ?>
                                                <?= langLink('no', 'no', 'Norsk', $currentLang) ?>
                                                <?= langLink('fi', 'fi', 'Suomi', $currentLang) ?>
                                            </div>
                                            <div class="p-2 border-b border-slate-800/50">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Baltic</div>
                                                <?= langLink('lt', 'lt', 'Lietuvių', $currentLang) ?>
                                                <?= langLink('et', 'ee', 'Eesti', $currentLang) ?>
                                                <?= langLink('lv', 'lv', 'Latviešu', $currentLang) ?>
                                            </div>
                                            <div class="p-2">
                                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold px-3 py-2">Asia & Other</div>
                                                <?= langLink('zh', 'cn', '中文', $currentLang) ?>
                                                <?= langLink('ja', 'jp', '日本語', $currentLang) ?>
                                                <?= langLink('ko', 'kr', '한국어', $currentLang) ?>
                                                <?= langLink('tr', 'tr', 'Türkçe', $currentLang) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-slate-800/50 my-2"></div>
                                    <form method="POST" action="<?= htmlspecialchars(localized_path('/logout', $currentLang)) ?>" class="p-0 m-0">
                                        <?php
                                        // Generate CSRF token
                                        if (class_exists('CSRF')) {
                                            echo CSRF::field();
                                        }
                                        ?>
                                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 hover:bg-red-900/20 rounded-lg transition-colors group text-left">
                                            <ion-icon name="log-out-outline" class="w-5 h-5 text-slate-400 group-hover:text-red-400 flex-shrink-0"></ion-icon>
                                            <span class="text-slate-300 group-hover:text-red-400 font-medium">Logout</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php if (!$isDashboard): ?>
        <!-- Mobile Menu (hidden by default) - Only for non-dashboard pages -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-800/50 py-4">
            <div class="space-y-2">
                <?php
                // Get current language ID for filtering
                $currentLanguageId = null;
                if (class_exists('Language')) {
                    $currentLanguage = Language::findByCode($currentLang);
                    if ($currentLanguage) {
                        $currentLanguageId = $currentLanguage->id;
                    }
                }
                
                // Get navigation menus for header position filtered by language
                if (class_exists('NavigationMenu')) {
                    $headerMenus = NavigationMenu::getByPosition('header', $currentLanguageId);
                    foreach ($headerMenus as $menu) {
                        $menuItems = $menu->getMenuItems();
                        foreach ($menuItems as $menuPage) {
                            $url = localized_path($menuPage->route, $currentLang);
                            $title = htmlspecialchars($menuPage->title ?? '');
                            echo '<a href="' . $url . '" class="block px-4 py-3 text-slate-300 hover:text-theme-primary hover:bg-slate-800/40 rounded-lg transition-colors">' . $title . '</a>';
                        }
                    }
                } elseif (class_exists('Page')) {
                    // Fallback to old behavior if NavigationMenu doesn't exist yet
                    $menuItems = Page::getMenuItems();
                    foreach ($menuItems as $menuPage) {
                        $url = localized_path($menuPage->route, $currentLang);
                        $title = htmlspecialchars($menuPage->title ?? '');
                        echo '<a href="' . $url . '" class="block px-4 py-3 text-slate-300 hover:text-theme-primary hover:bg-slate-800/40 rounded-lg transition-colors">' . $title . '</a>';
                    }
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
</header>
