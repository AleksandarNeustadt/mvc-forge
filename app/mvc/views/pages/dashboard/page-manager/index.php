<?php
require_once __DIR__ . '/../../../helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

// Map language codes to flag codes
$flagCodes = [
    'sr' => 'rs', 'hr' => 'hr', 'bg' => 'bg', 'ro' => 'ro', 'sl' => 'si', 'el' => 'gr', 'mk' => 'mk',
    'en' => 'gb', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'pt' => 'pt', 'nl' => 'nl',
    'pl' => 'pl', 'ru' => 'ru', 'uk' => 'ua', 'cs' => 'cz', 'sk' => 'sk', 'hu' => 'hu',
    'sv' => 'se', 'da' => 'dk', 'no' => 'no', 'fi' => 'fi',
    'lt' => 'lt', 'et' => 'ee', 'lv' => 'lv',
    'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'tr' => 'tr'
];

renderCrudTable([
    'app' => 'pages',
    'createUrl' => "/{$lang}/dashboard/pages/create",
    'editUrl' => "/{$lang}/dashboard/pages/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/pages/{id}/delete",
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'created_at',
    'defaultOrder' => 'desc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'title',
            'label' => 'Title',
            'sortable' => true,
            'render' => function($row) {
                $title = htmlspecialchars($row['title'] ?? 'Untitled');
                $slug = !empty($row['slug']) ? '<div class="text-xs text-slate-400 mt-1">Slug: ' . htmlspecialchars($row['slug']) . '</div>' : '';
                return '<div class="text-sm font-medium text-white">' . $title . '</div>' . $slug;
            }
        ],
        [
            'key' => 'route',
            'label' => 'Route',
            'sortable' => true,
            'render' => function($row) {
                $route = htmlspecialchars($row['route'] ?? '/');
                return '<code class="text-xs text-slate-300 bg-slate-900/50 px-2 py-1 rounded">' . $route . '</code>';
            }
        ],
        [
            'key' => 'page_type',
            'label' => 'Type',
            'sortable' => true,
            'render' => function($row) {
                $type = ucfirst(str_replace('_', ' ', $row['page_type'] ?? 'custom'));
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-300">' . htmlspecialchars($type) . '</span>';
            }
        ],
        [
            'key' => 'language',
            'label' => 'Language',
            'sortable' => false,
            'render' => 'renderPageLanguageColumn'
        ],
        [
            'key' => 'is_active',
            'label' => 'Status',
            'sortable' => true,
            'render' => function($row) {
                $isActive = $row['is_active'] ?? false;
                if ($isActive) {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-300">Active</span>';
                }
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-400">Inactive</span>';
            }
        ],
        [
            'key' => 'is_in_menu',
            'label' => 'In Menu',
            'sortable' => true,
            'render' => function($row) {
                $inMenu = $row['is_in_menu'] ?? false;
                if ($inMenu) {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900/50 text-blue-300">Yes</span>';
                }
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-400">No</span>';
            }
        ],
        [
            'key' => 'menu_order',
            'label' => 'Order',
            'sortable' => true
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => 'renderPageCreatedColumn'
        ]
    ]
]);
?>

<script nonce="<?= csp_nonce() ?>">
// Ensure flagCodeMap is available globally
const flagCodeMap = {
    'sr': 'rs', 'hr': 'hr', 'bg': 'bg', 'ro': 'ro', 'sl': 'si', 'el': 'gr', 'mk': 'mk',
    'en': 'gb', 'de': 'de', 'fr': 'fr', 'es': 'es', 'it': 'it', 'pt': 'pt', 'nl': 'nl',
    'pl': 'pl', 'ru': 'ru', 'uk': 'ua', 'cs': 'cz', 'sk': 'sk', 'hu': 'hu',
    'sv': 'se', 'da': 'dk', 'no': 'no', 'fi': 'fi',
    'lt': 'lt', 'et': 'ee', 'lv': 'lv',
    'zh': 'cn', 'ja': 'jp', 'ko': 'kr', 'tr': 'tr'
};
window.flagCodeMap = window.flagCodeMap || flagCodeMap;

