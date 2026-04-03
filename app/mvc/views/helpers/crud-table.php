<?php
/**
 * CRUD Table Helper Functions
 * 
 * This file contains helper functions for rendering CRUD tables with filters, search, and pagination.
 * All functions use function_exists() checks to prevent redeclaration errors when included multiple times.
 * 
 * @phpstan-ignore-file
 * This file uses dynamic array access and mixed types which PHPStan may flag, but are intentional.
 */
/**
 * Get flag emoji from country code
 * Helper function for generating flag emojis
 * Defined at top level to ensure it's available even if file is included multiple times
 */
if (!function_exists('getFlagEmoji')) {
    function getFlagEmoji($countryCode) {
        $countryCode = strtoupper($countryCode);
        if (strlen($countryCode) !== 2) return '';
        
        $firstChar = ord($countryCode[0]) - 65; // A=0, B=1, etc.
        $secondChar = ord($countryCode[1]) - 65;
        
        if ($firstChar < 0 || $firstChar > 25 || $secondChar < 0 || $secondChar > 25) {
            return '';
        }
        
        // Regional Indicator Symbol Letter A starts at U+1F1E6
        $firstSymbol = 0x1F1E6 + $firstChar;
        $secondSymbol = 0x1F1E6 + $secondChar;
        
        // Try multiple methods to generate emoji
        $emoji = '';
        
        // Method 1: mb_chr (PHP 7.2+)
        if (function_exists('mb_chr')) {
            $emoji = mb_chr($firstSymbol, 'UTF-8') . mb_chr($secondSymbol, 'UTF-8');
            if (!empty($emoji) && strlen($emoji) > 0) {
                return $emoji;
            }
        }
        
        // Method 2: json_decode with unicode escape
        $emoji = json_decode('"' . sprintf('\\u%04X\\u%04X', $firstSymbol, $secondSymbol) . '"');
        if (!empty($emoji)) {
            return $emoji;
        }
        
        // Method 3: html_entity_decode
        $emoji = html_entity_decode('&#x' . dechex($firstSymbol) . ';&#x' . dechex($secondSymbol) . ';', ENT_QUOTES, 'UTF-8');
        if (!empty($emoji)) {
            return $emoji;
        }
        
        return '';
    }
}

// Note: All functions in this file use function_exists() checks to prevent redeclaration errors
// This allows the file to be safely included multiple times (e.g., in compiled templates via eval())
// Helper functions like getFlagEmoji() are defined at the top and will always be available
// Main functions below will only be defined if they don't already exist

/**
 * Generate filters automatically based on columns
 * 
 * @param array $columns Column definitions
 * @param string $app App name for context
 * @return array Generated filters configuration
 */
if (!function_exists('generateFiltersFromColumns')) {
function generateFiltersFromColumns(array $columns, string $app): array {
    $filters = [];
    
    foreach ($columns as $column) {
        $key = $column['key'] ?? '';
        if (empty($key)) continue;
        
        // Author/User filters
        if (in_array($key, ['author', 'author_id', 'user', 'user_id'])) {
            $filters['author_id'] = [
                'type' => 'user-select',
                'label' => 'Author',
                'placeholder' => 'All Authors',
                'options' => getAuthorsForFilter()
            ];
        }
        
        // Category filters
        if (in_array($key, ['category', 'category_id', 'blog_category_id'])) {
            $filters['category_id'] = [
                'type' => 'select',
                'label' => 'Category',
                'placeholder' => 'All Cat...',
                'options' => getCategoriesForFilter()
            ];
        }
        
        // Status filters
        if ($key === 'status') {
            $filters['status'] = [
                'type' => 'select',
                'label' => 'Status',
                'placeholder' => 'All Statuses',
                'options' => getStatusOptionsForFilter($app)
            ];
        }
        
        // Continent filters (detect both 'continent' and 'continent_id' columns)
        if (in_array($key, ['continent', 'continent_id'])) {
            // Use continent_id as filter key for consistency
            if (!isset($filters['continent_id'])) {
                $filters['continent_id'] = [
                    'type' => 'select',
                    'label' => 'Continent',
                    'placeholder' => 'All Continents',
                    'options' => getContinentsForFilter()
                ];
            }
        }
        
        // Region filters (detect both 'region' and 'region_id' columns)
        if (in_array($key, ['region', 'region_id'])) {
            // Use region_id as filter key for consistency
            if (!isset($filters['region_id'])) {
                $filters['region_id'] = [
                    'type' => 'select',
                    'label' => 'Region',
                    'placeholder' => 'All Regions',
                    'options' => getRegionsForFilter()
                ];
            }
        }
        
        // Status-like filters (is_active, is_default, etc.)
        if (in_array($key, ['is_active', 'is_default', 'is_site_language'])) {
            // Generate boolean filter for active/inactive
            if ($key === 'is_active' && !isset($filters['is_active'])) {
                $filters['is_active'] = [
                    'type' => 'select',
                    'label' => 'Status',
                    'placeholder' => 'All Statuses',
                    'options' => [
                        '1' => 'Active',
                        '0' => 'Inactive'
                    ]
                ];
            }
        }
        
        // Date range filters - DISABLED for now
        // if (in_array($key, ['created_at', 'updated_at', 'published_at'])) {
        //     // Only create one date range filter (use created_at as primary)
        //     if (!isset($filters['created_at']) && $key === 'created_at') {
        //         $filters['created_at'] = [
        //             'type' => 'date-range',
        //             'label' => 'Date',
        //             'placeholder' => 'Date Range'
        //         ];
        //     }
        // }
    }
    
    return $filters;
}
}

/**
 * Get authors for filter dropdown
 */
