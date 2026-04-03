<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Core\routing\DynamicRouteRegistry;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Page;use BadMethodCallException;
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
 * Service layer for dashboard page-management business rules.
 */
class DashboardPageService
{
    private const PAGE_TYPE_TO_STORAGE = [
        'single_post' => 'blog_post',
        'category' => 'blog_category',
        'tag' => 'blog_tag',
        'list' => 'blog_list',
    ];

    private const PAGE_TYPE_FROM_STORAGE = [
        'blog_post' => 'single_post',
        'blog_category' => 'category',
        'blog_tag' => 'tag',
        'blog_list' => 'list',
    ];

    public function getPageList(): array
    {
        $pagesData = Database::select(
            "SELECT p.*, l.code as language_code, l.name as language_name, l.flag as language_flag
             FROM pages p
             LEFT JOIN languages l ON p.language_id = l.id
             ORDER BY p.menu_order ASC, p.created_at DESC"
        );

        $pages = [];
        foreach ($pagesData as $pageData) {
            $pages[] = [
                ...$pageData,
                'language' => $pageData['language_code'] ? [
                    'code' => $pageData['language_code'],
                    'name' => $pageData['language_name'],
                    'flag' => $pageData['language_flag'],
                ] : null,
            ];
        }

        return $pages;
    }

    /**
     * Build shared form data for page create/edit screens.
     */
    public function buildPageFormData(?int $excludePageId = null): array
    {
        return array_merge(
            [
                'parentPages' => $this->buildParentPageOptions($excludePageId),
                'navigationMenus' => $this->buildNavigationMenuOptions(),
            ],
            $this->buildBlogResourceOptions(),
            $this->buildLanguageOptions()
        );
    }

    public function normalizeRoute(string $routeInput, string $slug, string $application = ''): string
    {
        if ($application === 'contact') {
            return '/contact';
        }

        $routeSource = trim($routeInput) === '' ? $slug : $routeInput;

        return '/' . ltrim($routeSource, '/');
    }

    public function normalizeOptionalId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    public function validateRouteAndSlugUniqueness(string $route, string $slug, ?int $excludeId = null): array
    {
        $errors = [];
        $allPages = Page::all();

        foreach ($allPages as $page) {
            if ($excludeId !== null && $page->id === $excludeId) {
                continue;
            }

            if ($page->route === $route) {
                $errors['route'] = ['A page with this route already exists'];
            }

            if ($page->slug === $slug) {
                $errors['slug'] = ['A page with this slug already exists'];
            }

            if (!empty($errors)) {
                break;
            }
        }

        return $errors;
    }