// Define render functions for this specific table
// Note: tableId is generated as 'crud-table-' + app name with dashes removed
// For 'pages', it becomes 'crud-table-pages'
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-pages'] = {
    renderPageLanguageColumn: function(row) {
        // Handle both object format and direct value
        let language = row.language;
        
        // If language is an object with nested properties
        if (language && typeof language === 'object' && !Array.isArray(language)) {
            const langCode = (language.code || '').toLowerCase();
            const flagCode = flagCodeMap[langCode] || langCode || 'xx';
            const langName = language.name || language.code || '';
            
            if (langCode) {
                return `<div class="flex items-center gap-2">
                    <span class="fi fi-${flagCode}" style="font-size: 1.25rem;"></span>
                    <span class="text-sm text-slate-300">${langName}</span>
                </div>`;
            }
        }
        
        // Fallback: try to get language code directly from row
        if (row.language_code) {
            const langCode = (row.language_code || '').toLowerCase();
            const flagCode = flagCodeMap[langCode] || langCode || 'xx';
            const langName = row.language_name || row.language_code || '';
            return `<div class="flex items-center gap-2">
                <span class="fi fi-${flagCode}" style="font-size: 1.25rem;"></span>
                <span class="text-sm text-slate-300">${langName}</span>
            </div>`;
        }
        
        return '<span class="text-sm text-slate-500">N/A</span>';
    },
    renderPageCreatedColumn: function(row) {
        if (!row.created_at) {
            return '<span class="text-slate-500">N/A</span>';
        }
        
        // Helper function to render date/time
        const renderDateTime = (timestamp) => {
            const now = Math.floor(Date.now() / 1000);
            const diff = now - timestamp;
            
            // Calculate relative time
            let relativeTime = '';
            if (diff < 60) {
                relativeTime = 'pre ' + diff + ' sekundi';
            } else if (diff < 3600) {
                const minutes = Math.floor(diff / 60);
                relativeTime = 'pre ' + minutes + (minutes === 1 ? ' minute' : ' minuta');
            } else if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                relativeTime = 'pre ' + hours + (hours === 1 ? ' sata' : ' sati');
            } else if (diff < 2592000) {
                const days = Math.floor(diff / 86400);
                relativeTime = 'pre ' + days + (days === 1 ? ' dana' : ' dana');
            } else if (diff < 31536000) {
                const months = Math.floor(diff / 2592000);
                relativeTime = 'pre ' + months + (months === 1 ? ' meseca' : ' meseci');
            } else {
                const years = Math.floor(diff / 31536000);
                relativeTime = 'pre ' + years + (years === 1 ? ' godine' : ' godina');
            }
            
            // Format date: dd.mm.Yyyy HH:mm:ss
            const date = new Date(timestamp * 1000);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            const formattedDate = `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;
            
            return `<div class="flex flex-col">
                <span class="text-sm text-slate-300">${relativeTime}</span>
                <span class="text-xs text-slate-500">${formattedDate}</span>
            </div>`;
        };
        
        // Parse timestamp - handle both TIMESTAMP string and Unix timestamp
        let timestamp;
        if (typeof row.created_at === 'number') {
            // If it's already a Unix timestamp (in seconds)
            timestamp = row.created_at;
        } else if (typeof row.created_at === 'string') {
            // Try to parse as date string (MySQL TIMESTAMP format: 'YYYY-MM-DD HH:MM:SS')
            let dateStr = row.created_at.trim();
            
            // If it looks like a MySQL TIMESTAMP string
            if (/^\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2}/.test(dateStr)) {
                // Replace space with 'T' for ISO format if needed, or parse directly
                const date = new Date(dateStr.replace(' ', 'T'));
                if (!isNaN(date.getTime())) {
                    timestamp = Math.floor(date.getTime() / 1000);
                } else {
                    // Fallback: try parsing as-is
                    const date2 = new Date(dateStr);
                    if (!isNaN(date2.getTime())) {
                        timestamp = Math.floor(date2.getTime() / 1000);
                    } else {
                        console.warn('Could not parse created_at:', row.created_at);
                        return '<span class="text-slate-500">N/A</span>';
                    }
                }
            } else {
                // Try parsing as any date string
                const date = new Date(dateStr);
                if (!isNaN(date.getTime())) {
                    timestamp = Math.floor(date.getTime() / 1000);
                } else {
                    console.warn('Could not parse created_at:', row.created_at);
                    return '<span class="text-slate-500">N/A</span>';
                }
            }
        } else {
            console.warn('created_at is not a string or number:', typeof row.created_at, row.created_at);
            return '<span class="text-slate-500">N/A</span>';
        }
        
        return renderDateTime(timestamp);
    }
};
</script>
