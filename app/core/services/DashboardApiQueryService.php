<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\BlogCategory;
use App\Models\Continent;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Region;
use App\Models\User;use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Holds generic Dashboard API query helpers and response enrichment rules.
 */
class DashboardApiQueryService
{
    private const STANDARD_QUERY_PARAMS = [
        'page',
        'limit',
        'search',
        'sort',
        'order',
        'language_id',
        'language_code',
        'continent_id',
        'region_id',
        'api',
        'format',
    ];

    public function collectDynamicFilters(array $queryParams): array
    {
        $dynamicFilters = [];

        foreach ($queryParams as $key => $value) {
            if (in_array($key, self::STANDARD_QUERY_PARAMS, true) || $value === null || $value === '') {
                continue;
            }

            $dynamicFilters[$key] = $value;
        }

        return $dynamicFilters;
    }

    public function applyDynamicFilters($query, array $filters): void
    {
        foreach ($filters as $filterKey => $filterValue) {
            if (substr($filterKey, -5) === '_from') {
                $field = str_replace('_from', '', $filterKey);
                $query->where($field, '>=', $filterValue . ' 00:00:00');
                continue;
            }

            if (substr($filterKey, -3) === '_to') {
                $field = str_replace('_to', '', $filterKey);
                $query->where($field, '<=', $filterValue . ' 23:59:59');
                continue;
            }

            $query->where($filterKey, is_numeric($filterValue) ? (int) $filterValue : $filterValue);
        }
    }

    public function applySearchToQuery($query, string $search, string $modelClass): void
    {
        $searchableFields = $this->getSearchableFields($modelClass);
        if (empty($searchableFields)) {
            return;
        }

        $query->whereAnyLike($searchableFields, '%' . $search . '%');
    }

    public function enrichResourceData(string $app, $resource, array $resourceArray): array
    {
        return match ($app) {
            'blog-posts', 'blog' => $this->enrichBlogPostData($resource, $resourceArray),
            'blog-categories' => $this->enrichLanguageData($resource, $resourceArray, true),
            'pages', 'navigation-menus' => $this->enrichLanguageData($resource, $resourceArray, false),
            'users' => $this->enrichUserRolesData($resource, $resourceArray),
            'roles' => $this->enrichRoleData($resource, $resourceArray),
            'languages' => $this->enrichLanguageResourceData($resource, $resourceArray),
            'regions' => $this->enrichRegionData($resource, $resourceArray),
            default => $resourceArray,
        };
    }

    public function getBlogCategoriesWithRelations(
        int $page,
        int $limit,
        string $search,
        string $sort,
        string $order,
        ?int $languageId,
        ?string $languageCode
    ): array {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];

