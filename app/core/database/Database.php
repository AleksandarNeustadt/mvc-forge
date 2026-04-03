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
 * Database Class - Universal PDO Wrapper
 *
 * Supports multiple database drivers: MySQL, PostgreSQL, SQLite
 *
 * Usage:
 *   Database::select('SELECT * FROM users WHERE id = ?', [1]);
 *   Database::execute('INSERT INTO users (name) VALUES (?)', ['John']);
 *   $id = Database::lastInsertId();
 */
class Database
{
    private static array $connections = [];
    private static ?string $currentDriver = null;

    /**
     * Get database connection
     *
     * @param string|null $driver Driver name (mysql, pgsql, sqlite) or null for default
     * @return PDO Database connection
     */
    public static function connection(?string $driver = null): PDO
    {
        $driver = $driver ?? self::getDriver();

        if (!isset(self::$connections[$driver])) {
            self::$connections[$driver] = self::createConnection($driver);
        }

        return self::$connections[$driver];
    }

    /**
     * Create new database connection
     */
    private static function createConnection(string $driver): PDO
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', '');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        try {
            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ];
                    break;

                case 'pgsql':
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                    break;

                case 'sqlite':
                    $dsn = "sqlite:{$database}";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                    break;

                default:
                    throw new Exception("Unsupported database driver: {$driver}");
            }

            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get current database driver
     */
    public static function getDriver(): string
    {
        if (self::$currentDriver === null) {
            self::$currentDriver = Env::get('DB_CONNECTION', 'mysql');
        }

        return self::$currentDriver;
    }

    /**
     * Set database driver
     */
    public static function setDriver(string $driver): void
    {
        self::$currentDriver = $driver;
    }

    /**
     * Execute a query and return affected rows
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return int Number of affected rows
     */
    public static function execute(string $sql, array $bindings = []): int
    {
        try {
            $pdo = self::connection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database execute failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a query and return all results
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return array All rows
     */
    public static function select(string $sql, array $bindings = []): array
    {
        try {
            $pdo = self::connection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database select failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a query and return first result
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return array|null First row or null
     */
    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $results = self::select($sql, $bindings);
        return $results[0] ?? null;
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): int
    {
        $pdo = self::connection();
        return (int) $pdo->lastInsertId();
    }

    /**
     * Begin database transaction
     */
    public static function beginTransaction(): bool
    {
        $pdo = self::connection();
        return $pdo->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public static function commit(): bool
    {
        $pdo = self::connection();
        return $pdo->commit();
    }

    /**
     * Rollback database transaction
     */
    public static function rollback(): bool
    {
        $pdo = self::connection();
        return $pdo->rollBack();
    }

    /**
     * Execute a callback inside a database transaction.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $callback();
        }

        self::beginTransaction();

        try {
            $result = $callback();
            self::commit();

            return $result;
        } catch (Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Get QueryBuilder instance for table
     */
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    /**
     * Execute raw query (for special cases)
     */
    public static function query(string $sql, array $bindings = []): PDOStatement
    {
        $pdo = self::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * Ensure SQL identifiers are plain table/column names before interpolation.
     */
    public static function assertIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Invalid database identifier: {$identifier}");
        }

        return $identifier;
    }

    /**
     * Quote a validated SQL identifier for the active driver.
     */
    public static function quoteIdentifier(string $identifier): string
    {
        $identifier = self::assertIdentifier($identifier);

        return self::getDriver() === 'mysql'
            ? "`{$identifier}`"
            : "\"{$identifier}\"";
    }
}


if (!\class_exists('Database', false) && !\interface_exists('Database', false) && !\trait_exists('Database', false)) {
    \class_alias(__NAMESPACE__ . '\\Database', 'Database');
}
