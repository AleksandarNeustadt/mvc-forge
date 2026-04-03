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
 * Continent Model
 * 
 * Handles continent data operations
 */
class Continent extends Model
{
    protected string $table = 'continents';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'code',
        'name',
        'native_name',
        'sort_order',
        'is_active'
    ];

    protected array $casts = [
        'is_active' => 'bool',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Find continent by code
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
     * Get all active continents
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
     * Get regions for this continent
     */
    public function regions(): array
    {
        if (!class_exists('Region')) {
            return [];
        }
        
        $results = Region::query()
            ->where('continent_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        $instance = new Region();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get languages for this continent
     */
    public function languages(): array
    {
        if (!class_exists('Language')) {
            return [];
        }
        
        $results = Language::query()
            ->where('continent_id', $this->id)
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
     * Scope: Active continents
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}


if (!\class_exists('Continent', false) && !\interface_exists('Continent', false) && !\trait_exists('Continent', false)) {
    \class_alias(__NAMESPACE__ . '\\Continent', 'Continent');
}
