<?php

namespace App\Models;


use App\Core\database\Database;use BadMethodCallException;
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
 * Blog Category Model
 * 
 * Handles blog category data with hierarchical structure
 */
class BlogCategory extends Model
{
    protected string $table = 'blog_categories';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'meta_title',
        'meta_description',
        'sort_order',
        'language_id',
        'translation_group_id'
    ];

    protected array $casts = [
        'parent_id' => 'int',
        'sort_order' => 'int',
        'language_id' => 'int',
        'translation_group_id' => 'string',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    /**
     * Find category by slug
     */
    public static function findBySlug(string $slug, ?string $langCode = null): ?static
    {
        $query = static::query()->where('slug', $slug);

        // Filter by language
        if (class_exists(Language::class)) {
            $langCode = $langCode ?: current_lang();
            $language = Language::findByCode($langCode);
            if (!$language) {
                return null;
            }

            if ($language) {
                $query->where('language_id', $language->id);
            }
        }
        
        $row = $query->first();
        if (!$row) return null;
        
        $instance = new static();
        return $instance->newFromBuilder($row);
    }

    /**
     * Get parent category
     */
    public function parent(): ?static
    {
        if (!$this->parent_id) {
            return null;
        }
        
        return static::find($this->parent_id);
    }

    /**
     * Get child categories
     */
    public function children(): array
    {
        return static::query()
            ->where('parent_id', $this->id)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Get all descendants (recursive)
     */
    public function getAllChildren(): array
    {
        $children = $this->children();
        $allChildren = $children;
        
        foreach ($children as $child) {
            $allChildren = array_merge($allChildren, $child->getAllChildren());
        }
        
        return $allChildren;
    }

    /**
     * Get breadcrumbs (path from root)
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumbs, $category);
            $category = $category->parent();
        }
        
        return $breadcrumbs;
    }

    /**
     * Get depth level (0 = root)
     */
    public function getDepth(): int
    {
        $depth = 0;
        $category = $this->parent();
        
        while ($category) {
            $depth++;
            $category = $category->parent();
        }
        
        return $depth;
    }

    /**
     * Get posts in this category (through pivot table)
     */
    public function posts(): array
    {
        $postIds = Database::select(
            "SELECT blog_post_id FROM blog_post_categories WHERE blog_category_id = ?",
            [$this->id]
        );
        
        if (empty($postIds)) {
            return [];
        }
        
        $ids = array_column($postIds, 'blog_post_id');
        $query = BlogPost::query()->whereIn('id', $ids);

        if ($this->language_id !== null) {
            $query->where('language_id', (int) $this->language_id);
        } else {
            $query->whereNull('language_id');
        }

        return $query->get();
    }

    /**
     * Generate unique slug
     */
    protected static function generateUniqueSlug(string $base, ?int $excludeId = null, ?int $languageId = null): string
    {
        $slug = str_slug($base);
        $originalSlug = $slug;
        $counter = 1;

        $query = static::query()->where('slug', $slug);
        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        } else {
            $query->whereNull('language_id');
        }
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $query = static::query()->where('slug', $slug);
            if ($languageId !== null) {
                $query->where('language_id', $languageId);
            } else {
                $query->whereNull('language_id');
            }
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}


if (!\class_exists('BlogCategory', false) && !\interface_exists('BlogCategory', false) && !\trait_exists('BlogCategory', false)) {
    \class_alias(__NAMESPACE__ . '\\BlogCategory', 'BlogCategory');
}
