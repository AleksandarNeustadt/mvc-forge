<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\Continent;
use App\Models\Language;
use App\Models\Region;use BadMethodCallException;
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
 * Service layer for dashboard language management.
 */
class DashboardLanguageService
{
    public function getLanguageList(): array
    {
        $languages = [];

        foreach (Language::all() as $language) {
            $languageArray = $language->toArray();
            $languageArray['content_count'] = $this->countLanguageContent((int) $language->id);
            $languages[] = $languageArray;
        }

        return $languages;
    }

    public function buildGeoFormData(): array
    {
        $continents = [];
        $regions = [];
        $regionsByContinent = [];

        if (class_exists('Continent')) {
            $continents[''] = '-- Select Continent --';
            foreach (Continent::getActive() as $continent) {
                $continents[$continent->id] = $continent->name;

                if (class_exists('Region')) {
                    $regionsByContinent[$continent->id] = array_map(
                        fn($region) => ['id' => $region->id, 'name' => $region->name],
                        Region::getByContinent($continent->id)
                    );
                }
            }
        }

        if (class_exists('Region')) {
            $regions[''] = '-- Select Region --';
            foreach (Region::getActive() as $region) {
                $regions[$region->id] = $region->name;
            }
        }

        return [
            'continents' => $continents,
            'regions' => $regions,
            'regionsByContinent' => $regionsByContinent,
        ];
    }

    public function buildEditFormData(Language $language): array
    {
        $languageArray = $language->toArray();
        if (!isset($languageArray['id']) && isset($language->id)) {
            $languageArray['id'] = $language->id;
        }

        return array_merge(
            ['language' => $languageArray],
            $this->buildGeoFormData()
        );
    }

    public function validateLanguageInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];
        $code = strtolower(trim((string) ($input['code'] ?? '')));

        if ($code !== '' && $this->codeExists($code, $excludeId)) {
            $errors['code'] = array_merge(
                $errors['code'] ?? [],
                ['Language code already exists']
            );
        }

        return empty($errors) ? true : $errors;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = Language::query()->where('code', strtolower(trim($code)));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function applyLanguageData(Language $language, array $input): bool
    {
        $language->code = strtolower(trim($input['code'] ?? ''));
        $language->name = $input['name'] ?? '';
        $language->native_name = $input['native_name'] ?? '';
        $language->flag = $input['flag'] ?? '';
        $language->continent_id = !empty($input['continent_id']) ? (int) $input['continent_id'] : null;
        $language->region_id = !empty($input['region_id']) ? (int) $input['region_id'] : null;
        $language->is_active = !empty($input['is_active']);
        $wasDefault = (bool) $language->is_default;
        $language->is_default = !empty($input['is_default']);
        $language->sort_order = (int) ($input['sort_order'] ?? 0);

        if ($language->is_default && !$wasDefault) {
            if (!empty($language->id)) {
                Database::execute("UPDATE languages SET is_default = 0 WHERE id != ?", [$language->id]);
            } else {
                Database::execute("UPDATE languages SET is_default = 0");
            }
        }

        return $language->save();
    }

    public function saveLanguage(Language $language, array $input): Language
    {
        if (!$this->applyLanguageData($language, $input)) {
            throw new RuntimeException('Failed to save language');
        }

        return $language;
    }

    public function validateDeletion(Language $language): array
    {
        if ($language->is_default) {
            return ['general' => ['Cannot delete default language']];
        }

        if ($this->countLanguageContent((int) $language->id) > 0) {
            return [
                'general' => ['Cannot delete language that has associated content. Please remove or reassign content first.']
            ];
        }

        return [];
    }

    public function deleteLanguage(Language $language): void
    {
        $deletionErrors = $this->validateDeletion($language);
        if (!empty($deletionErrors)) {
            throw new InvalidArgumentException(
                $deletionErrors['general'][0] ?? 'Cannot delete language'
            );
        }

        $language->delete();
    }

    public function setDefaultLanguage(Language $language): Language
    {
        $language->setAsDefault();

        return $language;
    }

    private function countLanguageContent(int $languageId): int
    {
        $pageCount = Database::select("SELECT COUNT(*) as count FROM pages WHERE language_id = ?", [$languageId])[0]['count'] ?? 0;
        $menuCount = Database::select("SELECT COUNT(*) as count FROM navigation_menus WHERE language_id = ?", [$languageId])[0]['count'] ?? 0;
        $postCount = Database::select("SELECT COUNT(*) as count FROM blog_posts WHERE language_id = ?", [$languageId])[0]['count'] ?? 0;
        $categoryCount = Database::select("SELECT COUNT(*) as count FROM blog_categories WHERE language_id = ?", [$languageId])[0]['count'] ?? 0;
        $tagCount = Database::select("SELECT COUNT(*) as count FROM blog_tags WHERE language_id = ?", [$languageId])[0]['count'] ?? 0;

        return (int) $pageCount + (int) $menuCount + (int) $postCount + (int) $categoryCount + (int) $tagCount;
    }
}


if (!\class_exists('DashboardLanguageService', false) && !\interface_exists('DashboardLanguageService', false) && !\trait_exists('DashboardLanguageService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardLanguageService', 'DashboardLanguageService');
}
