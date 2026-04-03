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
 * Language Model
 * 
 * Handles language data operations
 */
class Language extends Model
{
    protected string $table = 'languages';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'country_code',
        'continent_id',
        'region_id',
        'is_active',
        'is_site_language',
        'is_default',
        'sort_order'
    ];

    protected array $casts = [
        'continent_id' => 'int',
        'region_id' => 'int',
        'is_active' => 'bool',
        'is_site_language' => 'bool',
        'is_default' => 'bool',
        'sort_order' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Find language by code
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
     * Get default language
     */
    public static function getDefault(): ?static
    {
        $result = static::query()
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        $instance = new static();
        return $instance->newFromBuilder($result);
    }

    /**
     * Get all active languages
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
     * Alias for getActive() (legacy name used in some views).
     */
    public static function allActive(): array
    {
        return static::getActive();
    }

    /**
     * Set as default language (and unset others)
     */
    public function setAsDefault(): bool
    {
        try {
            // Unset all other defaults
            Database::execute("UPDATE languages SET is_default = 0 WHERE id != ?", [$this->id]);
            
            // Set this as default
            $this->is_default = true;
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Scope: Active languages
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Default language
     */
    public static function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

    /**
     * Get continent for this language
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
     * Get region for this language
     */
    public function region(): ?Region
    {
        if (!$this->region_id) {
            return null;
        }
        
        if (!class_exists('Region')) {
            return null;
        }
        
        return Region::find($this->region_id);
    }

    /**
     * Scope: By continent
     */
    public static function scopeByContinent($query, int $continentId)
    {
        return $query->where('continent_id', $continentId);
    }

    /**
     * Scope: By region
     */
    public static function scopeByRegion($query, int $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}


if (!\class_exists('Language', false) && !\interface_exists('Language', false) && !\trait_exists('Language', false)) {
    \class_alias(__NAMESPACE__ . '\\Language', 'Language');
}
