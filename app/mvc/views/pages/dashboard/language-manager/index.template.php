{{-- Language Manager Index Page --}}

@php
require_once ViewEngine::getViewPath() . '/helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';
@endphp

@php
renderCrudTable([
    'app' => 'languages',
    'createUrl' => "/{$lang}/dashboard/languages/create",
    'editUrl' => "/{$lang}/dashboard/languages/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/languages/{id}/delete",
    'enableLanguageFilter' => false, // Languages don't have language filter
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'sort_order',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'flag',
            'label' => 'Flag',
            'sortable' => false,
            'render' => 'renderFlagColumn' // JS render function
        ],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'native_name', 'label' => 'Native Name', 'sortable' => true],
        [
            'key' => 'code',
            'label' => 'Code',
            'sortable' => true,
            'render' => 'renderCodeColumn' // JS render function
        ],
        [
            'key' => 'continent',
            'label' => 'Continent',
            'sortable' => false,
            'render' => 'renderContinentColumn' // JS render function
        ],
        [
            'key' => 'region',
            'label' => 'Region',
            'sortable' => false,
            'render' => 'renderRegionColumn' // JS render function
        ],
        [
            'key' => 'content_count',
            'label' => 'Content',
            'sortable' => false,
            'render' => 'renderContentCountColumn' // JS render function
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'sortable' => false,
            'render' => 'renderLanguageStatusColumn' // JS render function
        ]
    ],
    'customActions' => [
        'render' => 'renderLanguageCustomActions' // JS render function
    ]
]);
@endphp

<script nonce="{{ csp_nonce() }}">
{{-- Ensure render functions are defined BEFORE crud-table.js tries to use them --}}
{{-- Define getFlagCode function locally if not available globally --}}
if (typeof window.getFlagCode !== 'function') {
    const flagCodeMap = {
        'sr': 'rs', 'hr': 'hr', 'bg': 'bg', 'ro': 'ro', 'sl': 'si', 'el': 'gr', 'mk': 'mk',
        'en': 'gb', 'de': 'de', 'fr': 'fr', 'es': 'es', 'it': 'it', 'pt': 'pt', 'nl': 'nl',
        'pl': 'pl', 'ru': 'ru', 'uk': 'ua', 'cs': 'cz', 'sk': 'sk', 'hu': 'hu',
        'sv': 'se', 'da': 'dk', 'no': 'no', 'fi': 'fi',
        'lt': 'lt', 'et': 'ee', 'lv': 'lv',
        'zh': 'cn', 'ja': 'jp', 'ko': 'kr', 'tr': 'tr'
    };
    
    window.getFlagCode = function(langCode, flagFromDb = null) {
        {{-- Always use the language code mapping for flag-icons library --}}
        {{-- Ignore emoji from database as flag-icons uses 2-letter country codes --}}
        const code = (langCode || '').toLowerCase();
        return flagCodeMap[code] || code || 'xx';
    };
}

{{-- Define render functions for this specific table --}}
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-languages'] = {
    renderFlagColumn: function(row) {
        const flagCode = window.getFlagCode(row.code, row.flag);
        return `<span class="fi fi-${flagCode}" style="font-size: 1.5rem;"></span>`;
    },
    renderCodeColumn: function(row) {
        const code = (row.code || '').toUpperCase();
        return `<span class="text-white font-mono font-medium">${code}</span>`;
    },
    renderContinentColumn: function(row) {
        if (row.continent && row.continent.name) {
            {{-- Map continent codes to emoji icons --}}
            const continentIcons = {
                'eu': '🌍',  // Europe
                'as': '🌏',  // Asia
                'na': '🌎',  // North America
                'sa': '🌎',  // South America
                'af': '🦁',  // Africa
                'oc': '🏝️',  // Oceania
                'an': '❄️'   // Antarctica
            };
            const icon = continentIcons[row.continent.code?.toLowerCase()] || '🗺️';
            return `<span class="text-slate-300 flex items-center gap-2">
                <span class="text-lg">${icon}</span>
                <span>${row.continent.name}</span>
            </span>`;
        }
        return `<span class="text-slate-500">—</span>`;
    },
    renderRegionColumn: function(row) {
        if (row.region && row.region.name) {
            return `<span class="text-slate-300">${row.region.name}</span>`;
        }
        return `<span class="text-slate-500">—</span>`;
    },
    renderContentCountColumn: function(row) {
        const count = parseInt(row.content_count || 0);
        return `<span class="text-slate-300">${count} items</span>`;
    },
    renderLanguageStatusColumn: function(row) {
        let html = '';
        if (row.is_default) {
            html += '<span class="px-2 py-1 bg-blue-900/30 text-blue-400 rounded text-xs font-medium mr-2">Default</span>';
        }
        if (row.is_active) {
            html += '<span class="px-2 py-1 bg-green-900/30 text-green-400 rounded text-xs font-medium">Active</span>';
        } else {
            html += '<span class="px-2 py-1 bg-slate-700/50 text-slate-400 rounded text-xs font-medium">Inactive</span>';
        }
        return html;
    },
    renderLanguageCustomActions: function(row) {
        const lang = '{{ $lang }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let actionsHtml = '';

        if (!row.is_default) {
            actionsHtml += `
                <form method="POST" action="/${lang}/dashboard/languages/${row.id}/set-default" class="inline">
                    <input type="hidden" name="_csrf_token" value="${csrfToken}">
                    <button type="submit" 
                            class="px-3 py-1.5 bg-blue-900/30 hover:bg-blue-900/50 text-blue-400 text-sm rounded transition-colors">
                        Set Default
                    </button>
                </form>
            `;
        }

        {{-- Edit button --}}
        actionsHtml += `
            <a href="/${lang}/dashboard/languages/${row.id}/edit"
               class="px-3 py-1.5 bg-slate-700/50 hover:bg-slate-700 text-white text-sm rounded transition-colors">
                Edit
            </a>
        `;

        {{-- Delete button (disabled if default or has content) --}}
        const canDelete = !row.is_default && (row.content_count === 0);
        if (canDelete) {
            actionsHtml += `
                <form method="POST" action="/${lang}/dashboard/languages/${row.id}/delete" class="inline delete-language-form" data-confirm="Are you sure you want to delete this language?">
                    <input type="hidden" name="_csrf_token" value="${csrfToken}">
                    <button type="submit" 
                            class="px-3 py-1.5 bg-red-900/30 hover:bg-red-900/50 text-red-400 text-sm rounded transition-colors">
                        Delete
                    </button>
                </form>
            `;
        } else {
            actionsHtml += `
                <button type="button" disabled
                        class="px-3 py-1.5 bg-red-900/10 text-red-700 text-sm rounded cursor-not-allowed"
                        title="Cannot delete language with associated content or if it's default.">
                    Delete
                </button>
            `;
        }
        return actionsHtml;
    }
};

// Handle form submissions with confirmation (CSP-compliant with event delegation)
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.classList.contains('delete-language-form')) {
            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
