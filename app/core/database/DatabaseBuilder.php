<?php

namespace App\Core\database;


use App\Core\config\Env;use BadMethodCallException;
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
 * Database Builder Class
 * 
 * Manages database structure: tables, columns, indexes
 * 
 * Usage:
 *   $builder = new DatabaseBuilder();
 *   $tables = $builder->getTables();
 *   $columns = $builder->getTableColumns('users');
 */
class DatabaseBuilder
{
    /**
     * Get all tables in database
     */
    public static function getTables(): array
    {
        try {
            $driver = Database::getDriver();
            $dbName = Env::get('DB_DATABASE');

            if ($driver === 'mysql') {
                $sql = "SELECT table_name as name 
                        FROM information_schema.tables 
                        WHERE table_schema = ? 
                        ORDER BY table_name";
                $results = Database::select($sql, [$dbName]);
                return array_column($results, 'name');
            } elseif ($driver === 'pgsql') {
                $sql = "SELECT table_name as name 
                        FROM information_schema.tables 
                        WHERE table_schema = 'public' 
                        ORDER BY table_name";
                $results = Database::select($sql);
                return array_column($results, 'name');
            } else {
                // SQLite
                $sql = "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";
                $results = Database::select($sql);
                return array_column($results, 'name');
            }
        } catch (Exception $e) {
            error_log('Get tables failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get table columns
     */
    public static function getTableColumns(string $table): array
    {
        try {
            $table = Database::assertIdentifier($table);
            $driver = Database::getDriver();
            $dbName = Env::get('DB_DATABASE');

            if ($driver === 'mysql') {
                $sql = "SELECT 
                            column_name as name,
                            data_type as type,
                            character_maximum_length as length,
                            is_nullable as nullable,
                            column_default as default_value,
                            extra,
                            column_key as key_type
                        FROM information_schema.columns 
                        WHERE table_schema = ? AND table_name = ?
                        ORDER BY ordinal_position";
                return Database::select($sql, [$dbName, $table]);
            } elseif ($driver === 'pgsql') {
                $sql = "SELECT 
                            column_name as name,
                            data_type as type,
                            character_maximum_length as length,
                            is_nullable as nullable,
                            column_default as default_value
                        FROM information_schema.columns 
                        WHERE table_schema = 'public' AND table_name = ?
                        ORDER BY ordinal_position";
                return Database::select($sql, [$table]);
            } else {
                // SQLite
                $sql = "PRAGMA table_info(" . Database::quoteIdentifier($table) . ")";
                $results = Database::select($sql);
                return array_map(function($col) {
                    return [
                        'name' => $col['name'],
                        'type' => $col['type'],
                        'nullable' => $col['notnull'] == 0 ? 'YES' : 'NO',
                        'default_value' => $col['dflt_value'],
                        'key_type' => $col['pk'] == 1 ? 'PRI' : ''
                    ];
                }, $results);
            }
        } catch (Exception $e) {
            error_log('Get table columns failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get table indexes
     */
    public static function getTableIndexes(string $table): array
    {
        try {
            $table = Database::assertIdentifier($table);
            $driver = Database::getDriver();
            $dbName = Env::get('DB_DATABASE');

            if ($driver === 'mysql') {
                $sql = "SHOW INDEXES FROM " . Database::quoteIdentifier($table);
                return Database::select($sql);
            } elseif ($driver === 'pgsql') {
                $sql = "SELECT 
                            indexname as name,
                            indexdef as definition
                        FROM pg_indexes 
                        WHERE tablename = ?";
                return Database::select($sql, [$table]);
            } else {
                // SQLite
                $sql = "SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name=?";
                return Database::select($sql, [$table]);
            }
        } catch (Exception $e) {
            error_log('Get table indexes failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get table info
     */
    public static function getTableInfo(string $table): array
    {
        $table = Database::assertIdentifier($table);

        return [
            'name' => $table,
            'exists' => DatabaseTableBuilder::exists($table),
            'columns' => self::getTableColumns($table),
            'indexes' => self::getTableIndexes($table),
            'row_count' => self::getTableRowCount($table)
        ];
    }

    /**
     * Get row count for table
     */
    public static function getTableRowCount(string $table): int
    {
        try {
            $table = Database::quoteIdentifier($table);
            $result = Database::selectOne("SELECT COUNT(*) as count FROM {$table}");
            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Add column to table
     */
    public static function addColumn(string $table, string $name, string $type, array $options = []): bool
    {
        try {
            $table = Database::quoteIdentifier($table);
            $name = Database::quoteIdentifier($name);
            $nullable = $options['nullable'] ?? false ? 'NULL' : 'NOT NULL';
            $default = '';
            if (isset($options['default'])) {
                $defaultValue = $options['default'];
                if (is_string($defaultValue)) {
                    $defaultValue = Database::connection()->quote($defaultValue);
                } elseif (is_bool($defaultValue)) {
                    $defaultValue = $defaultValue ? '1' : '0';
                } elseif ($defaultValue === null) {
                    $defaultValue = 'NULL';
                }
                $default = " DEFAULT {$defaultValue}";
            }
            
            $sql = "ALTER TABLE {$table} ADD COLUMN {$name} {$type} {$nullable}{$default}";
            Database::execute($sql);
            return true;
        } catch (Exception $e) {
            error_log('Add column failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Drop column from table
     */
    public static function dropColumn(string $table, string $column): bool
    {
        try {
            $driver = Database::getDriver();
            
            if ($driver === 'sqlite') {
                // SQLite doesn't support DROP COLUMN directly
                throw new Exception('SQLite does not support DROP COLUMN. Table must be recreated.');
            }
            
            $sql = "ALTER TABLE " . Database::quoteIdentifier($table)
                . " DROP COLUMN " . Database::quoteIdentifier($column);
            Database::execute($sql);
            return true;
        } catch (Exception $e) {
            error_log('Drop column failed: ' . $e->getMessage());
            throw $e;
        }
    }
}


if (!\class_exists('DatabaseBuilder', false) && !\interface_exists('DatabaseBuilder', false) && !\trait_exists('DatabaseBuilder', false)) {
    \class_alias(__NAMESPACE__ . '\\DatabaseBuilder', 'DatabaseBuilder');
}
