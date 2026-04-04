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
 * Blog Post Model
 * 
 * Handles blog post data operations
 */
class BlogPost extends Model
{
    protected string $table = 'blog_posts';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'views',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'language_id',
        'translation_group_id'
    ];

    protected array $casts = [
        'author_id' => 'int',
        'published_at' => 'int',
        'views' => 'int',
        'language_id' => 'int',
        'translation_group_id' => 'string',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    /**
     * Find post by slug
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
     * Get author (User)
     */
    public function author(): ?User
    {
        if (!$this->author_id) {
            return null;
        }
        
        return User::find($this->author_id);
    }

    /**
     * Get categories (many-to-many)
     */
    public function categories(): array
    {
        $categoryIds = Database::select(
            "SELECT blog_category_id FROM blog_post_categories WHERE blog_post_id = ?",
            [$this->id]
        );
        
        if (empty($categoryIds)) {
            return [];
        }
        
        $ids = array_column($categoryIds, 'blog_category_id');
        $query = BlogCategory::query()->whereIn('id', $ids);

        if ($this->language_id !== null) {
            $query->where('language_id', (int) $this->language_id);
        } else {
            $query->whereNull('language_id');
        }

        return $query->get();
    }

    /**
     * Get tags (many-to-many)
     */
    public function tags(): array
    {
        $tagIds = Database::select(
            "SELECT blog_tag_id FROM blog_post_tags WHERE blog_post_id = ?",
            [$this->id]
        );
        
        if (empty($tagIds)) {
            return [];
        }
        
        $ids = array_column($tagIds, 'blog_tag_id');
        $query = BlogTag::query()->whereIn('id', $ids);

        if ($this->language_id !== null) {
            $query->where('language_id', (int) $this->language_id);
        } else {
            $query->whereNull('language_id');
        }

        return $query->get();
    }

    /**
     * Attach category to post
     */
    public function attachCategory(int $categoryId): bool
    {
        try {
            // Check if already exists
            $exists = Database::select(
                "SELECT id FROM blog_post_categories WHERE blog_post_id = ? AND blog_category_id = ?",
                [$this->id, $categoryId]
            );
            
            if (!empty($exists)) {
                return true; // Already attached
            }
            
            Database::execute(
                "INSERT INTO blog_post_categories (blog_post_id, blog_category_id, created_at) VALUES (?, ?, ?)",
                [$this->id, $categoryId, time()]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Detach category from post
     */
    public function detachCategory(int $categoryId): bool
    {
        try {
            Database::execute(
                "DELETE FROM blog_post_categories WHERE blog_post_id = ? AND blog_category_id = ?",
                [$this->id, $categoryId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sync categories (replace all)
     */
    public function syncCategories(array $categoryIds): void
    {
        Database::transaction(function () use ($categoryIds): void {
            Database::execute("DELETE FROM blog_post_categories WHERE blog_post_id = ?", [$this->id]);

            $time = time();
            foreach ($categoryIds as $categoryId) {
                Database::execute(
                    "INSERT INTO blog_post_categories (blog_post_id, blog_category_id, created_at) VALUES (?, ?, ?)",
                    [$this->id, $categoryId, $time]
                );
            }
        });
    }

    /**
     * Attach tag to post
     */
    public function attachTag(int $tagId): bool
    {
        try {
            // Check if already exists
            $exists = Database::select(
                "SELECT id FROM blog_post_tags WHERE blog_post_id = ? AND blog_tag_id = ?",
                [$this->id, $tagId]
            );
            
            if (!empty($exists)) {
                return true; // Already attached
            }
            
            Database::execute(
                "INSERT INTO blog_post_tags (blog_post_id, blog_tag_id, created_at) VALUES (?, ?, ?)",
                [$this->id, $tagId, time()]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Detach tag from post
     */
    public function detachTag(int $tagId): bool
    {
        try {
            Database::execute(
                "DELETE FROM blog_post_tags WHERE blog_post_id = ? AND blog_tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sync tags (replace all)
     */
    public function syncTags(array $tagIds): void
    {
        Database::transaction(function () use ($tagIds): void {
            Database::execute("DELETE FROM blog_post_tags WHERE blog_post_id = ?", [$this->id]);

            $time = time();
            foreach ($tagIds as $tagId) {
                Database::execute(
                    "INSERT INTO blog_post_tags (blog_post_id, blog_tag_id, created_at) VALUES (?, ?, ?)",
                    [$this->id, $tagId, $time]
                );
            }
        });
    }

    /**
     * Scope: Published posts
     */
    public static function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', time());
    }

    /**
     * Scope: Draft posts
     */
    public static function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Increment views
     */
    public function incrementViews(): bool
    {
        $this->views = ($this->views ?? 0) + 1;
        return $this->save();
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


if (!\class_exists('BlogPost', false) && !\interface_exists('BlogPost', false) && !\trait_exists('BlogPost', false)) {
    \class_alias(__NAMESPACE__ . '\\BlogPost', 'BlogPost');
}
