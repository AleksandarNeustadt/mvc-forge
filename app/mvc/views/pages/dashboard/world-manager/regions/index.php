<?php
require_once __DIR__ . '/../../../../helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

renderCrudTable([
    'app' => 'regions',
    'createUrl' => "/{$lang}/dashboard/regions/create",
    'editUrl' => "/{$lang}/dashboard/regions/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/regions/{id}/delete",
    'enableLanguageFilter' => false,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'sort_order',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'continent',
            'label' => 'Continent',
            'sortable' => false,
            'render' => 'renderContinentColumn'
        ],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'native_name', 'label' => 'Native Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code', 'sortable' => true],
        [
            'key' => 'is_active',
            'label' => 'Status',
            'sortable' => true,
            'render' => 'renderActiveStatusColumn'
        ]
    ]
]);
?>
<script nonce="<?= csp_nonce() ?>">
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-regions'] = {
    renderContinentColumn: function(row) {
        if (row.continent) {
            // Map continent codes to emoji icons
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
                <span>${row.continent.name || 'N/A'}</span>
            </span>`;
        }
        return '<span class="text-slate-500">N/A</span>';
    },
    renderActiveStatusColumn: function(row) {
        const isActive = row.is_active;
        const statusClass = isActive ? 'bg-green-900/30 text-green-400' : 'bg-slate-700/50 text-slate-400';
        const statusText = isActive ? 'Active' : 'Inactive';
        return `<span class="px-2 py-1 ${statusClass} rounded text-xs font-medium">${statusText}</span>`;
    }
};
</script>
