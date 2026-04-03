<?php

namespace App\Core\services;


use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Language;
use App\Models\NavigationMenu;
use App\Models\Page;
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
 * Service layer for transforming API resources into stable response payloads.
 */
class ApiResponseFormatterService
{
    public function formatPage(Page $page): array
    {
        $language = $page->language_id ? Language::find($page->language_id) : null;

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'route' => $page->route,
            'page_type' => $page->page_type,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'is_active' => (bool) $page->is_active,
            'is_in_menu' => (bool) $page->is_in_menu,
            'menu_order' => (int) $page->menu_order,
            'parent_page_id' => $page->parent_page_id,
            'navbar_id' => $page->navbar_id,
            'language' => $this->formatLanguageSummary($language),
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ];
    }

    public function formatMenu(NavigationMenu $menu): array
    {
        $language = $menu->language_id ? Language::find($menu->language_id) : null;

        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'position' => $menu->position,
            'is_active' => (bool) $menu->is_active,
            'menu_order' => (int) $menu->menu_order,
            'language' => $this->formatLanguageSummary($language),
            'created_at' => $menu->created_at,
            'updated_at' => $menu->updated_at,
        ];
    }

    public function formatPost(BlogPost $post): array
    {
        $language = $post->language_id ? Language::find($post->language_id) : null;
        $author = $post->author_id ? User::find($post->author_id) : null;

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image' => $post->featured_image,
            'status' => $post->status,
            'published_at' => $post->published_at,
            'views' => (int) $post->views,
            'author' => $author ? [
                'id' => $author->id,
                'username' => $author->username,
                'email' => $author->email,
            ] : null,
            'categories' => array_map(
                static fn($category): array => is_array($category)
                    ? [
                        'id' => $category['id'] ?? null,
                        'name' => $category['name'] ?? '',
                        'slug' => $category['slug'] ?? '',
                    ]
                    : [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ],
                $post->categories()
            ),
            'tags' => array_map(
                static fn($tag): array => is_array($tag)
                    ? [
                        'id' => $tag['id'] ?? null,
                        'name' => $tag['name'] ?? '',
                        'slug' => $tag['slug'] ?? '',
                    ]
                    : [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ],
                $post->tags()
            ),
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'meta_keywords' => $post->meta_keywords,
            'language' => $this->formatLanguageSummary($language),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];
    }

    public function formatCategory(BlogCategory $category): array
    {
        $language = $category->language_id ? Language::find($category->language_id) : null;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'image' => $category->image,
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'sort_order' => (int) $category->sort_order,
            'language' => $this->formatLanguageSummary($language),
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }

    public function formatTag(BlogTag $tag): array
    {
        $language = $tag->language_id ? Language::find($tag->language_id) : null;

        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => $tag->description,
            'language' => $this->formatLanguageSummary($language),
            'created_at' => $tag->created_at,
            'updated_at' => $tag->updated_at,
        ];
    }

    public function formatLanguage(Language $language): array
    {
        return [
            'id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'native_name' => $language->native_name,
            'flag' => $language->flag,
            'is_active' => (bool) $language->is_active,
            'is_default' => (bool) $language->is_default,
            'sort_order' => (int) $language->sort_order,
            'created_at' => $language->created_at,
            'updated_at' => $language->updated_at,
        ];
    }

    private function formatLanguageSummary(?Language $language): ?array
    {
        return $language ? [
            'id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
        ] : null;
    }
}


if (!\class_exists('ApiResponseFormatterService', false) && !\interface_exists('ApiResponseFormatterService', false) && !\trait_exists('ApiResponseFormatterService', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiResponseFormatterService', 'ApiResponseFormatterService');
}
