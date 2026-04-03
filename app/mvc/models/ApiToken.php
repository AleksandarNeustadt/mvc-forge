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
 * API Token Model
 * 
 * Handles API token data operations
 */
class ApiToken extends Model
{
    protected string $table = 'api_tokens';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'user_id',
        'token',
        'name',
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    protected array $casts = [
        'user_id' => 'int',
        'last_used_at' => 'int',
        'expires_at' => 'int',
        'created_at' => 'int',
        'updated_at' => 'int'
    ];

    protected bool $timestamps = false;

    /**
     * Find token by token string
     */
    public static function findByToken(string $token): ?static
    {
        $result = static::query()
            ->where('token', $token)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // Never expires
        }
        
        return $this->expires_at < time();
    }

    /**
     * Check if token is valid (not expired)
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): bool
    {
        $this->last_used_at = time();
        $this->updated_at = time();

        return $this->save();
    }

    /**
     * Get user associated with this token
     */
    public function user(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        
        return User::find($this->user_id);
    }

    /**
     * Generate a new API token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Create a new API token for user
     */
    public static function createToken(int $userId, ?string $name = null, ?int $expiresIn = null): static
    {
        $token = static::generateToken();
        $expiresAt = $expiresIn ? (time() + $expiresIn) : null;
        $now = time();
        
        $apiToken = new static();
        $apiToken->user_id = $userId;
        $apiToken->token = $token;
        $apiToken->name = $name;
        $apiToken->expires_at = $expiresAt;
        $apiToken->created_at = $now;
        $apiToken->updated_at = $now;
        $apiToken->save();
        
        return $apiToken;
    }

    /**
     * Revoke token (delete it)
     */
    public function revoke(): bool
    {
        return $this->delete();
    }

    /**
     * Revoke all tokens for a user
     */
    public static function revokeAllForUser(int $userId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->delete();
    }
}


if (!\class_exists('ApiToken', false) && !\interface_exists('ApiToken', false) && !\trait_exists('ApiToken', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiToken', 'ApiToken');
}
