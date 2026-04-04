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
 * Blog Tag Model
 * 
 * Handles blog tag data operations
 */
class BlogTag extends Model
{
    protected string $table = 'blog_tags';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'language_id',
        'translation_group_id'
    ];

    protected array $casts = [
        'language_id' => 'int',
        'translation_group_id' => 'string',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    /**
     * Find tag by slug
     */
    public static function findBySlug(string $slug, ?string $langCode = null): ?static
    {
        $query = static::query()->where('slug', $slug);

        if (class_exists(Language::class)) {
            $langCode = $langCode ?: current_lang();
            $language = Language::findByCode($langCode);
            if (!$language) {
                return null;
            }

            $query->where('language_id', $language->id);
        }

        $row = $query->first();
        if (!$row) {
            return null;
        }

        $instance = new static();
        return $instance->newFromBuilder($row);
    }

    /**
     * Get posts with this tag (many-to-many)
     */
    public function posts(): array
    {
        $postIds = Database::select(
            "SELECT blog_post_id FROM blog_post_tags WHERE blog_tag_id = ?",
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


if (!\class_exists('BlogTag', false) && !\interface_exists('BlogTag', false) && !\trait_exists('BlogTag', false)) {
    \class_alias(__NAMESPACE__ . '\\BlogTag', 'BlogTag');
}
