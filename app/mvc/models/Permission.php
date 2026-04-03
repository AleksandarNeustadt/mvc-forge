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
 * Permission Model
 * 
 * Handles permission data operations
 */
class Permission extends Model
{
    protected string $table = 'permissions';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'category'
    ];
    
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all roles that have this permission
     */
    public function roles(): array
    {
        $roleIds = Database::table('role_permission')
            ->where('permission_id', $this->id)
            ->pluck('role_id');
        
        if (empty($roleIds)) {
            return [];
        }
        
        return Role::query()
            ->whereIn('id', $roleIds)
            ->get();
    }

    /**
     * Find permission by slug
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::findByField('slug', $slug);
    }

    /**
     * Get permissions grouped by category
     */
    public static function groupedByCategory(): array
    {
        $permissions = static::all();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $category = $permission->category ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }
}


if (!\class_exists('Permission', false) && !\interface_exists('Permission', false) && !\trait_exists('Permission', false)) {
    \class_alias(__NAMESPACE__ . '\\Permission', 'Permission');
}
