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
 * Region Model
 * 
 * Handles region data operations
 */
class Region extends Model
{
    protected string $table = 'regions';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'continent_id',
        'code',
        'name',
        'native_name',
        'description',
        'sort_order',
        'is_active'
    ];

    protected array $casts = [
        'continent_id' => 'int',
        'is_active' => 'bool',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Find region by code
     */
    public static function findByCode(string $code): ?static
    {
        $result = static::query()
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Get all active regions
     */
    public static function getActive(): array
    {
        $results = static::query()
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        $instance = new static();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get regions by continent
     */
    public static function getByContinent(int $continentId): array
    {
        $results = static::query()
            ->where('continent_id', $continentId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        $instance = new static();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get continent for this region
     */
    public function continent(): ?Continent
    {
        if (!$this->continent_id) {
            return null;
        }
        
        if (!class_exists('Continent')) {
            return null;
        }
        
        return Continent::find($this->continent_id);
    }

    /**
     * Get languages for this region
     */
    public function languages(): array
    {
        if (!class_exists('Language')) {
            return [];
        }
        
        $results = Language::query()
            ->where('region_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        $instance = new Language();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Scope: Active regions
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: By continent
     */
    public static function scopeByContinent($query, int $continentId)
    {
        return $query->where('continent_id', $continentId);
    }
}


if (!\class_exists('Region', false) && !\interface_exists('Region', false) && !\trait_exists('Region', false)) {
    \class_alias(__NAMESPACE__ . '\\Region', 'Region');
}
