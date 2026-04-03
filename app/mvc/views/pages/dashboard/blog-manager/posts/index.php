<?php
require_once __DIR__ . '/../../../../helpers/crud-table.php';

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

// Filters will be auto-generated based on columns
// If you need custom filters, you can explicitly define them:
// 'filters' => [...]
renderCrudTable([
    'app' => 'blog-posts',
    'createUrl' => "/{$lang}/dashboard/blog/posts/create",
    'editUrl' => "/{$lang}/dashboard/blog/posts/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/blog/posts/{id}/delete",
    'customActions' => [
        'render' => 'renderBlogPostCustomActions'
    ],
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'created_at',
    'defaultOrder' => 'desc',
    'perPage' => 50,
    // Filters will be auto-generated based on columns:
    // - author column → author_id filter
    // - status column → status filter
    // - created_at column → created_at date-range filter
    // If you need category filter, you can add category column or explicitly define it in 'filters'
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
            'key' => 'language',
            'label' => 'Language',
            'sortable' => false,
            'render' => 'renderLanguageColumn'
        ],
        [
            'key' => 'author',
            'label' => 'Author',
            'sortable' => false,
            'render' => 'renderAuthorColumn'
        ],
        [
            'key' => 'category_id',
            'label' => 'Category',
            'sortable' => false,
            'render' => 'renderCategoryColumn'
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'sortable' => true,
            'render' => function($row) {
                $status = $row['status'] ?? 'draft';
                $statusColors = [
                    'published' => 'bg-green-900/50 text-green-300',
                    'draft' => 'bg-slate-700/50 text-slate-300',
                    'archived' => 'bg-red-900/50 text-red-300'
                ];
                $statusColor = $statusColors[$status] ?? $statusColors['draft'];
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusColor . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
            }
        ],
        [
            'key' => 'published_at',
            'label' => 'Published',
            'sortable' => true,
            'render' => 'renderPublishedColumn'
        ],
        [
            'key' => 'views',
            'label' => 'Views',
            'sortable' => true
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => 'renderCreatedColumn'
        ]
    ]
]);
?>
<script nonce="<?= csp_nonce() ?>">
// Map language codes to flag codes (make it global for fallback in crud-table.js)
window.flagCodeMap = window.flagCodeMap || {
    'sr': 'rs', 'hr': 'hr', 'bg': 'bg', 'ro': 'ro', 'sl': 'si', 'el': 'gr', 'mk': 'mk',
    'en': 'gb', 'de': 'de', 'fr': 'fr', 'es': 'es', 'it': 'it', 'pt': 'pt', 'nl': 'nl',
    'pl': 'pl', 'ru': 'ru', 'uk': 'ua', 'cs': 'cz', 'sk': 'sk', 'hu': 'hu',
    'sv': 'se', 'da': 'dk', 'no': 'no', 'fi': 'fi',
    'lt': 'lt', 'et': 'ee', 'lv': 'lv',
    'zh': 'cn', 'ja': 'jp', 'ko': 'kr', 'tr': 'tr'
};
const flagCodeMap = window.flagCodeMap;

