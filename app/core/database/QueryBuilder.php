<?php

namespace App\Core\database;

use BadMethodCallException;
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
 * Query Builder Class
 * 
 * Fluent API for building database queries (similar to Laravel)
 * 
 * Usage:
 *   $users = Database::table('users')
 *       ->where('active', 1)
 *       ->where('age', '>', 18)
 *       ->orderBy('name')
 *       ->get();
 */
class QueryBuilder
{
    private string $table;
    private array $wheres = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $selects = ['*'];
    private array $joins = [];
    private array $groupBy = [];
    private array $having = [];
    private string $type = 'select'; // select, insert, update, delete

    public function __construct(string $table)
    {
        $this->table = Database::assertIdentifier($table);
    }

    /**
     * Select columns
     */
    public function select(array|string $columns = ['*']): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->selects = array_map([$this, 'formatSelectExpression'], $columns);
        return $this;
    }

    /**
     * Add WHERE clause
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        // If only 2 arguments, assume = operator
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // If operator is null, assume =
        if ($operator === null) {
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Add OR WHERE clause
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add WHERE NOT IN clause
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add WHERE NULL clause
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IS',
            'value' => null,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add WHERE NOT NULL clause
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IS NOT',
            'value' => null,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add WHERE LIKE clause
     */
    public function whereLike(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * Add grouped OR LIKE conditions for multiple columns.
     */
    public function whereAnyLike(array $columns, string $value, string $boolean = 'AND'): self
    {
        $safeColumns = array_values(array_filter($columns, static fn($column) => is_string($column) && trim($column) !== ''));
        if (empty($safeColumns)) {
            return $this;
        }

        $conditions = [];
        $bindings = [];

        foreach ($safeColumns as $column) {
            $conditions[] = $this->formatColumnReference($column) . ' LIKE ?';
            $bindings[] = $value;
        }

        $this->wheres[] = [
            'raw' => true,
            'sql' => implode(' OR ', $conditions),
            'bindings' => $bindings,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * Add WHERE BETWEEN clause
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => [$min, $max],
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Add JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    /**
     * Add ORDER BY DESC clause
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add GROUP BY clause
     */
    public function groupBy(string|array $columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * Add HAVING clause
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Add LIMIT clause
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add OFFSET clause
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add pagination (limit + offset)
     */
    public function paginate(int $perPage, int $page = 1): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    /**
     * Build WHERE clause SQL
     */
    private function buildWhereClause(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $sql = 'WHERE ';
        $bindings = [];
        $conditions = [];

        foreach ($this->wheres as $index => $where) {
            // Check if this is a raw where clause
            if (isset($where['raw'])) {
                $boolean = $index > 0 ? ($where['boolean'] ?? 'AND') : '';
                $conditions[] = ($boolean ? $boolean . ' ' : '') . '(' . $where['sql'] . ')';
                if (isset($where['bindings']) && is_array($where['bindings'])) {
                    $bindings = array_merge($bindings, $where['bindings']);
                }
                continue;
            }

            $column = $where['column'];
            $operator = $this->normalizeOperator((string) $where['operator']);
            $value = $where['value'];
            $boolean = $index > 0 ? $where['boolean'] : '';
            $columnSql = $this->formatColumnReference($column);

            if ($operator === 'IN' || $operator === 'NOT IN') {
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $conditions[] = "{$boolean} {$columnSql} {$operator} ({$placeholders})";
                $bindings = array_merge($bindings, $value);
            } elseif ($operator === 'BETWEEN') {
                $conditions[] = "{$boolean} {$columnSql} {$operator} ? AND ?";
                $bindings[] = $value[0];
                $bindings[] = $value[1];
            } elseif ($value === null) {
                $conditions[] = "{$boolean} {$columnSql} {$operator} NULL";
            } else {
                $conditions[] = "{$boolean} {$columnSql} {$operator} ?";
                $bindings[] = $value;
            }
        }

        $sql .= implode(' ', $conditions);

        return [$sql, $bindings];
    }
    
    /**
     * Add raw WHERE clause with bindings
     * Useful for grouping conditions: ->whereRaw('(field1 LIKE ? OR field2 LIKE ?)', ['%search%', '%search%'])
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'raw' => true,
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => $boolean
        ];
        return $this;
    }

    /**
     * Build JOIN clause SQL
     */
    private function buildJoinClause(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';

        foreach ($this->joins as $join) {
            $joinType = $this->normalizeJoinType($join['type']);
            $joinTable = Database::quoteIdentifier($join['table']);
            $first = $this->formatColumnReference($join['first']);
            $operator = $this->normalizeOperator($join['operator']);
            $second = $this->formatColumnReference($join['second']);

            $sql .= " {$joinType} JOIN {$joinTable} ON {$first} {$operator} {$second}";
        }

        return $sql;
    }

    /**
     * Build ORDER BY clause SQL
     */
    private function buildOrderByClause(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $orders = [];

        foreach ($this->orderBy as $order) {
            $orders[] = $this->formatColumnReference($order['column']) . ' ' . $this->normalizeDirection($order['direction']);
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Build GROUP BY clause SQL
     */
    private function buildGroupByClause(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', array_map([$this, 'formatColumnReference'], $this->groupBy));
    }

    /**
     * Build HAVING clause SQL
     */
    private function buildHavingClause(): array
    {
        if (empty($this->having)) {
            return ['', []];
        }

        $sql = 'HAVING ';
        $bindings = [];
        $conditions = [];

        foreach ($this->having as $having) {
            $conditions[] = $this->formatColumnReference($having['column']) . ' ' . $this->normalizeOperator($having['operator']) . ' ?';
            $bindings[] = $having['value'];
        }

        $sql .= implode(' AND ', $conditions);

        return [$sql, $bindings];
    }

    /**
     * Build SELECT query SQL
     */
    private function buildSelectSql(): array
    {
        $selects = implode(', ', $this->selects);
        $sql = "SELECT {$selects} FROM " . Database::quoteIdentifier($this->table);

        // Add joins
        $sql .= $this->buildJoinClause();

        // Add where clause
        [$whereSql, $whereBindings] = $this->buildWhereClause();
        $sql .= ' ' . $whereSql;

        // Add group by
        $sql .= ' ' . $this->buildGroupByClause();

        // Add having
        [$havingSql, $havingBindings] = $this->buildHavingClause();
        $sql .= ' ' . $havingSql;

        // Add order by
        $sql .= ' ' . $this->buildOrderByClause();

        // Add limit
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // Add offset
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $bindings = array_merge($whereBindings, $havingBindings);

        return [$sql, $bindings];
    }

    /**
     * Execute SELECT query and return all results
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->buildSelectSql();
        return Database::select($sql, $bindings);
    }

    /**
     * Execute SELECT query and return first result
     */
    public function first(): ?array
    {
        $this->limit = 1;
        [$sql, $bindings] = $this->buildSelectSql();
        return Database::selectOne($sql, $bindings);
    }

    /**
     * Execute SELECT query and return count
     */
    public function count(): int
    {
        $originalSelects = $this->selects;
        $this->selects = [$this->formatSelectExpression('COUNT(*) as count')];
        [$sql, $bindings] = $this->buildSelectSql();
        $result = Database::selectOne($sql, $bindings);
        $this->selects = $originalSelects;
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Check if any records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Execute SELECT query and return single value
     */
    public function value(string $column): mixed
    {
        $originalSelects = $this->selects;
        $this->selects = [$this->formatSelectExpression($column)];
        $this->limit = 1;
        [$sql, $bindings] = $this->buildSelectSql();
        $result = Database::selectOne($sql, $bindings);
        $this->selects = $originalSelects;
        return $result[$column] ?? null;
    }

    /**
     * Execute SELECT query and return pluck (single column as array)
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $originalSelects = $this->selects;
        
        if ($key) {
            $this->selects = [$this->formatSelectExpression($key), $this->formatSelectExpression($column)];
        } else {
            $this->selects = [$this->formatSelectExpression($column)];
        }
        
        [$sql, $bindings] = $this->buildSelectSql();
        $results = Database::select($sql, $bindings);
        $this->selects = $originalSelects;

        if ($key) {
            $plucked = [];
            foreach ($results as $row) {
                $plucked[$row[$key]] = $row[$column];
            }
            return $plucked;
        }

        return array_column($results, $column);
    }

    /**
     * Build INSERT query SQL
     */
    private function buildInsertSql(array $data): array
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnsStr = implode(', ', array_map([Database::class, 'quoteIdentifier'], $columns));

        $sql = "INSERT INTO " . Database::quoteIdentifier($this->table) . " ({$columnsStr}) VALUES ({$placeholders})";
        $bindings = array_values($data);

        return [$sql, $bindings];
    }

    /**
     * Execute INSERT query
     */
    public function insert(array $data): bool
    {
        [$sql, $bindings] = $this->buildInsertSql($data);
        Database::execute($sql, $bindings);
        return true;
    }

    /**
     * Execute INSERT query and return last insert ID
     */
    public function insertGetId(array $data): int
    {
        [$sql, $bindings] = $this->buildInsertSql($data);
        Database::execute($sql, $bindings);
        return Database::lastInsertId();
    }

    /**
     * Execute INSERT query for multiple rows
     */
    public function insertBatch(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data[0]);
        $columnsStr = implode(', ', array_map([Database::class, 'quoteIdentifier'], $columns));

        $placeholders = [];
        $bindings = [];

        foreach ($data as $row) {
            $rowPlaceholders = implode(', ', array_fill(0, count($columns), '?'));
            $placeholders[] = "({$rowPlaceholders})";
            $bindings = array_merge($bindings, array_values($row));
        }

        $placeholdersStr = implode(', ', $placeholders);
        $sql = "INSERT INTO " . Database::quoteIdentifier($this->table) . " ({$columnsStr}) VALUES {$placeholdersStr}";

        Database::execute($sql, $bindings);
        return true;
    }

    /**
     * Build UPDATE query SQL
     */
    private function buildUpdateSql(array $data): array
    {
        $columns = array_keys($data);
        $setClause = [];
        $bindings = [];

        foreach ($columns as $column) {
            $setClause[] = Database::quoteIdentifier($column) . " = ?";
            $bindings[] = $data[$column];
        }

        $setClauseStr = implode(', ', $setClause);
        $sql = "UPDATE " . Database::quoteIdentifier($this->table) . " SET {$setClauseStr}";

        // Add where clause
        [$whereSql, $whereBindings] = $this->buildWhereClause();
        $sql .= ' ' . $whereSql;
        $bindings = array_merge($bindings, $whereBindings);

        return [$sql, $bindings];
    }

    /**
     * Execute UPDATE query
     */
    public function update(array $data): int
    {
        [$sql, $bindings] = $this->buildUpdateSql($data);
        return Database::execute($sql, $bindings);
    }

    /**
     * Build DELETE query SQL
     */
    private function buildDeleteSql(): array
    {
        $sql = "DELETE FROM " . Database::quoteIdentifier($this->table);

        // Add where clause
        [$whereSql, $whereBindings] = $this->buildWhereClause();
        $sql .= ' ' . $whereSql;

        return [$sql, $whereBindings];
    }

    /**
     * Execute DELETE query
     */
    public function delete(): int
    {
        [$sql, $bindings] = $this->buildDeleteSql();
        return Database::execute($sql, $bindings);
    }

    /**
     * Get SQL string (for debugging)
     */
    public function toSql(): string
    {
        [$sql, $bindings] = $this->buildSelectSql();
        
        // Replace placeholders with values for display
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }

    private function formatSelectExpression(string $expression): string
    {
        $expression = trim($expression);

        if ($expression === '*') {
            return '*';
        }

        if (preg_match('/^(COUNT|SUM|AVG|MIN|MAX)\(\*\)(?:\s+AS\s+([A-Za-z_][A-Za-z0-9_]*))?$/i', $expression, $matches)) {
            $sql = strtoupper($matches[1]) . '(*)';

            if (!empty($matches[2])) {
                $sql .= ' AS ' . Database::quoteIdentifier($matches[2]);
            }

            return $sql;
        }

        if (preg_match('/^(.+?)\s+AS\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $expression, $matches)) {
            return $this->formatColumnReference($matches[1]) . ' AS ' . Database::quoteIdentifier($matches[2]);
        }

        return $this->formatColumnReference($expression);
    }

    private function formatColumnReference(string $column): string
    {
        $column = trim($column);

        if ($column === '*') {
            return '*';
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\.\*$/', $column)) {
            return Database::quoteIdentifier(strtok($column, '.')) . '.*';
        }

        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.([A-Za-z_][A-Za-z0-9_]*)$/', $column, $matches)) {
            return Database::quoteIdentifier($matches[1]) . '.' . Database::quoteIdentifier($matches[2]);
        }

        return Database::quoteIdentifier($column);
    }

    private function normalizeOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));
        $allowed = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS', 'IS NOT'];

        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException("Invalid SQL operator: {$operator}");
        }

        return $operator;
    }

    private function normalizeDirection(string $direction): string
    {
        $direction = strtoupper(trim($direction));

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Invalid sort direction: {$direction}");
        }

        return $direction;
    }

    private function normalizeJoinType(string $type): string
    {
        $type = strtoupper(trim($type));

        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
            throw new InvalidArgumentException("Invalid join type: {$type}");
        }

        return $type;
    }
}


if (!\class_exists('QueryBuilder', false) && !\interface_exists('QueryBuilder', false) && !\trait_exists('QueryBuilder', false)) {
    \class_alias(__NAMESPACE__ . '\\QueryBuilder', 'QueryBuilder');
}
