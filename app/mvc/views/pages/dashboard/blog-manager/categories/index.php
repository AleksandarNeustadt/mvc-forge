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

renderCrudTable([
    'app' => 'blog-categories',
    'createUrl' => "/{$lang}/dashboard/blog/categories/create",
    'editUrl' => "/{$lang}/dashboard/blog/categories/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/blog/categories/{id}/delete",
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'sort_order',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'name',
            'label' => 'Name',
            'sortable' => true,
            'render' => function($row) {
                $depth = $row['depth'] ?? 0;
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);
                $name = htmlspecialchars($row['name'] ?? 'Untitled');
                $slug = !empty($row['slug']) ? '<div class="text-xs text-slate-400 mt-1">Slug: ' . htmlspecialchars($row['slug']) . '</div>' : '';
                return '<div class="flex items-center gap-2"><span class="text-white">' . $indent . '</span><div><div class="text-sm font-medium text-white">' . $name . '</div>' . $slug . '</div></div>';
            }
        ],
        [
            'key' => 'language',
            'label' => 'Language',
            'sortable' => false,
            'render' => 'renderCategoryLanguageColumn'
        ],
        [
            'key' => 'parent_id',
            'label' => 'Parent',
            'sortable' => false,
            'render' => function($row) {
                return '<span class="text-sm text-slate-300">' . htmlspecialchars($row['parent_name'] ?? '—') . '</span>';
            }
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => 'renderCategoryCreatedColumn'
        ]
    ],
    'customActions' => [
        'render' => 'renderCategoryCustomActions'
    ]
]);
?>
