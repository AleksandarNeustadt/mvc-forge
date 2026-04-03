<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\Language;
use App\Models\NavigationMenu;use BadMethodCallException;
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
 * Service layer for dashboard navigation-menu management.
 */
class DashboardNavigationService
{
    public function getMenuList(): array
    {
        $menusData = Database::select(
            "SELECT nm.*, l.code as language_code, l.name as language_name, l.flag as language_flag,
                    (SELECT COUNT(*) FROM pages WHERE navbar_id = nm.id) as page_count
             FROM navigation_menus nm
             LEFT JOIN languages l ON nm.language_id = l.id
             ORDER BY nm.menu_order ASC"
        );

        $menus = [];
        foreach ($menusData as $menuData) {
            $menuData['page_count'] = (int) ($menuData['page_count'] ?? 0);
            $menuData['language'] = $menuData['language_code'] ? [
                'code' => $menuData['language_code'],
                'name' => $menuData['language_name'],
                'flag' => $menuData['language_flag'],
            ] : null;
            $menus[] = $menuData;
        }

        return $menus;
    }

    public function buildLanguageOptions(): array
    {
        if (!class_exists('Language')) {
            return [
                'languages' => [],
                'languagesData' => [],
            ];
        }

        $languages = ['' => '-- Select Language --'];
        $languagesData = [];

        foreach (Language::getActive() as $language) {
            $languageArray = $language->toArray();
            $languages[$language->id] = $language->name . ' (' . $language->native_name . ')';
            $languagesData[$language->id] = [
                'code' => $language->code,
                'name' => $language->name,
                'native_name' => $language->native_name,
                'flag_code' => get_flag_code(strtolower($language->code ?? ''), $languageArray),
            ];
        }

        return [
            'languages' => $languages,
            'languagesData' => $languagesData,
        ];
    }

    public function applyMenuData(NavigationMenu $menu, array $input): void
    {
        $menu->name = $input['name'] ?? '';
        $menu->position = $input['position'] ?? '';
        $menu->is_active = !empty($input['is_active']);
        $menu->menu_order = (int) ($input['menu_order'] ?? 0);

        $languageId = $input['language_id'] ?? null;
        $menu->language_id = ($languageId !== null && $languageId !== '') ? (int) $languageId : null;
    }

    public function buildEditFormData(NavigationMenu $menu): array
    {
        $menuArray = $menu->toArray();
        if (!isset($menuArray['id']) && isset($menu->id)) {
            $menuArray['id'] = $menu->id;
        }

        return array_merge(
            ['navigationMenu' => $menuArray],
            $this->buildLanguageOptions()
        );
    }

    public function saveMenu(NavigationMenu $menu, array $input): NavigationMenu
    {
        $this->applyMenuData($menu, $input);
        $menu->save();

        return $menu;
    }

    public function deleteMenu(NavigationMenu $menu): void
    {
        Database::transaction(function () use ($menu): void {
            $this->detachPagesFromMenu((int) $menu->id);
            $menu->delete();
        });
    }

    public function detachPagesFromMenu(int $menuId): void
    {
        Database::execute("UPDATE pages SET navbar_id = NULL WHERE navbar_id = ?", [$menuId]);
    }
}


if (!\class_exists('DashboardNavigationService', false) && !\interface_exists('DashboardNavigationService', false) && !\trait_exists('DashboardNavigationService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardNavigationService', 'DashboardNavigationService');
}
