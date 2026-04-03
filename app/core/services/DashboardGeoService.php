<?php

namespace App\Core\services;


use App\Core\database\Database;
use App\Models\Continent;
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
 * Service layer for continent and region dashboard management.
 */
class DashboardGeoService
{
    public function buildContinentOptions(): array
    {
        $continents = [];

        if (!class_exists('Continent')) {
            return $continents;
        }

        $continents[''] = '-- Select Continent --';
        foreach (Continent::getActive() as $continent) {
            $continents[$continent->id] = $continent->name;
        }

        return $continents;
    }

    public function continentCodeExists(string $code, ?int $excludeId = null): bool
    {
        $query = Continent::query()->where('code', strtolower(trim($code)));

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function regionCodeExists(string $code, ?int $excludeId = null): bool
    {
        $normalizedCode = strtolower(trim($code));
        if ($normalizedCode === '') {
            return false;
        }

        $query = Region::query()->where('code', $normalizedCode);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function buildContinentEditData(Continent $continent): array
    {
        return [
            'continent' => $this->normalizeModelArray($continent),
        ];
    }

    public function buildRegionEditData(Region $region): array
    {
        return [
            'region' => $this->normalizeModelArray($region),
            'continents' => $this->buildContinentOptions(),
        ];
    }

    public function validateContinentInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];

        if ($this->continentCodeExists((string) ($input['code'] ?? ''), $excludeId)) {
            $errors['code'] = array_merge(
                $errors['code'] ?? [],
                ['Continent code already exists']
            );
        }

        return empty($errors) ? true : $errors;
    }

    public function validateRegionInput(array|bool $validation, array $input, ?int $excludeId = null): array|bool
    {
        $errors = is_array($validation) ? $validation : [];

        if ($this->regionCodeExists((string) ($input['code'] ?? ''), $excludeId)) {
            $errors['code'] = array_merge(
                $errors['code'] ?? [],
                ['Region code already exists']
            );
        }

        return empty($errors) ? true : $errors;
    }

    public function applyContinentData(Continent $continent, array $input): void
    {
        $continent->code = strtolower(trim($input['code'] ?? ''));
        $continent->name = $input['name'] ?? '';
        $continent->native_name = $input['native_name'] ?? '';
        $continent->is_active = !empty($input['is_active']);
        $continent->sort_order = (int) ($input['sort_order'] ?? 0);
    }

    public function applyRegionData(Region $region, array $input): void
    {
        $region->continent_id = (int) ($input['continent_id'] ?? 0);
        $region->name = $input['name'] ?? '';
        $region->code = strtolower(trim($input['code'] ?? ''));
        $region->native_name = $input['native_name'] ?? '';
        $region->description = $input['description'] ?? '';
        $region->is_active = !empty($input['is_active']);
        $region->sort_order = (int) ($input['sort_order'] ?? 0);
    }

    public function saveContinent(Continent $continent, array $input): Continent
    {
        $this->applyContinentData($continent, $input);
        $continent->save();

        return $continent;
    }

    public function saveRegion(Region $region, array $input): Region
    {
        $this->applyRegionData($region, $input);
        $region->save();

        return $region;
    }

    public function validateContinentDeletion(int $continentId): array
    {
        $languageCount = Database::select(
            "SELECT COUNT(*) as count FROM languages WHERE continent_id = ?",
            [$continentId]
        )[0]['count'] ?? 0;

        $regionCount = Database::select(
            "SELECT COUNT(*) as count FROM regions WHERE continent_id = ?",
            [$continentId]
        )[0]['count'] ?? 0;

        if ($languageCount > 0 || $regionCount > 0) {
            return ['general' => ['Cannot delete continent with associated languages or regions']];
        }

        return [];
    }

    public function validateRegionDeletion(int $regionId): array
    {
        $languageCount = Database::select(
            "SELECT COUNT(*) as count FROM languages WHERE region_id = ?",
            [$regionId]
        )[0]['count'] ?? 0;

        if ($languageCount > 0) {
            return ['general' => ['Cannot delete region with associated languages']];
        }

        return [];
    }

    public function deleteContinent(Continent $continent): void
    {
        $errors = $this->validateContinentDeletion((int) $continent->id);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                $errors['general'][0] ?? 'Cannot delete continent'
            );
        }

        $continent->delete();
    }

    public function deleteRegion(Region $region): void
    {
        $errors = $this->validateRegionDeletion((int) $region->id);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                $errors['general'][0] ?? 'Cannot delete region'
            );
        }

        $region->delete();
    }

    private function normalizeModelArray($model): array
    {
        $modelArray = $model->toArray();
        if (!isset($modelArray['id']) && isset($model->id)) {
            $modelArray['id'] = $model->id;
        }

        return is_array($modelArray) ? $modelArray : [];
    }
}


if (!\class_exists('DashboardGeoService', false) && !\interface_exists('DashboardGeoService', false) && !\trait_exists('DashboardGeoService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardGeoService', 'DashboardGeoService');
}
