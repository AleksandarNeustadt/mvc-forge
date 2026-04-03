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
 * Navigation Menu Model
 * 
 * Handles navigation menu data operations
 */
class NavigationMenu extends Model
{
    protected string $table = 'navigation_menus';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'position',
        'is_active',
        'menu_order',
        'language_id'
    ];

    protected array $casts = [
        'is_active' => 'bool',
        'menu_order' => 'int',
        'language_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get pages assigned to this navigation menu
     * 
     * @return array Array of Page model instances
     */
    public function pages(): array
    {
        $results = Page::query()
            ->where('navbar_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('menu_order', 'asc')
            ->get();
        
        $instance = new Page();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get menu items (pages) for this navigation menu
     * 
     * @return array Array of Page model instances, ordered by menu_order
     */
    public function getMenuItems(): array
    {
        $results = Page::query()
            ->where('navbar_id', $this->id)
            ->where('is_active', 1)
            ->where('is_in_menu', 1)
            ->orderBy('menu_order', 'asc')
            ->get();
        
        $instance = new Page();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Build menu item list for views (route + title).
     *
     * @param self $menu
     * @return array{items: list<array{url: string, title: string}>}
     */
    private static function menuToViewArray(self $menu): array
    {
        $items = [];
        foreach ($menu->getMenuItems() as $menuPage) {
            $items[] = [
                'url' => $menuPage->route ?? '',
                'title' => $menuPage->title ?? '',
            ];
        }
        return ['items' => $items];
    }

    /**
     * Get a menu by display name for the current language code (e.g. "sr").
     * Used by layout.php. If no row matches the name, falls back to the first
     * active "header" menu for that language (same idea as components/header.php).
     *
     * @return array{items: list<array{url: string, title: string}>}|null
     */
    public static function getByName(string $name, ?string $langCode = null): ?array
    {
        $languageId = null;
        if ($langCode !== null && $langCode !== '' && class_exists('Language')) {
            $lang = Language::findByCode($langCode);
            if ($lang) {
                $languageId = $lang->id;
            }
        }

        $menu = null;
        $query = static::query()
            ->where('name', $name)
            ->where('is_active', 1);
        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        }
        $row = $query->first();
        if ($row) {
            $instance = new static();
            $menu = $instance->newFromBuilder($row);
        } elseif ($languageId !== null) {
            $headerMenus = static::getByPosition('header', $languageId);
            $menu = $headerMenus[0] ?? null;
        }

        return $menu ? static::menuToViewArray($menu) : null;
    }

    /**
     * Get navigation menus by position
     * 
     * @param string $position Position name (e.g., 'header', 'footer')
     * @param int|null $languageId Optional language ID to filter by
     * @return array Array of NavigationMenu model instances
     */
    public static function getByPosition(string $position, ?int $languageId = null): array
    {
        $query = static::query()
            ->where('position', $position)
            ->where('is_active', 1);
        
        // Filter by language if provided
        if ($languageId !== null) {
            $query->where('language_id', $languageId);
        }
        
        $results = $query->orderBy('menu_order', 'asc')->get();
        
        $instance = new static();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Get all active navigation menus
     * 
     * @return array Array of NavigationMenu model instances
     */
    public static function getActive(): array
    {
        $results = static::query()
            ->where('is_active', 1)
            ->orderBy('position', 'asc')
            ->orderBy('menu_order', 'asc')
            ->get();
        
        $instance = new static();
        return array_map(function($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $results);
    }

    /**
     * Scope: Active menus
     */
    public static function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: By position
     */
    public static function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }
}


if (!\class_exists('NavigationMenu', false) && !\interface_exists('NavigationMenu', false) && !\trait_exists('NavigationMenu', false)) {
    \class_alias(__NAMESPACE__ . '\\NavigationMenu', 'NavigationMenu');
}
