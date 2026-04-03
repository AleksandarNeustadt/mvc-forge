<?php

namespace App\Models;

use BadMethodCallException;
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
 * Page Model
 * 
 * Handles page data operations for Page Manager system
 */
class Page extends Model
{
    protected string $table = 'pages';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'title',
        'slug',
        'route',
        'page_type',
        'application',
        'display_options',
        'content',
        'template',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
        'is_in_menu',
        'menu_order',
        'parent_page_id',
        'navbar_id',
        'blog_post_id',
        'blog_category_id',
        'blog_tag_id',
        'language_id'
    ];

    protected array $casts = [
        'is_active' => 'bool',
        'is_in_menu' => 'bool',
        'menu_order' => 'int',
        'parent_page_id' => 'int',
        'navbar_id' => 'int',
        'blog_post_id' => 'int',
        'blog_category_id' => 'int',
        'blog_tag_id' => 'int',
        'language_id' => 'int',
        'display_options' => 'json',  // JSON string to array
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Find page by slug
     */
    public static function findBySlug(string $slug, ?string $langCode = null): ?static
    {
        $query = static::query()->where('slug', $slug);
        
        // Filter by language
        if (class_exists('Language')) {
            $langCode = $langCode ?: current_lang();
            $language = Language::findByCode($langCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $row = $query->where('is_active', 1)->first();
        if (!$row) return null;
        
        $instance = new static();
        return $instance->newFromBuilder($row);
    }

    /**
     * Find page by route
     */
    public static function findByRoute(string $route, ?string $langCode = null): ?static
    {
        $query = static::query()->where('route', $route);
        
        // Filter by language
        if (class_exists('Language')) {
            $langCode = $langCode ?: current_lang();
            $language = Language::findByCode($langCode);
            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $row = $query->where('is_active', 1)->first();
        if (!$row) return null;
        
        $instance = new static();
        return $instance->newFromBuilder($row);
    }

    /**
     * Get parent page
     */
    public function parentPage(): ?static
    {
        if (!$this->parent_page_id) {
            return null;
        }
        
        return static::find($this->parent_page_id);
    }

    /**
     * Get child pages
     */
    public function childPages(): array
    {
        return static::query()
            ->where('parent_page_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('menu_order', 'asc')
            ->get();
    }

    /**
     * Get blog post (if page_type is 'blog_post')
     */
    public function blogPost(): ?BlogPost
    {
        if ($this->page_type !== 'blog_post' || !$this->blog_post_id) {
            return null;
        }
        
        return BlogPost::find($this->blog_post_id);
    }

    /**
     * Get blog category (if page_type is 'blog_category')
     */
    public function blogCategory(): ?BlogCategory
    {
        if ($this->page_type !== 'blog_category' || !$this->blog_category_id) {
            return null;
        }
        
        return BlogCategory::find($this->blog_category_id);
    }

    /**
     * Get blog tag (if page_type is 'blog_tag')
     */
    public function blogTag(): ?BlogTag
    {
        if ($this->page_type !== 'blog_tag' || !$this->blog_tag_id) {
            return null;
        }
        
        return BlogTag::find($this->blog_tag_id);
    }

    /**
     * Scope: Active pages
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Pages in menu
     */
    public static function scopeInMenu($query)
    {
        return $query->where('is_in_menu', 1)->where('is_active', 1);
    }

    /**
     * Scope: Ordered by menu_order
     */
    public static function scopeOrdered($query)
    {
        return $query->orderBy('menu_order', 'asc');
    }

    /**
     * Get menu items (pages that should appear in navigation)
     * 
     * @param int|null $navbarId Optional navbar ID to filter by
     * @return array Array of Page model instances, ordered by menu_order
     */
    public static function getMenuItems(?int $navbarId = null): array
    {
        $query = static::query()
            ->where('is_in_menu', 1)
            ->where('is_active', 1);
        
        if ($navbarId !== null) {
            $query->where('navbar_id', $navbarId);
        }
        
        $menuPages = $query->orderBy('menu_order', 'asc')->get();
        
        return $menuPages;
    }

    /**
     * Get navigation menu for this page
     * 
     * @return NavigationMenu|null
     */
    public function navigationMenu(): ?NavigationMenu
    {
        if (!$this->navbar_id) {
            return null;
        }
        
        return NavigationMenu::find($this->navbar_id);
    }

    /**
     * Check if route exists (excluding current page)
     */
    public static function routeExists(string $route, ?int $excludeId = null): bool
    {
        $query = static::query()->where('route', $route);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Generate unique slug
     */
    protected static function generateUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = str_slug($base);
        $originalSlug = $slug;
        $counter = 1;

        $query = static::query()->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $query = static::query()->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}


if (!\class_exists('Page', false) && !\interface_exists('Page', false) && !\trait_exists('Page', false)) {
    \class_alias(__NAMESPACE__ . '\\Page', 'Page');
}
