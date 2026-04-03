<?php

namespace App\Models;


use App\Core\cache\Cache;
use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;
use App\Core\logging\Logger;
use App\Core\security\Security;use BadMethodCallException;
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
 * User Model
 * 
 * Handles user data operations using PDO/QueryBuilder
 */
class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'slug',
        'avatar',
        'newsletter',
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
        'last_login_at',
        'last_login_ip',
        'banned_at',
        'approved_at',
        'status',
        'deleted_at',
        'failed_login_attempts',
        'locked_until',
        'password_reset_token',
        'password_reset_expires_at'
    ];

    protected array $hidden = [
        'password_hash'
    ];

    protected array $casts = [
        'newsletter' => 'bool',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'banned_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Find user by email (excluding soft deleted)
     */
    public static function findByEmail(string $email): ?static
    {
        $query = static::query()->where('email', $email);
        
        // Only filter by deleted_at if column exists
        if (self::hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        
        $result = $query->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Find user by username (excluding soft deleted)
     */
    public static function findByUsername(string $username): ?static
    {
        $query = static::query()->where('username', $username);
        
        // Only filter by deleted_at if column exists
        if (self::hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        
        $result = $query->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Find user by slug
     */
    public static function findBySlug(string $slug): ?static
    {
        $result = static::query()
            ->where('slug', $slug)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Check if email exists
     */
    public static function emailExists(string $email): bool
    {
        return static::query()
            ->where('email', $email)
            ->exists();
    }

    /**
     * Check if username exists
     */
    public static function usernameExists(string $username): bool
    {
        return static::query()
            ->where('username', $username)
            ->exists();
    }

    /**
     * Check if slug exists
     */
    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = static::query()->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Create user with hashed password
     */
    public static function createUser(array $data): static
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = Security::hashPassword($data['password']);
            unset($data['password']);
        }

        // Generate slug if not provided
        if (!isset($data['slug']) && isset($data['username'])) {
            $data['slug'] = static::generateUniqueSlug($data['username']);
        }

        return static::create($data);
    }

    /**
     * Update user password with history check
     */
    public function updatePassword(string $password, bool $checkHistory = true): bool
    {
        // Check password history if enabled
        if ($checkHistory) {
            $passwordHash = Security::hashPassword($password);
            
            // Check if password was used in last 5 passwords
            $recentPasswords = $this->getRecentPasswordHashes(5);
            foreach ($recentPasswords as $oldHash) {
                if (Security::verifyPassword($password, $oldHash)) {
                    throw new Exception('You cannot reuse a recently used password. Please choose a different password.');
                }
            }
            
            // Save old password to history before updating
            if (!empty($this->password_hash)) {
                $this->savePasswordToHistory($this->password_hash);
            }
            
            $this->password_hash = $passwordHash;
        } else {
            $this->password_hash = Security::hashPassword($password);
        }
        
        return $this->save();
    }
    
    /**
     * Get recent password hashes from history
     */
    private function getRecentPasswordHashes(int $limit = 5): array
    {
        try {
            $tables = DatabaseBuilder::getTables();
            if (!in_array('password_history', $tables)) {
                return []; // Table doesn't exist yet
            }
            
            $results = Database::select(
                "SELECT password_hash FROM password_history 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?",
                [$this->id, $limit]
            );
            
            return array_column($results, 'password_hash');
        } catch (Exception $e) {
            Logger::error('Failed to get password history', ['user_id' => $this->id, 'error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Save password hash to history
     */
    public function savePasswordToHistory(string $passwordHash): void
    {
        if (empty($passwordHash)) {
            return; // No password to save
        }
        
        try {
            $tables = DatabaseBuilder::getTables();
            if (!in_array('password_history', $tables)) {
                return; // Table doesn't exist yet
            }
            
            // Keep only last 10 passwords per user
            $existingCount = Database::selectOne(
                "SELECT COUNT(*) as count FROM password_history WHERE user_id = ?",
                [$this->id]
            )['count'] ?? 0;
            
            if ($existingCount >= 10) {
                // Delete oldest password (MySQL doesn't support DELETE with LIMIT in subquery easily)
                $oldest = Database::selectOne(
                    "SELECT id FROM password_history 
                     WHERE user_id = ? 
                     ORDER BY created_at ASC 
                     LIMIT 1",
                    [$this->id]
                );
                
                if ($oldest) {
                    Database::execute(
                        "DELETE FROM password_history WHERE id = ?",
                        [$oldest['id']]
                    );
                }
            }
            
            // Insert new password hash
            Database::table('password_history')->insert([
                'user_id' => $this->id,
                'password_hash' => $passwordHash,
                'created_at' => time()
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to save password to history', ['user_id' => $this->id, 'error' => $e->getMessage()]);
            // Don't throw - password history is not critical
        }
    }

    /**
     * Update last login
     */
    public function updateLastLogin(string $ip): bool
    {
        $this->last_login_at = time(); // Integer timestamp
        $this->last_login_ip = $ip;
        return $this->save();
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return Security::verifyPassword($password, $this->password_hash ?? '');
    }

    /**
     * Generate unique slug
     */
    protected static function generateUniqueSlug(string $base): string
    {
        $slug = str_slug($base);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Check if user is banned
     */
    public function isBanned(): bool
    {
        return !empty($this->banned_at) || ($this->status ?? 'active') === 'banned';
    }

    /**
     * Check if user is approved
     */
    public function isApproved(): bool
    {
        return !empty($this->approved_at) || ($this->status ?? 'active') === 'active';
    }

    /**
     * Check if user is pending approval
     */
    public function isPending(): bool
    {
        return ($this->status ?? 'active') === 'pending';
    }

    /**
     * Ban user
     */
    public function ban(): bool
    {
        $this->banned_at = time();
        $this->status = 'banned';
        return $this->save();
    }

    /**
     * Unban user
     */
    public function unban(): bool
    {
        $this->banned_at = null;
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Check if account is locked
     */
    public function isLocked(): bool
    {
        if (!self::hasLockoutColumns()) {
            return false; // Columns don't exist yet, account is not locked
        }
        
        if (!isset($this->locked_until) || empty($this->locked_until)) {
            return false;
        }
        
        // Check if lock has expired
        if (is_numeric($this->locked_until)) {
            return $this->locked_until > time();
        }
        
        // If it's a datetime string, convert to timestamp
        $lockedUntil = is_string($this->locked_until) ? strtotime($this->locked_until) : $this->locked_until;
        return $lockedUntil > time();
    }
    
    /**
     * Check if account lockout columns exist
     */
    private static function hasLockoutColumns(): bool
    {
        static $hasColumns = null;
        
        if ($hasColumns !== null) {
            return $hasColumns;
        }
        
        try {
            $columns = DatabaseBuilder::getTableColumns('users');
            $columnNames = array_column($columns, 'name');
            $hasColumns = in_array('failed_login_attempts', $columnNames) && 
                         in_array('locked_until', $columnNames);
            return $hasColumns;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts(): bool
    {
        if (!self::hasLockoutColumns()) {
            return true; // Columns don't exist yet, skip
        }
        
        $failedAttempts = ($this->failed_login_attempts ?? 0) + 1;
        $this->failed_login_attempts = $failedAttempts;
        
        // Lock account after 5 failed attempts for 15 minutes
        if ($failedAttempts >= 5) {
            $this->locked_until = time() + (15 * 60); // 15 minutes
        }
        
        return $this->save();
    }
    
    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts(): bool
    {
        if (!self::hasLockoutColumns()) {
            return true; // Columns don't exist yet, skip
        }
        
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        return $this->save();
    }

    /**
     * Approve user
     */
    public function approve(): bool
    {
        $this->approved_at = time();
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Set user as pending
     */
    public function setPending(): bool
    {
        $this->status = 'pending';
        return $this->save();
    }

    /**
     * Verify user email and automatically approve user
     */
    public function verifyEmail(): bool
    {
        $this->email_verified_at = time();
        $this->email_verification_token = null;
        $this->email_verification_expires_at = null;
        
        // Automatically approve user when email is verified
        if ($this->isPending()) {
            $this->approved_at = time();
            $this->status = 'active';
        }
        
        return $this->save();
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->email_verified_at);
    }

    /**
     * Get all roles for this user (with caching)
     */
    public function roles(): array
    {
        // Try to use cache, but fallback to direct query if cache fails
        try {
            $cacheKey = "user_{$this->id}_roles";
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        } catch (Exception $e) {
            // Cache failed, continue with direct query
        }
        
        // Load from database
        $roleIds = Database::table('user_role')
            ->where('user_id', $this->id)
            ->pluck('role_id');
        
        if (empty($roleIds)) {
            return [];
        }
        
        $results = Role::query()
            ->whereIn('id', $roleIds)
            ->orderBy('priority', 'ASC')
            ->get();
        
        $roles = [];
        foreach ($results as $result) {
            $role = new Role();
            $roles[] = $role->newFromBuilder($result);
        }
        
        // Try to cache, but don't fail if it doesn't work
        try {
            Cache::set($cacheKey, $roles, 3600);
        } catch (Exception $e) {
            // Cache failed, but we have the data so continue
        }
        
        return $roles;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleSlug): bool
    {
        $role = Role::findBySlug($roleSlug);
        
        if (!$role) {
            return false;
        }
        
        return Database::table('user_role')
            ->where('user_id', $this->id)
            ->where('role_id', $role->id)
            ->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $roleSlug) {
            if ($this->hasRole($roleSlug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $roleSlug) {
            if (!$this->hasRole($roleSlug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific permission
     * Checks through all user's roles
     */
    public function hasPermission(string $permissionSlug): bool
    {
        $roles = $this->roles();
        
        foreach ($roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if ($this->hasPermission($permissionSlug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if (!$this->hasPermission($permissionSlug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Attach role to user
     */
    public function attachRole(int|Role $role): void
    {
        $roleId = $role instanceof Role ? $role->id : $role;
        
        // Check if already attached
        $exists = Database::table('user_role')
            ->where('user_id', $this->id)
            ->where('role_id', $roleId)
            ->exists();
        
        if (!$exists) {
            $now = date('Y-m-d H:i:s');
            Database::table('user_role')->insert([
                'user_id' => $this->id,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }

    /**
     * Detach role from user
     */
    public function detachRole(int|Role $role): void
    {
        $roleId = $role instanceof Role ? $role->id : $role;
        
        Database::table('user_role')
            ->where('user_id', $this->id)
            ->where('role_id', $roleId)
            ->delete();
    }

    /**
     * Sync roles (replace all roles with given ones)
     */
    public function syncRoles(array $roleIds): void
    {
        Database::transaction(function () use ($roleIds): void {
            Database::table('user_role')
                ->where('user_id', $this->id)
                ->delete();

            $now = date('Y-m-d H:i:s');
            foreach ($roleIds as $roleId) {
                Database::table('user_role')->insert([
                    'user_id' => $this->id,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        });
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    /**
     * Soft delete user (override parent delete method)
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Soft delete - set deleted_at timestamp
        $this->deleted_at = time();
        $result = $this->save();
        
        // Clear cache
        Cache::forget("user_{$this->id}_roles");
        
        return $result;
    }

    /**
     * Force delete user (permanently remove)
     */
    public function forceDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = Database::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        if ($result) {
            $this->exists = false;
            Cache::forget("user_{$this->id}_roles");
        }

        return $result > 0;
    }

    /**
     * Restore soft deleted user
     */
    public function restore(): bool
    {
        if (!$this->exists || empty($this->deleted_at)) {
            return false;
        }

        $this->deleted_at = null;
        return $this->save();
    }

    /**
     * Check if user is soft deleted
     */
    public function isDeleted(): bool
    {
        return !empty($this->deleted_at);
    }

    /**
     * Override find to exclude soft deleted users
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static();
        $query = static::query()->where($instance->primaryKey, $id);
        
        // Only filter by deleted_at if column exists (backward compatibility)
        if (self::hasDeletedAtColumn()) {
            $query->whereNull('deleted_at');
        }
        
        $result = $query->first();

        if (!$result) {
            return null;
        }

        return $instance->newFromBuilder($result);
    }
    
    /**
     * Check if deleted_at column exists in users table
     */
    private static function hasDeletedAtColumn(): bool
    {
        static $hasColumn = null;
        
        if ($hasColumn !== null) {
            return $hasColumn;
        }
        
        try {
            $driver = Database::getDriver();
            if ($driver === 'mysql') {
                $columns = Database::select("SHOW COLUMNS FROM users LIKE 'deleted_at'");
                $hasColumn = !empty($columns);
            } elseif ($driver === 'pgsql') {
                $columns = Database::select("
                    SELECT column_name 
                    FROM information_schema.columns 
                    WHERE table_name = 'users' AND column_name = 'deleted_at'
                ");
                $hasColumn = !empty($columns);
            } else {
                $hasColumn = false;
            }
        } catch (Exception $e) {
            $hasColumn = false;
        }
        
        return $hasColumn;
    }
}


if (!\class_exists('User', false) && !\interface_exists('User', false) && !\trait_exists('User', false)) {
    \class_alias(__NAMESPACE__ . '\\User', 'User');
}
