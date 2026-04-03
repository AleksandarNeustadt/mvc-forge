{{-- Tags Index Page --}}

@php
require_once ViewEngine::getViewPath() . '/helpers/crud-table.php';

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
    'app' => 'blog-tags',
    'createUrl' => "/{$lang}/dashboard/blog/tags/create",
    'editUrl' => "/{$lang}/dashboard/blog/tags/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/blog/tags/{id}/delete",
    'enableLanguageFilter' => true,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'name',
    'defaultOrder' => 'asc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'id',
            'label' => 'ID',
            'sortable' => true
        ],
        [
            'key' => 'name',
            'label' => 'Name',
            'sortable' => true,
            'render' => function($row) {
                $name = htmlspecialchars($row['name'] ?? 'Untitled');
                $slug = !empty($row['slug']) ? '<div class="text-xs text-slate-400 mt-1">Slug: ' . htmlspecialchars($row['slug']) . '</div>' : '';
                return '<div class="text-sm font-medium text-white">' . $name . '</div>' . $slug;
            }
        ],
        [
            'key' => 'language',
            'label' => 'Language',
            'sortable' => false,
            'render' => function($row) use ($flagCodes) {
                if (!empty($row['language'])) {
                    $langCode = strtolower($row['language']['code'] ?? '');
                    $flagCode = $flagCodes[$langCode] ?? 'xx';
                    return '<div class="flex items-center gap-2"><span class="fi fi-' . htmlspecialchars($flagCode) . '" style="font-size: 1.25rem;"></span></div>';
                }
                return '<span class="text-sm text-slate-500">N/A</span>';
            }
        ],
        [
            'key' => 'post_count',
            'label' => 'Posts',
            'sortable' => false,
            'render' => function($row) {
                $count = (int) ($row['post_count'] ?? 0);
                return '<span class="text-sm text-slate-300">' . $count . ' post(s)</span>';
            }
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => function($row) {
                if (!empty($row['created_at'])) {
                    $timestamp = is_int($row['created_at']) ? $row['created_at'] : strtotime($row['created_at']);
                    return date('Y-m-d', $timestamp);
                }
                return 'N/A';
            }
        ]
    ]
]);
@endphp
