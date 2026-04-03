{{-- Roles Index --}}

@php
require_once ViewEngine::getViewPath() . '/helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

renderCrudTable([
    'app' => 'roles',
    'createUrl' => "/{$lang}/dashboard/users/roles/create",
    'editUrl' => "/{$lang}/dashboard/users/roles/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/users/roles/{id}/delete",
    'enableLanguageFilter' => false,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'priority',
    'defaultOrder' => 'desc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'name',
            'label' => 'Name',
            'sortable' => true
        ],
        [
            'key' => 'slug',
            'label' => 'Slug',
            'sortable' => true,
            'render' => function($row) {
                return '<span class="text-slate-400 text-sm font-mono">' . htmlspecialchars($row['slug'] ?? '') . '</span>';
            }
        ],
        [
            'key' => 'description',
            'label' => 'Description',
            'sortable' => false
        ],
        [
            'key' => 'priority',
            'label' => 'Priority',
            'sortable' => true
        ],
        [
            'key' => 'permissions_count',
            'label' => 'Permissions',
            'sortable' => false,
            'render' => function($row) {
                $count = (int) ($row['permissions_count'] ?? 0);
                return '<span class="inline-flex items-center gap-1"><ion-icon name="key-outline" class="text-theme-primary"></ion-icon>' . $count . ' permission(s)</span>';
            }
        ],
        [
            'key' => 'is_system',
            'label' => 'Type',
            'sortable' => true,
            'render' => function($row) {
                $isSystem = $row['is_system'] ?? false;
                if ($isSystem) {
                    return '<span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded">System</span>';
                }
                return '<span class="px-2 py-1 bg-slate-700/50 text-slate-400 text-xs rounded">Custom</span>';
            }
        ]
    ]
]);
@endphp