// Helper function to escape HTML (defined outside object for easier access)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Define render functions for this specific table
// Note: tableId is generated as 'crud-table-' + app name with dashes removed
// For 'blog-posts', it becomes 'crud-table-blogposts'
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-blogposts'] = {
    renderLanguageColumn: function(row) {
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
    renderCategoryColumn: function(row) {
        // Blog posts can have multiple categories (many-to-many)
        // For now, show first category or "Multiple" if more than one
        let categories = row.categories || [];
        if (Array.isArray(categories) && categories.length > 0) {
            if (categories.length === 1) {
                const category = categories[0];
                const categoryName = typeof category === 'string' ? category : (category.name || 'Unknown');
                return `<span class="text-sm text-slate-300">${escapeHtml(categoryName)}</span>`;
            } else {
                return `<span class="text-sm text-slate-300">${categories.length} categories</span>`;
            }
        }
        // Fallback: try to get category from category_id or category_name
        if (row.category_name) {
            return `<span class="text-sm text-slate-300">${escapeHtml(row.category_name)}</span>`;
        }
        return '<span class="text-sm text-slate-500">—</span>';
    },
    renderAuthorColumn: function(row) {
        const lang = '<?= htmlspecialchars($lang) ?>';
        let author = row.author;
        let authorName = row.author_name;
        let authorId = null;
        let avatar = null;
        
        // If author is an object
        if (author && typeof author === 'object' && !Array.isArray(author)) {
            authorName = author.full_name || author.username || author.email || 'Unknown';
            authorId = author.id;
            avatar = author.avatar;
        } else if (authorName) {
            // Try to get author ID from row
            authorId = row.author_id;
        }
        
        const safeAuthorName = escapeHtml(authorName || 'Unknown');
        const safeAvatar = avatar ? escapeHtml(avatar) : null;
        
        // Get initial for avatar fallback
        const initial = authorName ? authorName.charAt(0).toUpperCase() : 'U';
        
        // Build avatar HTML
        let avatarHtml = '';
        if (safeAvatar) {
            avatarHtml = `<img src="${safeAvatar}" alt="${safeAuthorName}" class="w-8 h-8 rounded-full object-cover">`;
        } else {
            avatarHtml = `<div class="w-8 h-8 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center">
                <span class="text-theme-primary font-semibold text-xs">${escapeHtml(initial)}</span>
            </div>`;
        }
        
        // Build link if we have author ID
        if (authorId) {
            return `<a href="/${lang}/dashboard/users/${authorId}" class="flex items-center gap-2 hover:text-theme-primary transition-colors">
                ${avatarHtml}
                <span class="text-sm text-slate-300">${safeAuthorName}</span>
            </a>`;
        } else {
            return `<div class="flex items-center gap-2">
                ${avatarHtml}
                <span class="text-sm text-slate-300">${safeAuthorName}</span>
            </div>`;
        }
    },
    renderPublishedColumn: function(row) {
        if (!row.published_at) {
            return '<span class="text-slate-500">—</span>';
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
        
        // Parse timestamp
        let timestamp;
        if (typeof row.published_at === 'number') {
            // If it's already a Unix timestamp (in seconds)
            timestamp = row.published_at;
        } else if (typeof row.published_at === 'string') {
            // Try to parse as date string
            const date = new Date(row.published_at);
            if (!isNaN(date.getTime())) {
                timestamp = Math.floor(date.getTime() / 1000);
            } else {
                return '<span class="text-slate-500">—</span>';
            }
        } else {
            return '<span class="text-slate-500">—</span>';
        }
        
        return renderDateTime(timestamp);
    },
    renderCreatedColumn: function(row) {
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
            // Handle both '2026-01-07 12:00:00' format and other date formats
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
    },
    renderBlogPostCustomActions: function(row) {
        const lang = '<?= htmlspecialchars($lang) ?>';
        const previewUrl = `/${lang}/dashboard/blog/posts/${row.id}/preview`;
        const editUrl = `/${lang}/dashboard/blog/posts/${row.id}/edit`;
        const deleteUrl = `/${lang}/dashboard/blog/posts/${row.id}/delete`;
        
        return `<div class="flex items-center justify-end gap-2">
            <a href="${previewUrl}" 
               target="_blank" 
               class="text-blue-400 hover:text-blue-300 transition-colors" 
               title="Preview">
                <ion-icon name="eye-outline" class="text-xl"></ion-icon>
            </a>
            <a href="${editUrl}" 
               class="text-theme-primary hover:text-theme-primary/80 transition-colors" 
               title="Edit">
                <ion-icon name="create-outline" class="text-xl"></ion-icon>
            </a>
            <button onclick="if(confirm('Are you sure you want to delete this post?')) { fetch('${deleteUrl}', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(() => window.location.reload()); }" 
                    class="text-red-400 hover:text-red-300 transition-colors" 
                    title="Delete">
                <ion-icon name="trash-outline" class="text-xl"></ion-icon>
            </button>
        </div>`;
    }
};
</script>

