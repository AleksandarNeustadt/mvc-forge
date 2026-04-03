<?php

namespace App\Core\services;


use App\Core\config\Env;
use App\Core\database\Database;
use App\Core\database\DatabaseBuilder;
use App\Core\database\DatabaseTableBuilder;
use App\Core\security\Security;use BadMethodCallException;
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
 * Service layer for dashboard database schema management.
 */
class DashboardSchemaService
{
    public function getDatabaseOverview(): array
    {
        $tables = DatabaseBuilder::getTables();

        return [
            'dbInfo' => [
                'driver' => Database::getDriver(),
                'database' => Env::get('DB_DATABASE'),
                'host' => Env::get('DB_HOST'),
                'table_count' => count($tables),
            ],
            'tables' => $tables,
        ];
    }

    public function getTableInfo(string $table): array
    {
        return DatabaseBuilder::getTableInfo($table);
    }

    public function tableExists(string $table): bool
    {
        return DatabaseTableBuilder::exists($table);
    }

    public function createTable(string $tableName): void
    {
        $builder = new DatabaseTableBuilder(Security::sanitize($tableName, 'slug'));
        $builder->id()->create();
    }

    public function normalizeColumnDefinition(array $input): array
    {
        $options = [
            'nullable' => !empty($input['nullable']),
        ];

        $default = $input['default'] ?? null;
        if ($default !== null && $default !== '') {
            $options['default'] = $default;
        }

        return [
            'name' => Security::sanitize((string) ($input['column_name'] ?? ''), 'slug'),
            'type' => $this->buildColumnType((string) ($input['column_type'] ?? ''), $input['length'] ?? null),
            'options' => $options,
            'unique' => !empty($input['unique']),
        ];
    }

    public function addColumn(string $table, array $columnDefinition): void
    {
        $table = Database::assertIdentifier($table);
        $columnName = Database::assertIdentifier((string) $columnDefinition['name']);

        DatabaseBuilder::addColumn(
            $table,
            $columnName,
            $columnDefinition['type'],
            $columnDefinition['options']
        );

        if (!empty($columnDefinition['unique'])) {
            $quotedTable = Database::quoteIdentifier($table);
            $quotedColumn = Database::quoteIdentifier($columnName);
            $indexName = Database::assertIdentifier('idx_' . $columnName);
            Database::execute(
                "ALTER TABLE {$quotedTable} ADD UNIQUE INDEX {$indexName} ({$quotedColumn})"
            );
        }
    }

    public function dropTable(string $table): void
    {
        DatabaseTableBuilder::drop($table);
    }

    public function dropColumn(string $table, string $column): void
    {
        DatabaseBuilder::dropColumn($table, $column);
    }

    public function buildColumnType(string $type, ?string $length): string
    {
        $types = [
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'boolean' => 'BOOLEAN',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'date' => 'DATE',
            'datetime' => 'TIMESTAMP',
            'time' => 'TIME',
            'json' => Database::getDriver() === 'mysql' ? 'JSON' : 'TEXT',
        ];

        $sqlType = $types[$type] ?? 'VARCHAR';

        if ($length && in_array($type, ['string', 'integer', 'decimal'], true)) {
            if ($type === 'decimal') {
                $parts = explode(',', $length);
                $precision = $parts[0] ?? 8;
                $scale = $parts[1] ?? 2;
                return "{$sqlType}({$precision}, {$scale})";
            }

            return "{$sqlType}({$length})";
        }

        if ($type === 'string' && !$length) {
            return "{$sqlType}(255)";
        }

        return $sqlType;
    }
}


if (!\class_exists('DashboardSchemaService', false) && !\interface_exists('DashboardSchemaService', false) && !\trait_exists('DashboardSchemaService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardSchemaService', 'DashboardSchemaService');
}
