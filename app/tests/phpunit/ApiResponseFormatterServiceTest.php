<?php

namespace Tests\PhpUnit;

use App\Core\services\ApiResponseFormatterService;
use App\Models\Language;
use PHPUnit\Framework\TestCase;

final class ApiResponseFormatterServiceTest extends TestCase
{
    public function testFormatLanguageReturnsStablePayload(): void
    {
        $language = new Language([
            'code' => 'sr',
            'name' => 'Serbian',
            'native_name' => 'Srpski',
            'flag' => 'rs',
            'is_active' => 1,
            'is_default' => 1,
            'sort_order' => 10,
        ]);
        $language->id = 7;
        $language->created_at = '2026-01-01 12:00:00';
        $language->updated_at = '2026-01-02 12:00:00';

        $payload = (new ApiResponseFormatterService())->formatLanguage($language);

        self::assertSame([
            'id' => 7,
            'code' => 'sr',
            'name' => 'Serbian',
            'native_name' => 'Srpski',
            'flag' => 'rs',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 10,
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-01-02 12:00:00',
        ], $payload);
    }
}
