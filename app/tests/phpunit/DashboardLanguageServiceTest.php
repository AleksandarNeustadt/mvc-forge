<?php

namespace Tests\PhpUnit;

use App\Core\services\DashboardLanguageService;
use App\Models\Language;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DashboardLanguageServiceTest extends TestCase
{
    public function testBuildEditFormDataIncludesLanguageAndGeoOptions(): void
    {
        $service = new class extends DashboardLanguageService {
            public function buildGeoFormData(): array
            {
                return [
                    'continents' => ['' => '-- Select Continent --', 1 => 'Europe'],
                    'regions' => ['' => '-- Select Region --', 5 => 'Balkan'],
                    'regionsByContinent' => [1 => [['id' => 5, 'name' => 'Balkan']]],
                ];
            }
        };

        $language = new Language([
            'code' => 'sr',
            'name' => 'Serbian',
            'native_name' => 'Srpski',
            'flag' => 'rs',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 10,
        ]);
        $language->id = 11;

        $formData = $service->buildEditFormData($language);

        self::assertSame(11, $formData['language']['id']);
        self::assertSame('sr', $formData['language']['code']);
        self::assertSame('Europe', $formData['continents'][1]);
        self::assertSame('Balkan', $formData['regions'][5]);
        self::assertSame([['id' => 5, 'name' => 'Balkan']], $formData['regionsByContinent'][1]);
    }

    public function testValidateDeletionRejectsDefaultLanguageWithoutDatabaseLookup(): void
    {
        $service = new DashboardLanguageService();
        $language = new Language(['is_default' => 1]);
        $language->id = 1;

        self::assertSame(
            ['general' => ['Cannot delete default language']],
            $service->validateDeletion($language)
        );
    }

    public function testDeleteLanguageThrowsForDefaultLanguage(): void
    {
        $service = new DashboardLanguageService();
        $language = new Language(['is_default' => 1]);
        $language->id = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete default language');

        $service->deleteLanguage($language);
    }
}
