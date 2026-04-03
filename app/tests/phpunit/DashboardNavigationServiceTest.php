<?php

namespace Tests\PhpUnit;

use App\Core\services\DashboardNavigationService;
use App\Models\NavigationMenu;
use PHPUnit\Framework\TestCase;

final class DashboardNavigationServiceTest extends TestCase
{
    public function testApplyMenuDataNormalizesBooleansIntegersAndNullableLanguage(): void
    {
        $service = new DashboardNavigationService();
        $menu = new NavigationMenu();

        $service->applyMenuData($menu, [
            'name' => 'Main menu',
            'position' => 'header',
            'is_active' => '1',
            'menu_order' => '7',
            'language_id' => '12',
        ]);

        self::assertSame('Main menu', $menu->name);
        self::assertSame('header', $menu->position);
        self::assertTrue((bool) $menu->is_active);
        self::assertSame(7, $menu->menu_order);
        self::assertSame(12, $menu->language_id);

        $service->applyMenuData($menu, [
            'name' => 'Footer menu',
            'position' => 'footer',
            'is_active' => '',
            'menu_order' => '',
            'language_id' => '',
        ]);

        self::assertSame('Footer menu', $menu->name);
        self::assertSame('footer', $menu->position);
        self::assertFalse((bool) $menu->is_active);
        self::assertSame(0, $menu->menu_order);
        self::assertNull($menu->language_id);
    }

    public function testBuildEditFormDataIncludesMenuIdAndLanguageOptions(): void
    {
        $service = new class extends DashboardNavigationService {
            public function buildLanguageOptions(): array
            {
                return [
                    'languages' => ['' => '-- Select Language --', 1 => 'Serbian (Srpski)'],
                    'languagesData' => [1 => ['code' => 'sr', 'name' => 'Serbian', 'native_name' => 'Srpski', 'flag_code' => 'rs']],
                ];
            }
        };

        $menu = new NavigationMenu([
            'name' => 'Header',
            'position' => 'header',
            'is_active' => 1,
            'menu_order' => 1,
            'language_id' => 1,
        ]);
        $menu->id = 33;

        $formData = $service->buildEditFormData($menu);

        self::assertSame(33, $formData['navigationMenu']['id']);
        self::assertSame('Header', $formData['navigationMenu']['name']);
        self::assertSame('Serbian (Srpski)', $formData['languages'][1]);
        self::assertSame('sr', $formData['languagesData'][1]['code']);
    }
}
