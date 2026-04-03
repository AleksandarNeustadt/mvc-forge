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
 * Role Model
 * 
 * Handles role data operations
 */
class Role extends Model
{
    protected string $table = 'roles';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'priority'
    ];
    
    protected array $casts = [
        'is_system' => 'bool',
        'priority' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all permissions for this role
     */
    public function permissions(): array
    {
        $permissionIds = Database::table('role_permission')
            ->where('role_id', $this->id)
            ->pluck('permission_id');
        
        if (empty($permissionIds)) {
            return [];
        }
        
        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->get();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        $permissionResult = Permission::query()
            ->where('slug', $permissionSlug)
            ->first();
        
        if (!$permissionResult) {
            return false;
        }
        
        // Convert array to Permission object if needed
        $permission = is_array($permissionResult) 
            ? (new Permission())->newFromBuilder($permissionResult)
            : $permissionResult;
        
        $exists = Database::table('role_permission')
            ->where('role_id', $this->id)
            ->where('permission_id', $permission->id)
            ->exists();
        
        return $exists;
    }

    /**
     * Attach permission to role
     */
    public function attachPermission(int|Permission $permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : $permission;
        
        // Check if already attached
        $exists = Database::table('role_permission')
            ->where('role_id', $this->id)
            ->where('permission_id', $permissionId)
            ->exists();
        
        if (!$exists) {
            Database::table('role_permission')->insert([
                'role_id' => $this->id,
                'permission_id' => $permissionId,
                'created_at' => time(),
                'updated_at' => time()
            ]);
        }
    }

    /**
     * Detach permission from role
     */
    public function detachPermission(int|Permission $permission): void
    {
        $permissionId = $permission instanceof Permission ? $permission->id : $permission;
        
        Database::table('role_permission')
            ->where('role_id', $this->id)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    /**
     * Sync permissions (replace all permissions with given ones)
     */
    public function syncPermissions(array $permissionIds): void
    {
        Database::transaction(function () use ($permissionIds): void {
            Database::table('role_permission')
                ->where('role_id', $this->id)
                ->delete();

            foreach ($permissionIds as $permissionId) {
                Database::table('role_permission')->insert([
                    'role_id' => $this->id,
                    'permission_id' => $permissionId,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
        });
    }

    /**
     * Get all users with this role
     */
    public function users(): array
    {
        $userIds = Database::table('user_role')
            ->where('role_id', $this->id)
            ->pluck('user_id');
        
        if (empty($userIds)) {
            return [];
        }
        
        return User::query()
            ->whereIn('id', $userIds)
            ->get();
    }

    /**
     * Find role by slug
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::findByField('slug', $slug);
    }
}


if (!\class_exists('Role', false) && !\interface_exists('Role', false) && !\trait_exists('Role', false)) {
    \class_alias(__NAMESPACE__ . '\\Role', 'Role');
}
