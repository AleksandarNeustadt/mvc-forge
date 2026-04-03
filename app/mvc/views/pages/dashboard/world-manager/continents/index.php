<?php
require_once __DIR__ . '/../../../../helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

renderCrudTable([
    'app' => 'continents',
    'createUrl' => "/{$lang}/dashboard/continents/create",
    'editUrl' => "/{$lang}/dashboard/continents/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/continents/{id}/delete",
    'enableLanguageFilter' => false,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'sort_order',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'icon',
            'label' => '',
            'sortable' => false,
            'render' => 'renderContinentIconColumn'
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
window.crudTableRenderFunctions['crud-table-continents'] = {
    renderContinentIconColumn: function(row) {
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
        const icon = continentIcons[row.code?.toLowerCase()] || '🗺️';
        return `<span class="text-2xl" title="${row.name || ''}">${icon}</span>`;
    },
    renderActiveStatusColumn: function(row) {
        const isActive = row.is_active;
        const statusClass = isActive ? 'bg-green-900/30 text-green-400' : 'bg-slate-700/50 text-slate-400';
        const statusText = isActive ? 'Active' : 'Inactive';
        return `<span class="px-2 py-1 ${statusClass} rounded text-xs font-medium">${statusText}</span>`;
    }
};
</script>