        if ($languageId !== null && $languageId !== '') {
            $whereConditions[] = 'c.language_id = ?';
            $params[] = $languageId;
        } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $whereConditions[] = 'c.language_id = ?';
                $params[] = $language->id;
            }
        }

        if (!empty($search)) {
            $whereConditions[] = '(c.name LIKE ? OR c.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions)
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';
        $sort = in_array($sort, ['id', 'name', 'slug', 'sort_order', 'created_at', 'parent_id'], true)
            ? $sort
            : 'sort_order';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT c.*, p.name as parent_name,
                       l.code as language_code, l.name as language_name, l.flag as language_flag
                FROM blog_categories c
                LEFT JOIN blog_categories p ON c.parent_id = p.id
                LEFT JOIN languages l ON c.language_id = l.id
                {$whereClause}
                ORDER BY c.{$sort} {$order}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $results = Database::select($sql, $params);

        $categoriesArray = [];
        foreach ($results as $catData) {
            $catArray = $catData;
            $catArray['parent_name'] = $catData['parent_name'] ?? null;
            $catArray['language'] = $catData['language_code'] ? [
                'id' => null,
                'code' => $catData['language_code'],
                'name' => $catData['language_name'],
                'native_name' => $catData['language_name'],
            ] : null;

            $category = new BlogCategory();
            $category->fill($catData);
            $category->exists = true;
            $catArray['depth'] = $category->getDepth();

            $categoriesArray[] = $catArray;
        }

        return $categoriesArray;
    }

    public function getBlogTagsWithRelations(
        int $page,
        int $limit,
        string $search,
        string $sort,
        string $order,
        ?int $languageId,
        ?string $languageCode
    ): array {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];

        if ($languageId !== null && $languageId !== '') {
            $whereConditions[] = 't.language_id = ?';
            $params[] = $languageId;
        } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $whereConditions[] = 't.language_id = ?';
                $params[] = $language->id;
            }
        }

        if (!empty($search)) {
            $whereConditions[] = '(t.name LIKE ? OR t.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions)
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';
        $sort = in_array($sort, ['id', 'name', 'slug', 'created_at'], true) ? $sort : 'name';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT t.*, COUNT(bpt.blog_post_id) as post_count,
                       l.code as language_code, l.name as language_name, l.flag as language_flag
                FROM blog_tags t
                LEFT JOIN blog_post_tags bpt ON t.id = bpt.blog_tag_id
                LEFT JOIN languages l ON t.language_id = l.id
                {$whereClause}
                GROUP BY t.id
                ORDER BY t.{$sort} {$order}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $results = Database::select($sql, $params);

        $tagsArray = [];
        foreach ($results as $tagData) {
            $tagArray = $tagData;
            $tagArray['post_count'] = (int) ($tagData['post_count'] ?? 0);
            $tagArray['language'] = $tagData['language_code'] ? [
                'id' => null,
                'code' => $tagData['language_code'],
                'name' => $tagData['language_name'],
                'native_name' => $tagData['language_name'],
            ] : null;
            $tagsArray[] = $tagArray;
        }

        return $tagsArray;
    }

    public function getBlogCategoriesCount(string $search, ?int $languageId, ?string $languageCode): int
    {
        $whereConditions = [];
        $params = [];

        if ($languageId !== null && $languageId !== '') {
            $whereConditions[] = 'language_id = ?';
            $params[] = $languageId;
        } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $whereConditions[] = 'language_id = ?';
                $params[] = $language->id;
            }
        }

        if (!empty($search)) {
            $whereConditions[] = '(name LIKE ? OR slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions)
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';
        $result = Database::selectOne(
            "SELECT COUNT(*) as count FROM blog_categories {$whereClause}",
            $params
        );

        return (int) ($result['count'] ?? 0);
    }

    public function getBlogTagsCount(string $search, ?int $languageId, ?string $languageCode): int
    {
        $whereConditions = [];
        $params = [];

        if ($languageId !== null && $languageId !== '') {
            $whereConditions[] = 'language_id = ?';
            $params[] = $languageId;
        } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $whereConditions[] = 'language_id = ?';
                $params[] = $language->id;
            }
        }

        if (!empty($search)) {
            $whereConditions[] = '(name LIKE ? OR slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($whereConditions)
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';
        $result = Database::selectOne(
            "SELECT COUNT(*) as count FROM blog_tags {$whereClause}",
            $params
        );

        return (int) ($result['count'] ?? 0);
    }

    public function getBlogPostsWithRelations(
        int $page,
        int $limit,
        string $search,
        string $sort,
        string $order,
        ?int $languageId,
        ?string $languageCode,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $limit;
        [$whereClause, $joinClause, $params, $hasCategoryFilter] = $this->buildBlogPostQueryParts(
            $search,
            $languageId,
            $languageCode,
            $filters
        );
        $sort = in_array(
            $sort,
            ['id', 'title', 'slug', 'status', 'published_at', 'created_at', 'updated_at', 'views', 'author_id'],
            true
        ) ? $sort : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        if ($hasCategoryFilter) {
            $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.status,
                           p.published_at, p.author_id, p.views, p.meta_title, p.meta_description,
                           p.meta_keywords, p.created_at, p.updated_at, p.language_id,
                           l.code as language_code, l.name as language_name, l.native_name as language_native_name,
                           u.id as author_id_from_join, u.username as author_username, u.email as author_email,
                           u.first_name as author_first_name, u.last_name as author_last_name, u.avatar as author_avatar
                    FROM blog_posts p
                    LEFT JOIN languages l ON p.language_id = l.id
                    LEFT JOIN users u ON p.author_id = u.id
                    {$joinClause}
                    {$whereClause}
                    GROUP BY p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.status,
                             p.published_at, p.author_id, p.views, p.meta_title, p.meta_description,
                             p.meta_keywords, p.created_at, p.updated_at, p.language_id,
                             l.code, l.name, l.native_name,
                             u.id, u.username, u.email, u.first_name, u.last_name, u.avatar
                    ORDER BY p.{$sort} {$order}
                    LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT p.*,
                           l.code as language_code, l.name as language_name, l.native_name as language_native_name,
                           u.id as author_id_from_join, u.username as author_username, u.email as author_email,
                           u.first_name as author_first_name, u.last_name as author_last_name, u.avatar as author_avatar
                    FROM blog_posts p
                    LEFT JOIN languages l ON p.language_id = l.id
                    LEFT JOIN users u ON p.author_id = u.id
                    {$joinClause}
                    {$whereClause}
                    ORDER BY p.{$sort} {$order}
                    LIMIT ? OFFSET ?";
        }

        $params[] = $limit;
        $params[] = $offset;
        $results = Database::select($sql, $params);
        $categoriesMap = $this->loadBlogPostCategories(array_column($results, 'id'));

        $postsArray = [];
        foreach ($results as $postData) {
            $postArray = $postData;
            $postArray['language'] = $postData['language_code'] ? [
                'code' => $postData['language_code'],
                'name' => $postData['language_name'],
                'native_name' => $postData['language_native_name'],
            ] : null;

            if (!empty($postData['author_id_from_join'])) {
                $fullName = trim(($postData['author_first_name'] ?? '') . ' ' . ($postData['author_last_name'] ?? ''));
                $postArray['author_id'] = $postData['author_id_from_join'];
                $postArray['author_name'] = !empty($fullName)
                    ? $fullName
                    : ($postData['author_username'] ?? $postData['author_email'] ?? 'Unknown');
                $postArray['author'] = [
                    'id' => $postData['author_id_from_join'],
                    'username' => $postData['author_username'] ?? '',
                    'email' => $postData['author_email'] ?? '',
                    'first_name' => $postData['author_first_name'] ?? '',
                    'last_name' => $postData['author_last_name'] ?? '',
                    'full_name' => $fullName ?: ($postData['author_username'] ?? 'Unknown'),
                    'avatar' => $postData['author_avatar'] ?? null,
                ];
            }

            $postId = $postData['id'];
            $postArray['categories'] = $categoriesMap[$postId] ?? [];
            if (!empty($postArray['categories'])) {
                $postArray['category_id'] = $postArray['categories'][0]['id'];
                $postArray['category_name'] = $postArray['categories'][0]['name'];
            }

            $postsArray[] = $postArray;
        }

        return $postsArray;
    }

    public function getBlogPostsCount(
        string $search,
        ?int $languageId,
        ?string $languageCode,
        array $filters = []
    ): int {
        [$whereClause, $joinClause, $params, $hasCategoryFilter] = $this->buildBlogPostQueryParts(
            $search,
            $languageId,
            $languageCode,
            $filters
        );
        $distinctClause = $hasCategoryFilter ? 'DISTINCT' : '';
        $result = Database::selectOne(
            "SELECT COUNT({$distinctClause} p.id) as count
             FROM blog_posts p
             {$joinClause}
             {$whereClause}",
            $params
        );

        return (int) ($result['count'] ?? 0);
    }

    public function getLanguagesWithRelations(
        int $page,
        int $limit,
        string $search,
        string $sort,
        string $order,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];

        if (isset($filters['continent_id']) && $filters['continent_id'] !== null && $filters['continent_id'] !== '') {
            $whereConditions[] = 'l.continent_id = ?';
            $params[] = (int) $filters['continent_id'];
        }

        if (isset($filters['region_id']) && $filters['region_id'] !== null && $filters['region_id'] !== '') {
            $whereConditions[] = 'l.region_id = ?';
            $params[] = (int) $filters['region_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $whereConditions[] = 'l.is_active = ?';
            $params[] = (int) $filters['is_active'];
        }

        if (!empty($search)) {
            $whereConditions[] = "(
                l.code LIKE ? OR
                l.name LIKE ? OR
                l.native_name LIKE ? OR
                COALESCE(c.name, '') LIKE ? OR
                COALESCE(r.name, '') LIKE ?
            )";
            $searchParam = "%{$search}%";
            array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        }

        $whereClause = !empty($whereConditions)
            ? 'WHERE ' . implode(' AND ', $whereConditions)
            : '';
        $sortField = in_array(
            $sort,
            ['id', 'code', 'name', 'native_name', 'sort_order', 'is_active', 'is_default', 'created_at', 'updated_at'],
            true
        ) ? $sort : 'sort_order';
        $orderDirection = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        if ($sort === 'continent') {
            $sortField = 'c.name';
        } elseif ($sort === 'region') {
            $sortField = 'r.name';
        } else {
            $sortField = "l.{$sortField}";
        }

        $totalResult = Database::selectOne(
            "SELECT COUNT(DISTINCT l.id) as count
             FROM languages l
             LEFT JOIN continents c ON l.continent_id = c.id
             LEFT JOIN regions r ON l.region_id = r.id
             {$whereClause}",
            $params
        );
        $total = (int) ($totalResult['count'] ?? 0);

        $params[] = $limit;
        $params[] = $offset;
        $results = Database::select(
            "SELECT l.*,
                    c.id as continent_id, c.name as continent_name, c.code as continent_code,
                    r.id as region_id, r.name as region_name, r.code as region_code,
                    COALESCE((
                        (SELECT COUNT(*) FROM pages WHERE language_id = l.id) +
                        (SELECT COUNT(*) FROM navigation_menus WHERE language_id = l.id) +
                        (SELECT COUNT(*) FROM blog_posts WHERE language_id = l.id) +
                        (SELECT COUNT(*) FROM blog_categories WHERE language_id = l.id) +
                        (SELECT COUNT(*) FROM blog_tags WHERE language_id = l.id)
                    ), 0) as content_count
             FROM languages l
             LEFT JOIN continents c ON l.continent_id = c.id
             LEFT JOIN regions r ON l.region_id = r.id
             {$whereClause}
             ORDER BY {$sortField} {$orderDirection}
             LIMIT ? OFFSET ?",
            $params
        );

        $languagesArray = [];
        foreach ($results as $langData) {
            $langArray = [
                'id' => $langData['id'],
                'code' => $langData['code'],
                'name' => $langData['name'],
                'native_name' => $langData['native_name'],
                'flag' => $langData['flag'],
                'country_code' => $langData['country_code'],
                'is_active' => (bool) $langData['is_active'],
                'is_site_language' => (bool) ($langData['is_site_language'] ?? false),
                'is_default' => (bool) $langData['is_default'],
                'sort_order' => (int) $langData['sort_order'],
                'created_at' => $langData['created_at'],
                'updated_at' => $langData['updated_at'],
                'content_count' => (int) ($langData['content_count'] ?? 0),
            ];

            if (!empty($langData['continent_id'])) {
                $langArray['continent'] = [
                    'id' => (int) $langData['continent_id'],
                    'name' => $langData['continent_name'],
                    'code' => $langData['continent_code'],
                ];
            }

            if (!empty($langData['region_id'])) {
                $langArray['region'] = [
                    'id' => (int) $langData['region_id'],
                    'name' => $langData['region_name'],
                    'code' => $langData['region_code'],
                ];
            }

            $languagesArray[] = $langArray;
        }

        return [
            'data' => $languagesArray,
            'total' => $total,
        ];
    }

    public function getFilterOptions(string $filterType, ?int $languageId, ?int $continentId): array
    {
        return match ($filterType) {
            'categories' => $this->getCategoryFilterOptions($languageId),
            'regions' => $this->getRegionFilterOptions($continentId),
            default => throw new InvalidArgumentException("Unknown filter type: {$filterType}"),
        };
    }

    private function getSearchableFields(string $modelClass): array
    {
        $fillable = [];

        try {
            $reflection = new ReflectionClass(new $modelClass());
            $fillableProperty = $reflection->getProperty('fillable');
            $fillableProperty->setAccessible(true);
            $fillable = $fillableProperty->getValue(new $modelClass()) ?? [];
        } catch (ReflectionException $e) {
            $fillable = [];
        }

        $searchableFields = !empty($fillable)
            ? $fillable
            : ['name', 'title', 'slug', 'email', 'username'];

        $excludedFields = [
            'id',
            'is_active',
            'is_default',
            'is_site_language',
            'created_at',
            'updated_at',
            'sort_order',
            'menu_order',
            'language_id',
            'author_id',
            'published_at',
            'views',
        ];

        return array_values(array_filter(
            $searchableFields,
            static fn ($field) => !in_array($field, $excludedFields, true)
        ));
    }

    private function enrichBlogPostData($resource, array $resourceArray): array
    {
        $resourceArray = $this->enrichLanguageData($resource, $resourceArray, false);

        if (!empty($resource->author_id) && class_exists('User')) {
            $author = User::find($resource->author_id);
            if ($author) {
                $fullName = trim(($author->first_name ?? '') . ' ' . ($author->last_name ?? ''));
                $resourceArray['author_id'] = $author->id;
                $resourceArray['author_name'] = $fullName !== ''
                    ? $fullName
                    : ($author->username ?? $author->email ?? 'Unknown');
                $resourceArray['author'] = [
                    'id' => $author->id,
                    'username' => $author->username ?? '',
                    'email' => $author->email ?? '',
                    'first_name' => $author->first_name ?? '',
                    'last_name' => $author->last_name ?? '',
                    'full_name' => $fullName ?: ($author->username ?? 'Unknown'),
                    'avatar' => $author->avatar ?? null,
                ];
            }
        }

        if (method_exists($resource, 'categories') && class_exists('BlogCategory')) {
            $categoriesArray = [];
            foreach ($resource->categories() as $category) {
                $categoriesArray[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug ?? null,
                ];
            }

            $resourceArray['categories'] = $categoriesArray;
            if (!empty($categoriesArray)) {
                $resourceArray['category_id'] = $categoriesArray[0]['id'];
                $resourceArray['category_name'] = $categoriesArray[0]['name'];
            }
        }

        return $resourceArray;
    }

    private function enrichLanguageData($resource, array $resourceArray, bool $includeFlag): array
    {
        if (empty($resource->language_id) || !class_exists('Language')) {
            return $resourceArray;
        }

        $language = Language::find($resource->language_id);
        if (!$language) {
            return $resourceArray;
        }

        $resourceArray['language'] = [
            'id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'native_name' => $language->native_name,
        ];

        if ($includeFlag) {
            $resourceArray['language']['flag'] = $language->flag ?? null;
        } elseif (isset($language->flag)) {
            $resourceArray['language']['flag'] = $language->flag;
        }

        if ($resource instanceof NavigationMenu) {
            $pageCount = Database::select(
                'SELECT COUNT(*) as count FROM pages WHERE navbar_id = ?',
                [$resource->id]
            )[0]['count'] ?? 0;
            $resourceArray['page_count'] = (int) $pageCount;
        }

        return $resourceArray;
    }

    private function enrichUserRolesData($resource, array $resourceArray): array
    {
        if (!method_exists($resource, 'roles')) {
            return $resourceArray;
        }

        $resourceArray['roles'] = [];
        foreach ($resource->roles() as $role) {
            $resourceArray['roles'][] = [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug ?? null,
            ];
        }

        return $resourceArray;
    }

    private function enrichRoleData($resource, array $resourceArray): array
    {
        $permissionsCount = Database::select(
            'SELECT COUNT(*) as count FROM role_permission WHERE role_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $resourceArray['permissions_count'] = (int) $permissionsCount;

        return $resourceArray;
    }

    private function enrichLanguageResourceData($resource, array $resourceArray): array
    {
        $pageCount = Database::select(
            'SELECT COUNT(*) as count FROM pages WHERE language_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $menuCount = Database::select(
            'SELECT COUNT(*) as count FROM navigation_menus WHERE language_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $postCount = Database::select(
            'SELECT COUNT(*) as count FROM blog_posts WHERE language_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $categoryCount = Database::select(
            'SELECT COUNT(*) as count FROM blog_categories WHERE language_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $tagCount = Database::select(
            'SELECT COUNT(*) as count FROM blog_tags WHERE language_id = ?',
            [$resource->id]
        )[0]['count'] ?? 0;
        $resourceArray['content_count'] = (int) (
            $pageCount + $menuCount + $postCount + $categoryCount + $tagCount
        );

        if (!empty($resource->continent_id) && class_exists('Continent')) {
            $continent = Continent::find($resource->continent_id);
            if ($continent) {
                $resourceArray['continent'] = [
                    'id' => $continent->id,
                    'name' => $continent->name,
                    'code' => $continent->code,
                ];
            }
        }

        if (!empty($resource->region_id) && class_exists('Region')) {
            $region = Region::find($resource->region_id);
            if ($region) {
                $resourceArray['region'] = [
                    'id' => $region->id,
                    'name' => $region->name,
                    'code' => $region->code,
                ];
            }
        }

        return $resourceArray;
    }

    private function enrichRegionData($resource, array $resourceArray): array
    {
        if (empty($resource->continent_id) || !class_exists('Continent')) {
            return $resourceArray;
        }

        $continent = Continent::find($resource->continent_id);
        if ($continent) {
            $resourceArray['continent'] = [
                'id' => $continent->id,
                'name' => $continent->name,
                'code' => $continent->code,
            ];
        }

        return $resourceArray;
    }

    private function buildBlogPostQueryParts(
        string $search,
        ?int $languageId,
        ?string $languageCode,
        array $filters
    ): array {
        $whereConditions = [];
        $params = [];
        $joins = [];

        if ($languageId !== null && $languageId !== '') {
            $whereConditions[] = 'p.language_id = ?';
            $params[] = $languageId;
        } elseif ($languageCode !== null && $languageCode !== '' && class_exists('Language')) {
            $language = Language::findByCode($languageCode);
            if ($language) {
                $whereConditions[] = 'p.language_id = ?';
                $params[] = $language->id;
            }
        }

        if (isset($filters['author_id']) && $filters['author_id'] !== null && $filters['author_id'] !== '') {
            $whereConditions[] = 'p.author_id = ?';
            $params[] = (int) $filters['author_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $whereConditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }

        $hasCategoryFilter = isset($filters['category_id'])
            && $filters['category_id'] !== null
            && $filters['category_id'] !== '';
        if ($hasCategoryFilter) {
            $joins[] = 'INNER JOIN blog_post_categories bpc ON p.id = bpc.blog_post_id';
            $whereConditions[] = 'bpc.blog_category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        if (isset($filters['created_at_from']) && $filters['created_at_from'] !== null && $filters['created_at_from'] !== '') {
            $whereConditions[] = 'DATE(p.created_at) >= DATE(?)';
            $params[] = $filters['created_at_from'] . ' 00:00:00';
        }

        if (isset($filters['created_at_to']) && $filters['created_at_to'] !== null && $filters['created_at_to'] !== '') {
            $whereConditions[] = 'DATE(p.created_at) <= DATE(?)';
            $params[] = $filters['created_at_to'] . ' 23:59:59';
        }

        if (!empty($search)) {
            $whereConditions[] = '(p.title LIKE ? OR p.slug LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
            $searchParam = "%{$search}%";
            array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
        }

        return [
            !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '',
            !empty($joins) ? implode(' ', $joins) : '',
            $params,
            $hasCategoryFilter,
        ];
    }

    private function loadBlogPostCategories(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $categoryResults = Database::select(
            "SELECT bpc.blog_post_id, bc.id, bc.name, bc.slug
             FROM blog_post_categories bpc
             INNER JOIN blog_categories bc ON bpc.blog_category_id = bc.id
             WHERE bpc.blog_post_id IN ({$placeholders})
             ORDER BY bc.name ASC",
            $postIds
        );

        $categoriesMap = [];
        foreach ($categoryResults as $catRow) {
            $postId = $catRow['blog_post_id'];
            if (!isset($categoriesMap[$postId])) {
                $categoriesMap[$postId] = [];
            }

            $categoriesMap[$postId][] = [
                'id' => $catRow['id'],
                'name' => $catRow['name'],
                'slug' => $catRow['slug'],
            ];
        }

        return $categoriesMap;
    }

    private function getCategoryFilterOptions(?int $languageId): array
    {
        if (!class_exists('BlogCategory')) {
            throw new RuntimeException('BlogCategory class not found');
        }

        $options = [];
        foreach (BlogCategory::all() as $category) {
            if ($languageId && $category->language_id != $languageId) {
                continue;
            }

            $options[] = [
                'value' => $category->id,
                'label' => $category->name,
            ];
        }

        usort($options, static fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $options;
    }

    private function getRegionFilterOptions(?int $continentId): array
    {
        if (!class_exists('Region')) {
            return [];
        }

        $query = Region::query();
        if ($continentId) {
            $query->where('continent_id', $continentId);
        }

        $options = [];
        foreach ($query->orderBy('name', 'asc')->get() as $region) {
            $options[] = [
                'value' => $region->id,
                'label' => $region->name,
                'code' => $region->code ?? '',
            ];
        }

        return $options;
    }
}


if (!\class_exists('DashboardApiQueryService', false) && !\interface_exists('DashboardApiQueryService', false) && !\trait_exists('DashboardApiQueryService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardApiQueryService', 'DashboardApiQueryService');
}
