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
    'app' => 'navigation-menus',
    'createUrl' => "/{$lang}/dashboard/navigation-menus/create",
    'editUrl' => "/{$lang}/dashboard/navigation-menus/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/navigation-menus/{id}/delete",
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'menu_order',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'name',
            'label' => 'Name',
            'sortable' => true
        ],
        [
            'key' => 'position',
            'label' => 'Position',
            'sortable' => true,
            'render' => function($row) {
                $position = htmlspecialchars($row['position'] ?? '');
                return '<span class="px-2 py-1 bg-slate-700/50 rounded text-xs font-medium">' . $position . '</span>';
            }
        ],
        [
            'key' => 'language',
            'label' => 'Language',
            'sortable' => false,
            'render' => 'renderMenuLanguageColumn'
        ],
        [
            'key' => 'page_count',
            'label' => 'Pages',
            'sortable' => false,
            'render' => function($row) {
                $count = (int) ($row['page_count'] ?? 0);
                return '<span class="text-sm text-slate-300">' . $count . '</span>';
            }
        ],
        [
            'key' => 'menu_order',
            'label' => 'Order',
            'sortable' => true
        ],
        [
            'key' => 'is_active',
            'label' => 'Status',
            'sortable' => true,
            'render' => function($row) {
                $isActive = $row['is_active'] ?? false;
                if ($isActive) {
                    return '<span class="px-2 py-1 bg-green-900/30 text-green-400 rounded text-xs font-medium">Active</span>';
                }
                return '<span class="px-2 py-1 bg-slate-700/50 text-slate-400 rounded text-xs font-medium">Inactive</span>';
            }
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => 'renderMenuCreatedColumn'
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
// For 'navigation-menus', it becomes 'crud-table-navigationmenus'
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-navigationmenus'] = {
    renderMenuLanguageColumn: function(row) {
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
    renderMenuCreatedColumn: function(row) {
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
