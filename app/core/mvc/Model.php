<?php

namespace App\Core\mvc;


use App\Core\database\Database;
use App\Core\database\QueryBuilder;use BadMethodCallException;
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
 * Base Model Class
 * 
 * Eloquent-style ORM for working with database tables
 * 
 * Usage:
 *   class User extends Model {
 *       protected $table = 'users';
 *       protected $fillable = ['name', 'email'];
 *   }
 *   
 *   $user = User::find(1);
 *   $user->name = 'John';
 *   $user->save();
 */
class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    /**
     * Create new model instance
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        if (!empty($this->table)) {
            return $this->table;
        }
        
        $className = static::class;
        $basename = basename(str_replace('\\', '/', $className));
        return strtolower($basename) . 's';
    }

    /**
     * Get fillable attributes
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Fill model attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable)) {
                // Convert boolean to integer for database storage
                if (is_bool($value) && isset($this->casts[$key]) && $this->casts[$key] === 'bool') {
                    $this->attributes[$key] = $value ? 1 : 0;
                } else {
                    $this->attributes[$key] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Magic getter
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if attribute exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $array = $this->attributes;

        // Hide fields
        foreach ($this->hidden as $field) {
            unset($array[$field]);
        }

        // Apply casts
        foreach ($this->casts as $field => $type) {
            if (isset($array[$field])) {
                $array[$field] = $this->castAttribute($array[$field], $type);
            }
        }

        return $array;
    }

    /**
     * Cast attribute to type
     */
    protected function castAttribute(mixed $value, string $type): mixed
    {
        // Don't cast null values
        if ($value === null) {
            return null;
        }
        
        return match($type) {
            'int', 'integer' => $this->castToInt($value),
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'object' => is_string($value) ? json_decode($value) : $value,
            'date', 'datetime' => is_string($value) ? strtotime($value) : $value,
            default => $value,
        };
    }
    
    /**
     * Cast value to integer, handling TIMESTAMP strings
     */
    protected function castToInt(mixed $value): int
    {
        // If already an integer, return as-is
        if (is_int($value)) {
            return $value;
        }
        
        // If it's a numeric string, cast directly
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        // If it's a TIMESTAMP string (MySQL format: 'YYYY-MM-DD HH:MM:SS'), convert to Unix timestamp
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2}/', $value)) {
            $timestamp = strtotime($value);
            return $timestamp !== false ? $timestamp : 0;
        }
        
        // Fallback: try to cast to int
        return (int) $value;
    }

    /**
     * Get query builder for this model
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();
        return Database::table($instance->getTable());
    }

    /**
     * Find model by ID
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static();
        $result = static::query()
            ->where($instance->primaryKey, $id)
            ->first();

        if (!$result) {
            return null;
        }

        return $instance->newFromBuilder($result);
    }

    /**
     * Find or fail
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if (!$model) {
            throw new Exception("Model not found: " . static::class . " with ID {$id}");
        }

        return $model;
    }

    /**
     * Find first model by a single column value.
     */
    public static function findByField(string $field, mixed $value): ?static
    {
        $result = static::query()
            ->where($field, $value)
            ->first();

        if (!$result) {
            return null;
        }

        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Check whether a model exists by a single column value.
     */
    public static function existsByField(string $field, mixed $value, int|string|null $excludeId = null): bool
    {
        $instance = new static();
        $query = static::query()->where($field, $value);

        if ($excludeId !== null) {
            $query->where($instance->primaryKey, '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get all records
     */
    public static function all(): array
    {
        $results = static::query()->get();
        $instance = new static();

        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get first record
     */
    public static function first(): ?static
    {
        $result = static::query()->first();

        if (!$result) {
            return null;
        }

        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Create new model from database result
     */
    public function newFromBuilder(array $attributes): static
    {
        $model = new static();
        
        // Set all attributes directly (don't filter by fillable when loading from DB)
        foreach ($attributes as $key => $value) {
            $model->attributes[$key] = $value;
        }
        
        $model->original = $attributes;
        $model->exists = true;

        return $model;
    }

    /**
     * Save model
     */
    public function save(): bool
    {
        if ($this->timestamps) {
            // Check if timestamps should be integers (Unix timestamps) or strings (datetime)
            // If created_at/updated_at are cast as 'int', use time(), otherwise use date()
            $createdAtType = isset($this->casts) && isset($this->casts[$this->createdAt]) 
                ? $this->casts[$this->createdAt] 
                : null;
            
            if ($createdAtType === 'int') {
                $now = time();
            } else {
                $now = date('Y-m-d H:i:s');
            }

            if (!$this->exists) {
                $this->attributes[$this->createdAt] = $now;
            }

            $this->attributes[$this->updatedAt] = $now;
        }

        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }

    /**
     * Perform insert
     */
    protected function performInsert(): bool
    {
        $table = $this->getTable();
        $attributes = $this->getAttributesForSave();

        $result = Database::table($table)->insert($attributes);

        if ($result) {
            $this->exists = true;
            $this->attributes[$this->primaryKey] = Database::lastInsertId();
            $this->original = $this->attributes;
        }

        return $result;
    }

    /**
     * Perform update
     */
    protected function performUpdate(): bool
    {
        $table = $this->getTable();
        $attributes = $this->getAttributesForSave();

        // Only update changed attributes
        $dirty = $this->getDirty($attributes);
        
        if (empty($dirty)) {
            return true; // Nothing to update
        }

        $primaryKeyValue = $this->attributes[$this->primaryKey] ?? null;
        
        if (!$primaryKeyValue) {
            throw new Exception("Cannot update model: primary key '{$this->primaryKey}' is missing");
        }
        
        $result = Database::table($table)
            ->where($this->primaryKey, $primaryKeyValue)
            ->update($dirty);

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result > 0;
    }

    /**
     * Delete model
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $result = Database::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        if ($result) {
            $this->exists = false;
        }

        return $result > 0;
    }

    /**
     * Get attributes for save (only fillable)
     */
    protected function getAttributesForSave(): array
    {
        $attributes = empty($this->fillable) 
            ? $this->attributes 
            : array_intersect_key($this->attributes, array_flip($this->fillable));
        
        // Convert values based on casts
        foreach ($attributes as $key => $value) {
            // Convert boolean values to integers for database
            if (is_bool($value)) {
                $attributes[$key] = $value ? 1 : 0;
            }
            // Convert arrays/objects to JSON string for 'json' cast fields
            elseif (isset($this->casts[$key]) && $this->casts[$key] === 'json') {
                if (is_array($value) || is_object($value)) {
                    $attributes[$key] = json_encode($value);
                } elseif ($value === null) {
                    $attributes[$key] = null;
                }
                // If it's already a string, leave it as is (might be already JSON encoded)
            }
            // Convert empty strings to null for integer cast fields (for nullable columns)
            elseif (isset($this->casts[$key]) && $this->casts[$key] === 'int') {
                // For nullable foreign keys (ending with _id), convert empty string or '0' to null
                if (str_ends_with($key, '_id') || str_ends_with($key, '_page_id')) {
                    if ($value === '' || $value === '0' || (is_string($value) && trim($value) === '')) {
                        $attributes[$key] = null;
                    } elseif ($value !== null) {
                        $attributes[$key] = (int) $value;
                    }
                }
            }
        }
        
        // Additional pass: convert empty strings to null for fields that might be nullable integers
        // Check if field ends with '_id' which are typically nullable foreign keys
        foreach ($attributes as $key => $value) {
            if (is_string($value) && $value === '' && (str_ends_with($key, '_id') || str_ends_with($key, '_page_id'))) {
                $attributes[$key] = null;
            }
            // Also convert string '0' to null for nullable foreign keys
            if (is_string($value) && $value === '0' && (str_ends_with($key, '_id') || str_ends_with($key, '_page_id'))) {
                $attributes[$key] = null;
            }
        }
        
        return $attributes;
    }

    /**
     * Get dirty (changed) attributes
     */
    protected function getDirty(array $attributes): array
    {
        $dirty = [];

        foreach ($attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Create new model instance
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Update model
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Get attribute value
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Check if model exists
     */
    public function exists(): bool
    {
        return $this->exists;
    }

}


if (!\class_exists('Model', false) && !\interface_exists('Model', false) && !\trait_exists('Model', false)) {
    \class_alias(__NAMESPACE__ . '\\Model', 'Model');
}
