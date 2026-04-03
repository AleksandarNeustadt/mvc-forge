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
 * Database Table Builder Class
 * 
 * Fluent API for building database tables (similar to Laravel migrations)
 * 
 * Usage:
 *   $builder = new DatabaseTableBuilder('users');
 *   $builder->id()
 *           ->string('name')
 *           ->email('email')->unique()
 *           ->timestamps()
 *           ->create();
 */
class DatabaseTableBuilder
{
    private string $table;
    private string $engine = 'InnoDB';
    private string $charset = 'utf8mb4';
    private string $collation = 'utf8mb4_unicode_ci';
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private ?string $primaryKey = null;

    public function __construct(string $table)
    {
        $this->table = Database::assertIdentifier($table);
    }

    /**
     * Set storage engine
     */
    public function engine(string $engine): self
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Set charset and collation
     */
    public function charset(string $charset, string $collation = null): self
    {
        $this->charset = $charset;
        $this->collation = $collation ?? $charset . '_unicode_ci';
        return $this;
    }

    /**
     * ID column (auto-increment primary key)
     */
    public function id(string $name = 'id'): self
    {
        $driver = Database::getDriver();
        $column = Database::quoteIdentifier($name);
        
        if ($driver === 'pgsql') {
            $this->columns[] = "{$column} SERIAL PRIMARY KEY";
        } elseif ($driver === 'sqlite') {
            $this->columns[] = "{$column} INTEGER PRIMARY KEY AUTOINCREMENT";
        } else {
            $this->columns[] = "{$column} INT AUTO_INCREMENT PRIMARY KEY";
        }
        
        $this->primaryKey = Database::assertIdentifier($name);
        return $this;
    }