if (!function_exists('getAuthorsForFilter')) {
function getAuthorsForFilter(): array {
    $authors = [];
    $authorsData = [];
    
    if (class_exists('User')) {
        $users = User::all();
        foreach ($users as $user) {
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            
            // Format: "A. Popovic" (first letter of first name + last name)
            $shortName = '';
            if (!empty($firstName) && !empty($lastName)) {
                $firstInitial = strtoupper(substr($firstName, 0, 1));
                $shortName = $firstInitial . '. ' . $lastName;
            } elseif (!empty($fullName)) {
                $shortName = $fullName;
            } else {
                $shortName = $user->username ?? $user->email ?? 'Unknown';
            }
            
            // Get avatar or generate initial
            $avatar = $user->avatar ?? null;
            $initial = '';
            if (empty($avatar)) {
                // Use first letter of first name, or username, or email
                if (!empty($firstName)) {
                    $initial = strtoupper(substr($firstName, 0, 1));
                } elseif (!empty($user->username)) {
                    $initial = strtoupper(substr($user->username, 0, 1));
                } elseif (!empty($user->email)) {
                    $initial = strtoupper(substr($user->email, 0, 1));
                } else {
                    $initial = 'U';
                }
            }
            
            $authors[$user->id] = [
                'label' => $shortName,
                'avatar' => $avatar,
                'initial' => $initial,
                'full_name' => $fullName ?: ($user->username ?? $user->email ?? 'Unknown'),
                'username' => $user->username ?? ''
            ];
            
            $authorsData[$user->id] = [
                'label' => $shortName,
                'avatar' => $avatar,
                'initial' => $initial,
                'full_name' => $fullName ?: ($user->username ?? $user->email ?? 'Unknown'),
                'username' => $user->username ?? ''
            ];
        }
    }
    
    // Store authors data globally for JavaScript access
    $GLOBALS['_authorsFilterData'] = $authorsData;
    
    return $authors;
}
}

/**
 * Get categories for filter dropdown
 */
if (!function_exists('getCategoriesForFilter')) {
function getCategoriesForFilter(): array {
    $categories = [];
    if (class_exists('BlogCategory')) {
        $allCategories = BlogCategory::all();
        foreach ($allCategories as $category) {
            $categories[$category->id] = $category->name;
        }
    }
    return $categories;
}
}

/**
 * Get status options for filter dropdown
 */
if (!function_exists('getStatusOptionsForFilter')) {
function getStatusOptionsForFilter(string $app): array {
    // App-specific status options
    if ($app === 'users') {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'banned' => 'Banned'
        ];
    } elseif (in_array($app, ['blog-posts', 'blog'])) {
        return [
            'published' => 'Published',
            'draft' => 'Draft',
            'archived' => 'Archived'
        ];
    }
    
    // Default status options
    return [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived'
    ];
}
}

/**
 * Get continents for filter dropdown with code and icon data
 */
if (!function_exists('getContinentsForFilter')) {
function getContinentsForFilter(): array {
    $continents = [];
    if (class_exists('Continent')) {
        // Map continent codes to emoji icons
        $continentIcons = [
            'eu' => '🌍',  // Europe
            'as' => '🌏',  // Asia
            'na' => '🌎',  // North America
            'sa' => '🌎',  // South America
            'af' => '🦁',  // Africa
            'oc' => '🏝️',  // Oceania
            'an' => '❄️'   // Antarctica
        ];
        
        $allContinents = Continent::all();
        foreach ($allContinents as $continent) {
            $code = strtolower($continent->code ?? '');
            $icon = $continentIcons[$code] ?? '🗺️';
            
            // Return array with label and data attributes
            $continents[$continent->id] = [
                'label' => $continent->name,
                'code' => $code,
                'icon' => $icon
            ];
        }
    }
    return $continents;
}
}

/**
 * Get regions for filter dropdown with code data
 */
if (!function_exists('getRegionsForFilter')) {
function getRegionsForFilter(): array {
    $regions = [];
    if (class_exists('Region')) {
        $allRegions = Region::all();
        foreach ($allRegions as $region) {
            $code = strtoupper($region->code ?? '');
            
            // Return array with label and code
            $regions[$region->id] = [
                'label' => $region->name,
                'code' => $code
            ];
        }
    }
    return $regions;
}
}

