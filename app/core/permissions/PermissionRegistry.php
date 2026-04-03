<?php

namespace App\Core\permissions;


use App\Models\Permission;use BadMethodCallException;
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
 * Permission Registry
 * 
 * Centralized system for registering and managing permissions
 * Permissions can be registered here and will be automatically created/updated in database
 */
class PermissionRegistry
{
    private static array $permissions = [];
    private static bool $loaded = false;

    /**
     * Register a permission
     * 
     * @param string $slug Permission slug (e.g., 'users.view')
     * @param string $name Permission name (e.g., 'View Users')
     * @param string $description Permission description
     * @param string $category Permission category (e.g., 'users', 'blog', 'pages', 'system')
     */
    public static function register(
        string $slug,
        string $name,
        string $description = '',
        string $category = 'other'
    ): void {
        self::$permissions[$slug] = [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'category' => $category
        ];
    }

    /**
     * Register multiple permissions at once
     * 
     * @param array $permissions Array of permission definitions
     */
    public static function registerMany(array $permissions): void
    {
        foreach ($permissions as $permission) {
            self::register(
                $permission['slug'],
                $permission['name'],
                $permission['description'] ?? '',
                $permission['category'] ?? 'other'
            );
        }
    }

    /**
     * Get all registered permissions
     */
    public static function all(): array
    {
        return self::$permissions;
    }

    /**
     * Sync registered permissions to database
     * Creates missing permissions and updates existing ones
     */
    public static function sync(): void
    {
        if (empty(self::$permissions)) {
            return;
        }

        foreach (self::$permissions as $permission) {
            try {
                $existing = Permission::query()
                    ->where('slug', $permission['slug'])
                    ->first();

                if ($existing) {
                    // Update existing permission (only if changed)
                    $permissionModel = new Permission();
                    $permissionModel->newFromBuilder($existing);
                    
                    $needsUpdate = false;
                    $updateData = [];
                    
                    if ($permissionModel->name !== $permission['name']) {
                        $updateData['name'] = $permission['name'];
                        $needsUpdate = true;
                    }
                    if ($permissionModel->description !== $permission['description']) {
                        $updateData['description'] = $permission['description'];
                        $needsUpdate = true;
                    }
                    if ($permissionModel->category !== $permission['category']) {
                        $updateData['category'] = $permission['category'];
                        $needsUpdate = true;
                    }
                    
                    if ($needsUpdate) {
                        $permissionModel->update($updateData);
                    }
                } else {
                    // Create new permission only if it doesn't exist
                    Permission::create($permission);
                }
            } catch (Exception $e) {
                // Skip if permission already exists (might have unique constraint on name)
                error_log("Permission sync warning for '{$permission['slug']}': " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Load default permissions
     * This should be called during application bootstrap
     */
    public static function loadDefaults(): void
    {
        if (self::$loaded) {
            return;
        }

        // User Management Permissions
        self::registerMany([
            ['slug' => 'users.view', 'name' => 'View Users', 'description' => 'View user list and details', 'category' => 'users'],
            ['slug' => 'users.create', 'name' => 'Create Users', 'description' => 'Create new users', 'category' => 'users'],
            ['slug' => 'users.edit', 'name' => 'Edit Users', 'description' => 'Edit existing users', 'category' => 'users'],
            ['slug' => 'users.delete', 'name' => 'Delete Users', 'description' => 'Delete users', 'category' => 'users'],
            ['slug' => 'users.manage-roles', 'name' => 'Manage User Roles', 'description' => 'Assign and manage user roles', 'category' => 'users'],
            ['slug' => 'users.manage-permissions', 'name' => 'Manage Permissions', 'description' => 'Manage role permissions', 'category' => 'users'],
        ]);

        // Blog Management Permissions
        self::registerMany([
            ['slug' => 'blog.view', 'name' => 'View Blog Posts', 'description' => 'View blog posts', 'category' => 'blog'],
            ['slug' => 'blog.create', 'name' => 'Create Blog Posts', 'description' => 'Create new blog posts', 'category' => 'blog'],
            ['slug' => 'blog.edit', 'name' => 'Edit Blog Posts', 'description' => 'Edit blog posts', 'category' => 'blog'],
            ['slug' => 'blog.delete', 'name' => 'Delete Blog Posts', 'description' => 'Delete blog posts', 'category' => 'blog'],
            ['slug' => 'blog.publish', 'name' => 'Publish Blog Posts', 'description' => 'Publish blog posts', 'category' => 'blog'],
            ['slug' => 'blog.manage-categories', 'name' => 'Manage Blog Categories', 'description' => 'Manage blog categories', 'category' => 'blog'],
            ['slug' => 'blog.manage-tags', 'name' => 'Manage Blog Tags', 'description' => 'Manage blog tags', 'category' => 'blog'],
        ]);

        // Page Management Permissions
        self::registerMany([
            ['slug' => 'pages.view', 'name' => 'View Pages', 'description' => 'View pages', 'category' => 'pages'],
            ['slug' => 'pages.create', 'name' => 'Create Pages', 'description' => 'Create new pages', 'category' => 'pages'],
            ['slug' => 'pages.edit', 'name' => 'Edit Pages', 'description' => 'Edit pages', 'category' => 'pages'],
            ['slug' => 'pages.delete', 'name' => 'Delete Pages', 'description' => 'Delete pages', 'category' => 'pages'],
            ['slug' => 'pages.publish', 'name' => 'Publish Pages', 'description' => 'Publish pages', 'category' => 'pages'],
        ]);

        // System Management Permissions
        self::registerMany([
            ['slug' => 'system.dashboard', 'name' => 'Access Dashboard', 'description' => 'Access dashboard', 'category' => 'system'],
            ['slug' => 'system.database', 'name' => 'Manage Database', 'description' => 'Manage database', 'category' => 'system'],
            ['slug' => 'system.settings', 'name' => 'Manage Settings', 'description' => 'Manage system settings', 'category' => 'system'],
            ['slug' => 'system.languages', 'name' => 'Manage Languages', 'description' => 'Manage site languages', 'category' => 'system'],
        ]);

        // Contact Management Permissions
        self::registerMany([
            ['slug' => 'contact.view', 'name' => 'View Contact Messages', 'description' => 'View contact form submissions', 'category' => 'contact'],
            ['slug' => 'contact.manage', 'name' => 'Manage Contact Messages', 'description' => 'Manage contact messages (read, reply, delete)', 'category' => 'contact'],
            ['slug' => 'contact.submit', 'name' => 'Submit Contact Form', 'description' => 'Submit contact form (requires authentication)', 'category' => 'contact'],
        ]);

        self::$loaded = true;
    }
}


if (!\class_exists('PermissionRegistry', false) && !\interface_exists('PermissionRegistry', false) && !\trait_exists('PermissionRegistry', false)) {
    \class_alias(__NAMESPACE__ . '\\PermissionRegistry', 'PermissionRegistry');
}