    public function validatePageInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];
        $application = (string) ($input['application'] ?? '');

        if ($application === 'blog' && trim((string) ($input['page_type'] ?? '')) === '') {
            $errors['page_type'] = array_merge(
                $errors['page_type'] ?? [],
                ['The page_type field is required']
            );
        }

        $slug = (string) ($input['slug'] ?? '');
        $route = $this->normalizeRoute((string) ($input['route'] ?? ''), $slug, $application);
        $uniquenessErrors = $this->validateRouteAndSlugUniqueness($route, $slug, $excludeId);

        foreach ($uniquenessErrors as $field => $fieldErrors) {
            $errors[$field] = array_merge($errors[$field] ?? [], $fieldErrors);
        }

        return empty($errors) ? true : $errors;
    }

    public function mapPageTypeForStorage(string $pageType): string
    {
        return self::PAGE_TYPE_TO_STORAGE[$pageType] ?? $pageType;
    }

    public function buildDisplayOptions(string $application, string $pageType, array $input): ?array
    {
        if ($application === 'homepage') {
            $blogSliderPostsIds = [];
            $blogSliderPosts = $input['homepage_blog_slider_posts'] ?? [];
            if (is_array($blogSliderPosts)) {
                foreach ($blogSliderPosts as $postId) {
                    if (!empty($postId) && is_numeric($postId)) {
                        $blogSliderPostsIds[] = (int) $postId;
                    }
                }
            }

            return [
                'enable_blog_slider' => !empty($input['homepage_enable_blog_slider']),
                'blog_slider_posts' => $blogSliderPostsIds,
                'enable_login_form' => !empty($input['homepage_enable_login_form']),
                'enable_contact_form' => !empty($input['homepage_enable_contact_form']),
            ];
        }

        if (in_array($pageType, ['category', 'tag', 'list'], true)) {
            $displayStyleValue = 'list';
            if (!empty($input['display_style'])) {
                $displayStyleValue = $input['display_style'];
            } elseif (!empty($input['display_style_visual'])) {
                $displayStyleValue = $input['display_style_visual'];
            }

            return [
                'style' => $displayStyleValue,
                'grid_columns' => (int) ($input['grid_columns'] ?? 3),
                'posts_per_page' => (int) ($input['posts_per_page'] ?? 10),
                'show_excerpt' => !empty($input['show_excerpt']),
                'show_featured_image' => !empty($input['show_featured_image']),
            ];
        }

        return null;
    }

    public function applyBlogAssociations(Page $page, string $pageType, array $input): void
    {
        $page->blog_post_id = null;
        $page->blog_category_id = null;
        $page->blog_tag_id = null;

        if ($pageType === 'single_post') {
            $page->blog_post_id = $this->normalizeOptionalId($input['blog_post_id'] ?? null);
        } elseif ($pageType === 'category') {
            $page->blog_category_id = $this->normalizeOptionalId($input['blog_category_id'] ?? null);
        } elseif ($pageType === 'tag') {
            $page->blog_tag_id = $this->normalizeOptionalId($input['blog_tag_id'] ?? null);
        }
    }

    public function preparePageForEdit(Page $page): array
    {
        $pageArray = $page->toArray();

        if (($pageArray['application'] ?? '') === 'blog' && isset($pageArray['page_type'])) {
            $pageArray['page_type'] = self::PAGE_TYPE_FROM_STORAGE[$pageArray['page_type']]
                ?? $pageArray['page_type'];
        }

        $displayOptions = null;
        if (isset($pageArray['display_options'])) {
            if (is_string($pageArray['display_options'])) {
                $displayOptions = json_decode($pageArray['display_options'], true);
            } elseif (is_array($pageArray['display_options'])) {
                $displayOptions = $pageArray['display_options'];
            }
        }

        if (is_array($displayOptions) && ($pageArray['application'] ?? '') === 'blog') {
            $pageArray['display_style'] = $displayOptions['style'] ?? 'list';
            $pageArray['grid_columns'] = $displayOptions['grid_columns'] ?? 3;
            $pageArray['posts_per_page'] = $displayOptions['posts_per_page'] ?? 10;
            $pageArray['show_excerpt'] = $displayOptions['show_excerpt'] ?? false;
            $pageArray['show_featured_image'] = $displayOptions['show_featured_image'] ?? false;
        }

        return $pageArray;
    }

    public function savePage(Page $page, array $input): Page
    {
        $application = (string) ($input['application'] ?? '');
        $pageType = (string) ($input['page_type'] ?? '');
        $slug = (string) ($input['slug'] ?? '');

        $page->title = (string) ($input['title'] ?? '');
        $page->slug = $slug;
        $page->route = $this->normalizeRoute(
            (string) ($input['route'] ?? ''),
            $slug,
            $application
        );
        $page->application = $application ?: null;
        $page->page_type = $this->mapPageTypeForStorage($pageType);
        $page->content = (string) ($input['content'] ?? '');
        $page->template = (string) ($input['template'] ?? 'default');
        $page->meta_title = (string) ($input['meta_title'] ?? '');
        $page->meta_description = (string) ($input['meta_description'] ?? '');
        $page->meta_keywords = (string) ($input['meta_keywords'] ?? '');
        $page->is_active = !empty($input['is_active']);
        $page->is_in_menu = !empty($input['is_in_menu']);
        $page->menu_order = (int) ($input['menu_order'] ?? 0);
        $page->parent_page_id = $this->normalizeOptionalId($input['parent_page_id'] ?? null);
        $page->navbar_id = $this->normalizeOptionalId($input['navbar_id'] ?? null);
        $page->language_id = $this->normalizeOptionalId($input['language_id'] ?? null);
        $page->display_options = $this->buildDisplayOptions($application, $pageType, $input);

        $this->applyBlogAssociations($page, $pageType, $input);

        if (!$page->save()) {
            throw new RuntimeException('Failed to save page to database');
        }

        DynamicRouteRegistry::clearCache();

        return $page;
    }

    public function deletePage(Page $page): void
    {
        $page->delete();
        DynamicRouteRegistry::clearCache();
    }

    private function buildParentPageOptions(?int $excludePageId = null): array
    {
        $parentPages = [];
        foreach (Page::all() as $page) {
            if ($excludePageId !== null && $page->id === $excludePageId) {
                continue;
            }

            $parentPages[$page->id] = $page->title;
        }

        return $parentPages;
    }

    private function buildBlogResourceOptions(): array
    {
        return [
            'blogPosts' => array_map(fn($post) => $post->toArray(), BlogPost::all()),
            'blogCategories' => array_map(fn($category) => $category->toArray(), BlogCategory::all()),
            'blogTags' => array_map(fn($tag) => $tag->toArray(), BlogTag::all()),
        ];
    }

    private function buildNavigationMenuOptions(): array
    {
        if (!class_exists('NavigationMenu')) {
            return [];
        }

        $navigationMenus = [];
        foreach (NavigationMenu::getActive() as $menu) {
            $navigationMenus[$menu->id] = $menu->name . ' (' . $menu->position . ')';
        }

        return $navigationMenus;
    }

    private function buildLanguageOptions(): array
    {
        $languages = [];
        $languagesData = [];

        if (!class_exists('Language')) {
            return [
                'languages' => $languages,
                'languagesData' => $languagesData,
            ];
        }

        $languages[''] = '-- Select Language --';
        foreach (Language::getActive() as $language) {
            $languageArray = $language->toArray();
            $languages[$language->id] = $language->name . ' (' . $language->native_name . ')';
            $languagesData[$language->id] = [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'flag_code' => get_flag_code(strtolower($language->code ?? ''), $languageArray),
            ];
        }

        return [
            'languages' => $languages,
            'languagesData' => $languagesData,
        ];
    }
}


if (!\class_exists('DashboardPageService', false) && !\interface_exists('DashboardPageService', false) && !\trait_exists('DashboardPageService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardPageService', 'DashboardPageService');
}
