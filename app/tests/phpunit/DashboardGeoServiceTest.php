<?php

namespace Tests\PhpUnit;

use App\Core\services\DashboardGeoService;
use App\Models\Continent;
use App\Models\Region;
use PHPUnit\Framework\TestCase;

final class DashboardGeoServiceTest extends TestCase
{
    public function testApplyContinentDataNormalizesCodeFlagsAndSortOrder(): void
    {
        $service = new DashboardGeoService();
        $continent = new Continent();

        $service->applyContinentData($continent, [
            'code' => '  EU ',
            'name' => 'Europe',
            'native_name' => 'Evropa',
            'is_active' => '1',
            'sort_order' => '12',
        ]);

        self::assertSame('eu', $continent->code);
        self::assertSame('Europe', $continent->name);
        self::assertSame('Evropa', $continent->native_name);
        self::assertTrue((bool) $continent->is_active);
        self::assertSame(12, $continent->sort_order);
    }

    public function testApplyRegionDataNormalizesForeignKeyCodeAndFlags(): void
    {
        $service = new DashboardGeoService();
        $region = new Region();

        $service->applyRegionData($region, [
            'continent_id' => '7',
            'name' => 'Balkan',
            'code' => ' BA ',
            'native_name' => 'Balkan',
            'description' => 'Regional test',
            'is_active' => '',
            'sort_order' => '3',
        ]);

        self::assertSame(7, $region->continent_id);
        self::assertSame('Balkan', $region->name);
        self::assertSame('ba', $region->code);
        self::assertSame('Balkan', $region->native_name);
        self::assertSame('Regional test', $region->description);
        self::assertFalse((bool) $region->is_active);
        self::assertSame(3, $region->sort_order);
    }

    public function testBuildContinentEditDataPreservesModelId(): void
    {
        $service = new DashboardGeoService();
        $continent = new Continent([
            'code' => 'eu',
            'name' => 'Europe',
            'native_name' => 'Evropa',
            'is_active' => 1,
            'sort_order' => 10,
        ]);
        $continent->id = 42;

        $formData = $service->buildContinentEditData($continent);

        self::assertSame(42, $formData['continent']['id']);
        self::assertSame('eu', $formData['continent']['code']);
        self::assertSame('Europe', $formData['continent']['name']);
    }
}