if (!function_exists('renderCrudTable')) {
/**
 * Universal CRUD Table Helper
 * 
 * Renders a configurable CRUD table with toolbar (search, sort, language filter, pagination)
 * 
 * @param array<string, mixed> $config Configuration array with:
 *   - 'app' => string (required) - App name (e.g., 'blog-posts', 'blog-categories')
 *   - 'columns' => array<int, array<string, mixed>> (required) - Column definitions
 *   - 'createUrl' => string (optional) - URL for create button
 *   - 'editUrl' => string (optional) - URL pattern for edit (use {id} placeholder)
 *   - 'deleteUrl' => string (optional) - URL pattern for delete (use {id} placeholder)
 *   - 'title' => string (optional) - Page title (reserved for future use)
 *   - 'description' => string (optional) - Page description (reserved for future use)
 *   - 'enableLanguageFilter' => bool (optional, default: true) - Show language filter
 *   - 'enableSearch' => bool (optional, default: true) - Show search
 *   - 'enableSort' => bool (optional, default: true) - Enable column sorting
 *   - 'defaultSort' => string (optional, default: 'id') - Default sort column
 *   - 'defaultOrder' => string (optional, default: 'desc') - Default sort order
 *   - 'perPage' => int (optional, default: 50) - Items per page
 *   - 'filters' => array<string, array<string, mixed>> (optional) - Explicit filters configuration (overrides auto-generation)
 *   - 'customActions' => array<string, mixed>|null (optional) - Custom actions column configuration
 * 
 * @return void
 */
function renderCrudTable(array $config): void {
    /** @var \Router|null $router */
    global $router;
    $lang = isset($router) && is_object($router) && property_exists($router, 'lang') ? ($router->lang ?? 'sr') : 'sr';
    
    /** @var string $app */
    $app = (string)($config['app'] ?? '');
    /** @var array<int, array<string, mixed>> $columns */
    $columns = is_array($config['columns'] ?? null) ? $config['columns'] : [];
    /** @var string $createUrl */
    $createUrl = (string)($config['createUrl'] ?? '');
    /** @var string $editUrl */
    $editUrl = (string)($config['editUrl'] ?? '');
    /** @var string $deleteUrl */
    $deleteUrl = (string)($config['deleteUrl'] ?? '');
    // Note: $title and $description from config are reserved for future use but currently not displayed
    /** @var bool $enableLanguageFilter */
    $enableLanguageFilter = $config['enableLanguageFilter'] ?? true;
    /** @var bool $enableSearch */
    $enableSearch = $config['enableSearch'] ?? true;
    /** @var bool $enableSort */
    $enableSort = $config['enableSort'] ?? true;
    /** @var string $defaultSort */
    $defaultSort = (string)($config['defaultSort'] ?? 'id');
    /** @var string $defaultOrder */
    $defaultOrder = (string)($config['defaultOrder'] ?? 'desc');
    /** @var int $perPage */
    $perPage = (int)($config['perPage'] ?? 50);
    /** @var array<string, mixed>|null $customActions */
    $customActions = $config['customActions'] ?? null; // Custom render function for actions column
    /** @var array<string, mixed> $explicitFilters */
    $explicitFilters = $config['filters'] ?? []; // Explicitly defined filters (override)
    
    // Auto-generate filters based on columns if not explicitly defined
    $filters = !empty($explicitFilters) ? $explicitFilters : generateFiltersFromColumns($columns, $app);
    
    // Get languages for filter
    $languages = [];
    $languagesData = [];
    if ($enableLanguageFilter && class_exists('Language')) {
        $flagCodes = [
            'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
            'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
            'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
            'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
            'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
            'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr'
        ];
        
        // getFlagEmoji() is now defined at the top of the file
        $langs = Language::getActive();
        $languages[''] = 'All';
        $defaultLanguageId = null; // Will be set if current language is found
        
        foreach ($langs as $langItem) {
            $code = strtolower($langItem->code ?? '');
            $flagCode = $flagCodes[$code] ?? 'xx';
            // getFlagEmoji() should be defined at the top of this file
            // If it's not available, use empty string as fallback
            if (function_exists('getFlagEmoji')) {
                $flagEmoji = getFlagEmoji($flagCode);
            } else {
                // Fallback: generate flag emoji inline if function not available
                $flagCodeUpper = strtoupper($flagCode);
                if (strlen($flagCodeUpper) === 2) {
                    $firstChar = ord($flagCodeUpper[0]) - 65;
                    $secondChar = ord($flagCodeUpper[1]) - 65;
                    if ($firstChar >= 0 && $firstChar <= 25 && $secondChar >= 0 && $secondChar <= 25) {
                        $firstSymbol = 0x1F1E6 + $firstChar;
                        $secondSymbol = 0x1F1E6 + $secondChar;
                        if (function_exists('mb_chr')) {
                            $flagEmoji = mb_chr($firstSymbol, 'UTF-8') . mb_chr($secondSymbol, 'UTF-8');
                        } else {
                            $flagEmoji = json_decode('"' . sprintf('\\u%04X\\u%04X', $firstSymbol, $secondSymbol) . '"') ?: '';
                        }
                    } else {
                        $flagEmoji = '';
                    }
                } else {
                    $flagEmoji = '';
                }
            }
            $langCode = strtoupper($langItem->code ?? '');
            
            // Check if this is the current system language
            if (strtolower($code) === strtolower($lang)) {
                $defaultLanguageId = $langItem->id;
            }
            
            // If emoji generation failed, try alternative method
            if (empty($flagEmoji) && $flagCode !== 'xx') {
                // Alternative: use JSON encoding to create emoji
                $countryCode = strtoupper($flagCode);
                if (strlen($countryCode) === 2) {
                    $firstChar = ord($countryCode[0]) - 65;
                    $secondChar = ord($countryCode[1]) - 65;
                    if ($firstChar >= 0 && $firstChar <= 25 && $secondChar >= 0 && $secondChar <= 25) {
                        $firstSymbol = 0x1F1E6 + $firstChar;
                        $secondSymbol = 0x1F1E6 + $secondChar;
                        // Use json_decode with unicode escape
                        $flagEmoji = json_decode('"' . sprintf('\\u%04X\\u%04X', $firstSymbol, $secondSymbol) . '"');
                        
                        // If still empty, try html_entity_decode
                        if (empty($flagEmoji)) {
                            $flagEmoji = html_entity_decode('&#x' . dechex($firstSymbol) . ';&#x' . dechex($secondSymbol) . ';', ENT_QUOTES, 'UTF-8');
                        }
                    }
                }
            }
            
            $languages[$langItem->id] = $langItem->name . ' (' . $langItem->native_name . ')';
            $languagesData[$langItem->id] = [
                'code' => $langItem->code,
                'name' => $langItem->name,
                'native_name' => $langItem->native_name,
                'flag_code' => $flagCode,
                'flag_emoji' => $flagEmoji ?: '',
                'lang_code' => $langCode
            ];
        }
    }
    
    // Generate unique ID for this table instance
    $tableId = 'crud-table-' . str_replace(['-', '_'], '', $app);
    // Filter out render functions from columns for JSON (they can't be serialized)
    // Render functions will be handled server-side or through custom JS
    $columnsForJson = array_map(function($col) {
        $colCopy = $col;
        if (isset($colCopy['render'])) {
            $colCopy['hasRender'] = true;
            unset($colCopy['render']); // Remove function from JSON
        }
        return $colCopy;
    }, $columns);
    
    // Use direct API route instead of delegating through web route
    $apiUrl = "/{$lang}/api/dashboard/{$app}";
    
    // Prepare customActions for JSON (only keep render function name as string)
    $customActionsForJson = null;
    if ($customActions && isset($customActions['render'])) {
        $customActionsForJson = [
            'render' => $customActions['render'] // Keep as string (function name)
        ];
    }
    
    // Prepare filters for JSON (remove callbacks, keep only data)
    $filtersForJson = [];
    if (!empty($filters)) {
        foreach ($filters as $filterKey => $filterConfig) {
            $filtersForJson[$filterKey] = [
                'type' => $filterConfig['type'] ?? 'select',
                'label' => $filterConfig['label'] ?? ucfirst(str_replace('_', ' ', $filterKey)),
                'placeholder' => $filterConfig['placeholder'] ?? null,
                'options' => $filterConfig['options'] ?? []
            ];
        }
    }
    
    $configJson = json_encode([
        'app' => $app,
        'apiUrl' => $apiUrl,
        'columns' => $columnsForJson,
        'editUrl' => $editUrl,
        'deleteUrl' => $deleteUrl,
        'customActions' => $customActionsForJson,
        'enableSort' => $enableSort,
        'defaultSort' => $defaultSort,
        'defaultOrder' => $defaultOrder,
        'perPage' => $perPage,
        'languagesData' => $languagesData,
        'filters' => $filtersForJson
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    
    // Note: originalColumns are used in JavaScript below, created inline from $columns
    ?>
    
    <div id="<?= htmlspecialchars($tableId) ?>-container">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <!-- Toolbar -->
                <div class="flex-1 flex flex-wrap items-center gap-4">
                <?php if ($enableSearch): ?>
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <input type="text" 
                                   id="<?= htmlspecialchars($tableId) ?>-search" 
                                   placeholder="Search..." 
                                   class="w-full px-4 py-2 pl-10 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary">
                            <ion-icon name="search-outline" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 text-xl"></ion-icon>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($enableLanguageFilter): ?>
                    <!-- Language Filter (Custom Dropdown) -->
                    <div class="w-auto min-w-[120px] relative">
                        <button type="button"
                                id="<?= htmlspecialchars($tableId) ?>-language-toggle"
                                class="w-full min-w-[120px] flex items-center justify-between gap-2 px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white hover:border-theme-primary/70 focus:border-theme-primary focus:ring-1 focus:ring-theme-primary transition-all language-filter-toggle">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <?php 
                                $defaultFlagCode = 'xx';
                                $defaultText = 'All';
                                if ($defaultLanguageId && isset($languagesData[$defaultLanguageId])) {
                                    $defaultFlagCode = $languagesData[$defaultLanguageId]['flag_code'] ?? 'xx';
                                    $defaultText = strtoupper($languagesData[$defaultLanguageId]['code'] ?? '');
                                }
                                ?>
                                <span id="<?= htmlspecialchars($tableId) ?>-language-flag-display" class="fi fi-<?= htmlspecialchars($defaultFlagCode) ?>" style="font-size: 1rem; flex-shrink: 0;"></span>
                                <span id="<?= htmlspecialchars($tableId) ?>-language-text" class="text-sm font-medium truncate"><?= htmlspecialchars($defaultText) ?></span>
                            </div>
                            <ion-icon name="chevron-down-outline" 
                                     id="<?= htmlspecialchars($tableId) ?>-language-arrow"
                                     class="text-slate-400 text-lg transition-transform flex-shrink-0"></ion-icon>
                        </button>
                        
                        <!-- Language Dropdown -->
                        <div id="<?= htmlspecialchars($tableId) ?>-language-dropdown" 
                             style="display: none;"
                             class="absolute left-0 mt-2 w-64 max-h-96 overflow-y-auto bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl z-50">
                            <div class="p-2">
                                <!-- All Languages Option -->
                                <button type="button"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800/60 transition-colors language-filter-option"
                                        data-value=""
                                        data-code=""
                                        data-flag-code="">
                                    <span class="text-sm font-medium text-slate-300">All</span>
                                </button>
                                
                                <!-- Language Options -->
                                <?php foreach ($languagesData as $langId => $langData): 
                                    $flagCode = $langData['flag_code'] ?? 'xx';
                                    $langCode = $langData['lang_code'] ?? '';
                                ?>
                                    <button type="button"
                                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800/60 transition-colors language-filter-option"
                                            data-value="<?= htmlspecialchars($langId) ?>"
                                            data-code="<?= htmlspecialchars($langData['code']) ?>"
                                            data-flag-code="<?= htmlspecialchars($flagCode) ?>">
                                        <span class="fi fi-<?= htmlspecialchars($flagCode) ?>" style="font-size: 1rem;"></span>
                                        <span class="text-sm font-medium text-slate-300"><?= htmlspecialchars($langCode) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden input to store selected value -->
                        <input type="hidden" 
                               id="<?= htmlspecialchars($tableId) ?>-language-value" 
                               value="<?= $defaultLanguageId ? htmlspecialchars($defaultLanguageId) : '' ?>"
                               data-default-language-id="<?= $defaultLanguageId ? htmlspecialchars($defaultLanguageId) : '' ?>">
                    </div>
                <?php endif; ?>
                
                <?php 
                // Render dynamic filters
                if (!empty($filters)): 
                    foreach ($filters as $filterKey => $filterConfig): 
                        $filterType = $filterConfig['type'] ?? 'select';
                        $filterLabel = $filterConfig['label'] ?? ucfirst(str_replace('_', ' ', $filterKey));
                        $filterOptions = $filterConfig['options'] ?? [];
                        $filterPlaceholder = $filterConfig['placeholder'] ?? "All {$filterLabel}";
                ?>
                    <!-- <?= htmlspecialchars($filterLabel) ?> Filter -->
                    <?php if ($filterType === 'user-select'): ?>
                        <!-- User/Author Filter (Custom Dropdown) -->
                        <div class="w-auto min-w-[180px] relative">
                            <button type="button"
                                    id="<?= htmlspecialchars($tableId) ?>-<?= htmlspecialchars($filterKey) ?>-toggle"
                                    class="w-full min-w-[180px] flex items-center justify-between gap-2 px-3 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white hover:border-theme-primary/70 focus:border-theme-primary focus:ring-1 focus:ring-theme-primary transition-all user-filter-toggle">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div id="<?= htmlspecialchars($tableId) ?>-<?= htmlspecialchars($filterKey) ?>-avatar-display" class="w-6 h-6 rounded-full bg-slate-700/50 border border-slate-600 flex items-center justify-center flex-shrink-0">
                                        <span class="text-theme-primary font-semibold text-xs"></span>
                                    </div>
                                    <span id="<?= htmlspecialchars($tableId) ?>-<?= htmlspecialchars($filterKey) ?>-text" class="text-sm font-medium truncate"><?= htmlspecialchars($filterPlaceholder) ?></span>
                                </div>
                                <ion-icon name="chevron-down-outline" 
                                         id="<?= htmlspecialchars($tableId) ?>-<?= htmlspecialchars($filterKey) ?>-arrow"
                                         class="text-slate-400 text-lg transition-transform flex-shrink-0"></ion-icon>
                            </button>
                            
                            <!-- User Dropdown -->
                            <div id="<?= htmlspecialchars($tableId) ?>-<?= htmlspecialchars($filterKey) ?>-dropdown" 
                                 style="display: none;"
                                 class="absolute left-0 mt-2 w-64 max-h-96 overflow-y-auto bg-slate-900/95 backdrop-blur-xl border border-slate-700/50 rounded-xl shadow-2xl z-50">
                                <div class="p-2">
                                    <!-- All Authors Option -->
                                    <button type="button"
                                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800/60 transition-colors user-filter-option"
                                            data-value=""
                                            data-label=""
                                            data-avatar=""
                                            data-initial="">
                                        <span class="text-sm font-medium text-slate-300"><?= htmlspecialchars($filterPlaceholder) ?></span>
                                    </button>
                                    
                                    <!-- User Options -->
                                    <?php foreach ($filterOptions as $userId => $userData): 
                                        if (is_array($userData)):
                                            $userAvatar = $userData['avatar'] ?? null;
                                            $userInitial = $userData['initial'] ?? 'U';
                                            $userLabel = $userData['label'] ?? 'Unknown';
                                    ?>
                                        <button type="button"
                                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-800/60 transition-colors user-filter-option"
                                                data-value="<?= htmlspecialchars($userId) ?>"
                                                data-label="<?= htmlspecialchars($userLabel) ?>"
                                                data-avatar="<?= htmlspecialchars($userAvatar ?? '') ?>"
                                                data-initial="<?= htmlspecialchars($userInitial) ?>">
                                            <?php if ($userAvatar): ?>
                                                <img src="<?= htmlspecialchars($userAvatar) ?>" 
                                                     alt="<?= htmlspecialchars($userLabel) ?>" 
                                                     class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                            <?php else: ?>
                                                <div class="w-6 h-6 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-theme-primary font-semibold text-xs"><?= htmlspecialchars($userInitial) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <span class="text-sm font-medium text-slate-300 truncate"><?= htmlspecialchars($userLabel) ?></span>
                                        </button>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Hidden input to store selected value -->
                            <input type="hidden" 
                                   id="<?= htmlspecialchars($tableId) ?>-filter-<?= htmlspecialchars($filterKey) ?>" 
                                   value="">
                        </div>
                    <?php elseif ($filterType === 'select'): ?>
                        <div class="min-w-[200px]">
                            <select id="<?= htmlspecialchars($tableId) ?>-filter-<?= htmlspecialchars($filterKey) ?>" 
                                    data-placeholder="<?= htmlspecialchars($filterPlaceholder) ?>"
                                    class="w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary filter-select-with-icons">
                                <option value=""><?= htmlspecialchars($filterPlaceholder) ?></option>
                                <?php foreach ($filterOptions as $optionValue => $optionLabel): ?>
                                    <?php if (is_array($optionLabel)): ?>
                                        <option value="<?= htmlspecialchars($optionValue) ?>" <?php 
                                            foreach ($optionLabel as $dataKey => $dataValue): 
                                                if ($dataKey !== 'label') {
                                                    echo 'data-' . htmlspecialchars($dataKey) . '="' . htmlspecialchars($dataValue) . '" '; 
                                                }
                                            endforeach; 
                                        ?>>
                                            <?php 
                                            // Build display text with icon and code if available
                                            $displayText = $optionLabel['label'] ?? $optionValue;
                                            if (isset($optionLabel['icon'])) {
                                                $displayText = $optionLabel['icon'] . ' ' . $displayText;
                                            }
                                            if (isset($optionLabel['code']) && !empty($optionLabel['code'])) {
                                                $displayText .= ' (' . strtoupper($optionLabel['code']) . ')';
                                            }
                                            echo htmlspecialchars($displayText);
                                            ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?= htmlspecialchars($optionValue) ?>">
                                            <?= htmlspecialchars($optionLabel) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($filterType === 'date-range'): ?>
                        <!-- Date Range Filter - Compact Design with Labels -->
                        <div class="w-auto min-w-[300px]">
                            <div class="flex items-center gap-2 bg-slate-900/50 border border-slate-700 rounded-lg px-3 py-2">
                                <ion-icon name="calendar-outline" class="text-slate-400 text-lg flex-shrink-0"></ion-icon>
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                        <label class="text-xs text-slate-400 whitespace-nowrap flex-shrink-0">From:</label>
                                        <input type="date" 
                                               id="<?= htmlspecialchars($tableId) ?>-filter-<?= htmlspecialchars($filterKey) ?>-from" 
                                               class="flex-1 min-w-0 px-2 py-1 text-sm bg-slate-800/50 border border-slate-600 rounded text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary focus:outline-none"
                                               title="From Date">
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-1 min-w-0">
                                        <label class="text-xs text-slate-400 whitespace-nowrap flex-shrink-0">To:</label>
                                        <input type="date" 
                                               id="<?= htmlspecialchars($tableId) ?>-filter-<?= htmlspecialchars($filterKey) ?>-to" 
                                               class="flex-1 min-w-0 px-2 py-1 text-sm bg-slate-800/50 border border-slate-600 rounded text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary focus:outline-none"
                                               title="To Date">
                                    </div>
                                </div>
                                <button type="button" 
                                        class="text-slate-400 hover:text-slate-300 transition-colors flex-shrink-0 p-1 date-range-clear-btn"
                                        data-table-id="<?= htmlspecialchars($tableId) ?>"
                                        data-filter-key="<?= htmlspecialchars($filterKey) ?>"
                                        title="Clear date range">
                                    <ion-icon name="close-circle-outline" class="text-lg"></ion-icon>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php 
                    endforeach; 
                endif; 
                ?>
                
                </div>
                
                <?php if (!empty($createUrl)): ?>
                    <a href="<?= htmlspecialchars($createUrl) ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                        <ion-icon name="add-outline"></ion-icon>
                        Create
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
            <div id="<?= htmlspecialchars($tableId) ?>-loading" class="p-8 text-center text-slate-400">
                <ion-icon name="hourglass-outline" class="text-4xl animate-spin mb-2"></ion-icon>
                <p>Loading...</p>
            </div>
            
            <div id="<?= htmlspecialchars($tableId) ?>-table-container" class="hidden">
                <div class="overflow-x-auto">
                    <table class="w-full" id="<?= htmlspecialchars($tableId) ?>-table">
                        <thead class="bg-slate-900/50 border-b border-slate-700/50">
                            <tr id="<?= htmlspecialchars($tableId) ?>-table-head">
                                <?php foreach ($columns as $column): 
                                    $columnKey = $column['key'] ?? '';
                                    $columnLabel = $column['label'] ?? $columnKey;
                                    $isSortable = ($enableSort && (!isset($column['sortable']) || $column['sortable'] !== false));
                                    $isCurrentSort = ($columnKey === $defaultSort);
                                    $sortIcon = '';
                                    if ($isSortable) {
                                        if ($isCurrentSort) {
                                            $sortIcon = $defaultOrder === 'asc' 
                                                ? '<ion-icon name="arrow-up-outline" class="ml-1 text-theme-primary"></ion-icon>'
                                                : '<ion-icon name="arrow-down-outline" class="ml-1 text-theme-primary"></ion-icon>';
                                        } else {
                                            $sortIcon = '<ion-icon name="swap-vertical-outline" class="ml-1 text-slate-500 opacity-50"></ion-icon>';
                                        }
                                    }
                                ?>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300 <?= $isSortable ? 'cursor-pointer hover:text-white transition-colors select-none' : '' ?>"
                                        <?php if ($isSortable): ?>
                                            data-sort-key="<?= htmlspecialchars($columnKey) ?>"
                                            data-current-sort="<?= $isCurrentSort ? 'true' : 'false' ?>"
                                            data-current-order="<?= $isCurrentSort ? htmlspecialchars($defaultOrder) : '' ?>"
                                        <?php endif; ?>>
                                        <div class="flex items-center">
                                            <span><?= htmlspecialchars($columnLabel) ?></span>
                                            <?php if ($isSortable): ?>
                                                <?= $sortIcon ?>
                                            <?php endif; ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                                <?php 
                                $hasActionsColumn = false;
                                foreach ($columns as $col) {
                                    if (($col['key'] ?? '') === '_actions') {
                                        $hasActionsColumn = true;
                                        break;
                                    }
                                }
                                if (!$hasActionsColumn && (!empty($editUrl) || !empty($deleteUrl))): ?>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-300">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50" id="<?= htmlspecialchars($tableId) ?>-table-body">
                            <!-- Data will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-700/50 flex items-center justify-between" id="<?= htmlspecialchars($tableId) ?>-pagination">
                    <!-- Pagination will be rendered here -->
                </div>
            </div>
            
            <div id="<?= htmlspecialchars($tableId) ?>-empty" class="hidden p-8 text-center text-slate-400">
                <ion-icon name="document-outline" class="text-4xl mb-2"></ion-icon>
                <p>No data found</p>
            </div>
        </div>
    </div>

    <style>
        /* Style for select filters with icons */
        .filter-select-with-icons option {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Ensure emoji icons are properly displayed */
        .filter-select-with-icons option::before {
            content: '';
        }
        
        /* Style for language filter select with flags */
        .language-filter-select {
            background-image: none;
            font-size: 0.875rem;
        }
        
        .language-filter-select option {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            /* Ensure emoji flags are displayed */
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        }
    </style>
    
    <script nonce="<?php echo function_exists('csp_nonce') ? csp_nonce() : ''; ?>">
    // Initialize custom language filter dropdown
    (function() {
        const tableId = '<?= htmlspecialchars($tableId) ?>';
        let languageDropdownInitialized = false;
        
        function initLanguageFilterDropdown() {
            // Prevent multiple initializations
            if (languageDropdownInitialized) return;
            
            const toggle = document.getElementById(tableId + '-language-toggle');
            const dropdown = document.getElementById(tableId + '-language-dropdown');
            const arrow = document.getElementById(tableId + '-language-arrow');
            const flagDisplay = document.getElementById(tableId + '-language-flag-display');
            const textDisplay = document.getElementById(tableId + '-language-text');
            const hiddenInput = document.getElementById(tableId + '-language-value');
            
            if (!toggle || !dropdown || !arrow) {
                // Elements not found yet, retry
                return false;
            }
            
            languageDropdownInitialized = true;
            
            const options = dropdown.querySelectorAll('.language-filter-option');
            
            function openDropdown() {
                dropdown.style.display = 'block';
                dropdown.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                toggle.setAttribute('aria-expanded', 'true');
            }
            
            function closeDropdown() {
                dropdown.style.display = 'none';
                dropdown.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                toggle.setAttribute('aria-expanded', 'false');
            }
            
            // Toggle dropdown
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const isVisible = dropdown.style.display !== 'none' && !dropdown.classList.contains('hidden');
                
                if (isVisible) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });
            
            // Close dropdown when clicking outside
            const closeOnOutsideClick = function(e) {
                if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
                    closeDropdown();
                }
            };
            document.addEventListener('click', closeOnOutsideClick);
            
            // Close dropdown on Escape key
            const closeOnEscape = function(e) {
                if (e.key === 'Escape' && dropdown.style.display !== 'none') {
                    closeDropdown();
                }
            };
            document.addEventListener('keydown', closeOnEscape);
            
            // Handle option selection
            options.forEach(function(option) {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const value = this.getAttribute('data-value') || '';
                    const code = this.getAttribute('data-code') || '';
                    const flagCode = this.getAttribute('data-flag-code') || '';
                    
                    // Update hidden input
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                    
                    // Update display
                    if (value === '') {
                        // All selected
                        if (flagDisplay) flagDisplay.className = 'fi fi-xx';
                        if (textDisplay) textDisplay.textContent = 'All';
                    } else {
                        // Language selected
                        if (flagDisplay) {
                            flagDisplay.className = 'fi fi-' + flagCode;
                        }
                        if (textDisplay) {
                            textDisplay.textContent = code.toUpperCase();
                        }
                    }
                    
                    // Close dropdown
                    closeDropdown();
                    
                    // Trigger change event for CrudTable
                    if (hiddenInput) {
                        const changeEvent = new Event('change', { bubbles: true });
                        hiddenInput.dispatchEvent(changeEvent);
                    }
                });
            });
            
            return true;
        }
        
        // Try to initialize with retries
        function tryInitLanguage(retries = 10) {
            if (initLanguageFilterDropdown()) {
                return; // Successfully initialized
            }
            
            if (retries > 0) {
                setTimeout(() => tryInitLanguage(retries - 1), 100);
            } else {
                console.warn('Failed to initialize language filter dropdown for table:', tableId);
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => tryInitLanguage(), 200);
            });
        } else {
            setTimeout(() => tryInitLanguage(), 200);
        }
    })();
    
    // Initialize custom user/author filter dropdowns
    (function() {
        const tableId = '<?= htmlspecialchars($tableId) ?>';
        const userDropdownsInitialized = new Set();
        
        function initUserFilterDropdown(filterKey) {
            const dropdownId = filterKey + '-dropdown';
            if (userDropdownsInitialized.has(dropdownId)) {
                return true; // Already initialized
            }
            
            const toggle = document.getElementById(tableId + '-' + filterKey + '-toggle');
            const dropdown = document.getElementById(tableId + '-' + filterKey + '-dropdown');
            const arrow = document.getElementById(tableId + '-' + filterKey + '-arrow');
            const avatarDisplay = document.getElementById(tableId + '-' + filterKey + '-avatar-display');
            const textDisplay = document.getElementById(tableId + '-' + filterKey + '-text');
            const hiddenInput = document.getElementById(tableId + '-filter-' + filterKey);
            
            if (!toggle || !dropdown || !arrow) {
                // Elements not found yet, retry
                return false;
            }
            
            userDropdownsInitialized.add(dropdownId);
            
            const options = dropdown.querySelectorAll('.user-filter-option');
            const placeholder = toggle.querySelector('span')?.textContent || 'All Authors';
            
            function openDropdown() {
                dropdown.style.display = 'block';
                dropdown.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                toggle.setAttribute('aria-expanded', 'true');
            }
            
            function closeDropdown() {
                dropdown.style.display = 'none';
                dropdown.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                toggle.setAttribute('aria-expanded', 'false');
            }
            
            // Toggle dropdown
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const isVisible = dropdown.style.display !== 'none' && !dropdown.classList.contains('hidden');
                
                if (isVisible) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });
            
            // Close dropdown when clicking outside
            const closeOnOutsideClick = function(e) {
                if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
                    closeDropdown();
                }
            };
            document.addEventListener('click', closeOnOutsideClick);
            
            // Close dropdown on Escape key
            const closeOnEscape = function(e) {
                if (e.key === 'Escape' && dropdown.style.display !== 'none') {
                    closeDropdown();
                }
            };
            document.addEventListener('keydown', closeOnEscape);
            
            // Handle option selection
            options.forEach(function(option) {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const value = this.getAttribute('data-value') || '';
                    const label = this.getAttribute('data-label') || '';
                    const avatar = this.getAttribute('data-avatar') || '';
                    const initial = this.getAttribute('data-initial') || '';
                    
                    // Update hidden input
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                    
                    // Update display
                    if (value === '') {
                        // All selected
                        if (avatarDisplay) {
                            avatarDisplay.innerHTML = '<span class="text-theme-primary font-semibold text-xs"></span>';
                        }
                        if (textDisplay) {
                            textDisplay.textContent = placeholder;
                        }
                    } else {
                        // User selected
                        if (avatarDisplay) {
                            if (avatar) {
                                avatarDisplay.innerHTML = '<img src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(label) + '" class="w-6 h-6 rounded-full object-cover">';
                            } else {
                                avatarDisplay.innerHTML = '<div class="w-6 h-6 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center"><span class="text-theme-primary font-semibold text-xs">' + escapeHtml(initial) + '</span></div>';
                            }
                        }
                        if (textDisplay) {
                            textDisplay.textContent = label;
                        }
                    }
                    
                    // Close dropdown
                    closeDropdown();
                    
                    // Trigger change event for CrudTable
                    if (hiddenInput) {
                        const changeEvent = new Event('change', { bubbles: true });
                        hiddenInput.dispatchEvent(changeEvent);
                    }
                });
            });
            
            return true;
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Try to initialize all user filter dropdowns with retries
        function tryInitUserFilters(retries = 10) {
            // Find all user filter toggles
            const toggles = document.querySelectorAll('.' + tableId + ' .user-filter-toggle, [id^="' + tableId + '-"][id$="-toggle"].user-filter-toggle');
            let allInitialized = true;
            let foundAny = false;
            
            toggles.forEach(function(toggle) {
                const id = toggle.id;
                // Extract filter key from ID (e.g., "crud-table-posts-author_id-toggle" -> "author_id")
                const match = id.match(new RegExp(tableId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '-(.+)-toggle'));
                if (match && match[1]) {
                    foundAny = true;
                    const filterKey = match[1];
                    if (!initUserFilterDropdown(filterKey)) {
                        allInitialized = false;
                    }
                }
            });
            
            if (!foundAny || (!allInitialized && retries > 0)) {
                setTimeout(() => tryInitUserFilters(retries - 1), 100);
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => tryInitUserFilters(), 200);
            });
        } else {
            setTimeout(() => tryInitUserFilters(), 200);
        }
    })();
    </script>

    <script nonce="<?php echo function_exists('csp_nonce') ? csp_nonce() : ''; ?>">
    // Initialize global flagCodeMap if not already set
    window.flagCodeMap = window.flagCodeMap || {
        'sr': 'rs', 'hr': 'hr', 'bg': 'bg', 'ro': 'ro', 'sl': 'si', 'el': 'gr', 'mk': 'mk',
        'en': 'gb', 'de': 'de', 'fr': 'fr', 'es': 'es', 'it': 'it', 'pt': 'pt', 'nl': 'nl',
        'pl': 'pl', 'ru': 'ru', 'uk': 'ua', 'cs': 'cz', 'sk': 'sk', 'hu': 'hu',
        'sv': 'se', 'da': 'dk', 'no': 'no', 'fi': 'fi',
        'lt': 'lt', 'et': 'ee', 'lv': 'lv',
        'zh': 'cn', 'ja': 'jp', 'ko': 'kr', 'tr': 'tr'
    };
    
    (function() {
        const config = <?= $configJson ?>;
        const tableId = '<?= htmlspecialchars($tableId) ?>';
        
        // Restore render functions from PHP columns (they can't be serialized to JSON)
        // Render functions need to be defined in JavaScript in the view file
        // For now, we'll store column metadata
        <?php
        /** @var array<int, array<string, mixed>> $columns */
        $originalColumnsJson = json_encode(array_map(function($col) {
            /** @var array<string, mixed> $col */
            /** @var array<string, mixed> $colCopy */
            $colCopy = (array)$col;
            if (isset($colCopy['render'])) {
                $colCopy['hasRender'] = true;
                // Keep render as string if it's a string (function name)
                if (is_string($colCopy['render'])) {
                    // Keep the render string
                } else {
                    // Remove function if it's a closure
                    unset($colCopy['render']);
                }
            }
            return $colCopy;
        }, $columns), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>
        const originalColumns = <?php echo $originalColumnsJson; ?>;
        
        config.originalColumns = originalColumns;
        
        // Initialize CRUD Table when DOM and script are ready
        function initCrudTable(retries = 50) {
            if (typeof window.CrudTable !== 'undefined') {
                try {
                    // Ensure render functions object exists (don't overwrite if already set by view)
                    window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
                    if (!window.crudTableRenderFunctions[tableId]) {
                        window.crudTableRenderFunctions[tableId] = {};
                    }
                    
                    const crudTable = new window.CrudTable(tableId, config);
                } catch (error) {
                    console.error('Error initializing CrudTable:', error);
                }
            } else if (retries > 0) {
                // Retry after a short delay if CrudTable is not yet loaded
                setTimeout(() => initCrudTable(retries - 1), 100);
            } else {
                console.error('CrudTable class not found after multiple retries. Make sure crud-table.js is loaded.');
            }
        }
        
        // Wait for DOM to be ready and give time for render functions to be registered
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                // Give a small delay to ensure render functions from view files are registered
                setTimeout(() => initCrudTable(), 200);
            });
        } else {
            // DOM is already ready, but give time for render functions
            setTimeout(() => initCrudTable(), 200);
        }
    })();
    </script>
<?php
}
}
