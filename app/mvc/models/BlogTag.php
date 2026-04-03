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
        'language_id'
    ];

    protected array $casts = [
        'language_id' => 'int',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    /**
     * Find tag by slug
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::findByField('slug', $slug);
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
        return BlogPost::query()
            ->whereIn('id', $ids)
            ->get();
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


if (!\class_exists('BlogTag', false) && !\interface_exists('BlogTag', false) && !\trait_exists('BlogTag', false)) {
    \class_alias(__NAMESPACE__ . '\\BlogTag', 'BlogTag');
}