    /**
     * String column
     */
    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " VARCHAR({$length})";
        return $this;
    }

    /**
     * Text column
     */
    public function text(string $name, string $type = 'TEXT'): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " {$type}";
        return $this;
    }

    /**
     * Integer column
     */
    public function integer(string $name, int $length = null): self
    {
        $lengthStr = $length ? "({$length})" : '';
        $this->columns[] = Database::quoteIdentifier($name) . " INT{$lengthStr}";
        return $this;
    }

    /**
     * Big integer column
     */
    public function bigInteger(string $name): self
    {
        $driver = Database::getDriver();
        $type = $driver === 'pgsql' ? 'BIGSERIAL' : 'BIGINT';
        $this->columns[] = Database::quoteIdentifier($name) . " {$type}";
        return $this;
    }

    /**
     * Boolean column
     */
    public function boolean(string $name): self
    {
        $driver = Database::getDriver();
        $type = $driver === 'pgsql' ? 'BOOLEAN' : 'TINYINT(1)';
        $this->columns[] = Database::quoteIdentifier($name) . " {$type}";
        return $this;
    }

    /**
     * Float column
     */
    public function float(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " FLOAT({$precision}, {$scale})";
        return $this;
    }

    /**
     * Decimal column
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " DECIMAL({$precision}, {$scale})";
        return $this;
    }

    /**
     * Date column
     */
    public function date(string $name): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " DATE";
        return $this;
    }

    /**
     * DateTime column
     */
    public function dateTime(string $name): self
    {
        $driver = Database::getDriver();
        $type = $driver === 'pgsql' ? 'TIMESTAMP' : 'TIMESTAMP';
        $this->columns[] = Database::quoteIdentifier($name) . " {$type}";
        return $this;
    }

    /**
     * Time column
     */
    public function time(string $name): self
    {
        $this->columns[] = Database::quoteIdentifier($name) . " TIME";
        return $this;
    }

    /**
     * JSON column
     */
    public function json(string $name): self
    {
        $driver = Database::getDriver();
        $type = $driver === 'mysql' ? 'JSON' : ($driver === 'pgsql' ? 'JSONB' : 'TEXT');
        $this->columns[] = Database::quoteIdentifier($name) . " {$type}";
        return $this;
    }

    /**
     * Email column (string with email validation)
     */
    public function email(string $name, int $length = 255): self
    {
        return $this->string($name, $length);
    }

    /**
     * Timestamps (created_at, updated_at)
     */
    public function timestamps(): self
    {
        $driver = Database::getDriver();
        
        if ($driver === 'mysql') {
            $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        } elseif ($driver === 'pgsql') {
            $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        } else {
            $this->columns[] = "created_at INTEGER DEFAULT (strftime('%s', 'now'))";
            $this->columns[] = "updated_at INTEGER DEFAULT (strftime('%s', 'now'))";
        }
        
        return $this;
    }

    /**
     * Nullable column
     */
    public function nullable(): self
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex] .= ' NULL';
        }
        return $this;
    }

    /**
     * Default value
     */
    public function default(mixed $value): self
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex] .= " DEFAULT " . $this->formatDefaultValue($value);
        }
        return $this;
    }

    /**
     * Make column unique
     */
    public function unique(): self
    {
        if (!empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $columnName = $this->extractColumnName($this->columns[$lastIndex]);
            $indexName = Database::quoteIdentifier('idx_' . $columnName);
            $this->indexes[] = "UNIQUE INDEX {$indexName} (" . Database::quoteIdentifier($columnName) . ")";
        }
        return $this;
    }

    /**
     * Add index
     */
    public function index(string $name, array $columns = []): self
    {
        $name = Database::quoteIdentifier($name);

        if (empty($columns) && !empty($this->columns)) {
            $lastIndex = count($this->columns) - 1;
            $columnName = $this->extractColumnName($this->columns[$lastIndex]);
            $columns = [$columnName];
        }
        
        $cols = implode(', ', array_map([Database::class, 'quoteIdentifier'], $columns));
        $this->indexes[] = "INDEX {$name} ({$cols})";
        return $this;
    }

    /**
     * Foreign key
     */
    public function foreign(string $column, string $references, string $on = null, string $onDelete = null, string $onUpdate = null): self
    {
        $fk = "FOREIGN KEY (" . Database::quoteIdentifier($column) . ") REFERENCES "
            . $this->formatForeignReference($references);
        
        if ($onDelete) {
            $fk .= " ON DELETE {$onDelete}";
        }
        
        if ($onUpdate) {
            $fk .= " ON UPDATE {$onUpdate}";
        }
        
        $this->foreignKeys[] = $fk;
        return $this;
    }

    /**
     * Extract column name from column definition
     */
    private function extractColumnName(string $columnDef): string
    {
        preg_match('/^[`"]?(\w+)[`"]?\s/', $columnDef, $matches);
        return $matches[1] ?? '';
    }

    private function formatDefaultValue(mixed $value): string
    {
        if (is_string($value)) {
            return Database::connection()->quote($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return 'NULL';
        }

        return (string) $value;
    }

    private function formatForeignReference(string $references): string
    {
        $references = trim($references);

        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\(([A-Za-z_][A-Za-z0-9_]*)\)$/', $references, $matches)) {
            return Database::quoteIdentifier($matches[1]) . '(' . Database::quoteIdentifier($matches[2]) . ')';
        }

        return Database::quoteIdentifier($references);
    }

    /**
     * Build CREATE TABLE SQL
     */
    public function toSql(): string
    {
        $driver = Database::getDriver();
        $sql = "CREATE TABLE IF NOT EXISTS " . Database::quoteIdentifier($this->table) . " (\n";
        
        // Columns
        $sql .= "    " . implode(",\n    ", $this->columns);
        
        // Indexes
        if (!empty($this->indexes)) {
            $sql .= ",\n    " . implode(",\n    ", $this->indexes);
        }
        
        // Foreign keys
        if (!empty($this->foreignKeys)) {
            $sql .= ",\n    " . implode(",\n    ", $this->foreignKeys);
        }
        
        $sql .= "\n)";
        
        // MySQL specific
        if ($driver === 'mysql') {
            $sql .= " ENGINE={$this->engine}";
            $sql .= " DEFAULT CHARSET={$this->charset}";
            $sql .= " COLLATE={$this->collation}";
        }
        
        return $sql;
    }

    /**
     * Create table
     */
    public function create(): bool
    {
        try {
            $sql = $this->toSql();
            Database::execute($sql);
            return true;
        } catch (Exception $e) {
            error_log('Table creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Drop table
     */
    public static function drop(string $table): bool
    {
        try {
            Database::execute("DROP TABLE IF EXISTS " . Database::quoteIdentifier($table));
            return true;
        } catch (Exception $e) {
            error_log('Table drop failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if table exists
     */
    public static function exists(string $table): bool
    {
        try {
            $table = Database::assertIdentifier($table);
            $driver = Database::getDriver();
            $dbName = Env::get('DB_DATABASE');
            
            if ($driver === 'mysql') {
                // Try to get database name from connection if not in env
                try {
                    $result = Database::selectOne("SELECT DATABASE()");
                    if ($result && isset($result['DATABASE()'])) {
                        $dbName = $result['DATABASE()'];
                    }
                } catch (Exception $e) {
                    // Use env value
                }
                
                $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                        WHERE table_schema = ? AND table_name = ?";
                $result = Database::selectOne($sql, [$dbName, $table]);
                return ($result['count'] ?? 0) > 0;
            } elseif ($driver === 'pgsql') {
                $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                        WHERE table_schema = 'public' AND table_name = ?";
                $result = Database::selectOne($sql, [$table]);
                return ($result['count'] ?? 0) > 0;
            } else {
                // SQLite
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
                $result = Database::selectOne($sql, [$table]);
                return $result !== null;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}


if (!\class_exists('DatabaseTableBuilder', false) && !\interface_exists('DatabaseTableBuilder', false) && !\trait_exists('DatabaseTableBuilder', false)) {
    \class_alias(__NAMESPACE__ . '\\DatabaseTableBuilder', 'DatabaseTableBuilder');
}
