<?php

/**
 * Migration: Make page/blog slugs and routes unique per language.
 *
 * Legacy installs created single-column UNIQUE indexes on slug/route, which blocks
 * valid cases such as /sr/novosti and /de/novosti sharing the same route segment.
 */

require_once __DIR__ . '/../../config/Env.php';
$envFile = getenv('APP_ENV_FILE') ?: '.env';
Env::load(__DIR__ . '/../../../' . $envFile);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "Updating content uniqueness indexes to be language-aware...\n\n";

try {
    $driver = Database::getDriver();

    ap_drop_unique_index_if_exists('pages', 'idx_slug');
    ap_drop_unique_index_if_exists('pages', 'idx_route');
    ap_drop_unique_index_if_exists('blog_posts', 'idx_slug');
    ap_drop_unique_index_if_exists('blog_categories', 'idx_slug');
    ap_drop_unique_index_if_exists('blog_tags', 'idx_slug');

    ap_create_unique_index_if_missing('pages', 'idx_pages_language_slug_unique', ['language_id', 'slug'], $driver);
    ap_create_unique_index_if_missing('pages', 'idx_pages_language_route_unique', ['language_id', 'route'], $driver);
    ap_create_unique_index_if_missing('blog_posts', 'idx_blog_posts_language_slug_unique', ['language_id', 'slug'], $driver);
    ap_create_unique_index_if_missing('blog_categories', 'idx_blog_categories_language_slug_unique', ['language_id', 'slug'], $driver);
    ap_create_unique_index_if_missing('blog_tags', 'idx_blog_tags_language_slug_unique', ['language_id', 'slug'], $driver);

    echo "Language-aware unique indexes updated successfully.\n";
} catch (Exception $exception) {
    echo 'Error: ' . $exception->getMessage() . "\n";
    exit(1);
}

function ap_drop_unique_index_if_exists(string $table, string $indexName): void
{
    if (!ap_index_exists($table, $indexName, true)) {
        return;
    }

    $quotedTable = Database::quoteIdentifier($table);
    $quotedIndex = Database::quoteIdentifier($indexName);
    $driver = Database::getDriver();

    if ($driver === 'pgsql') {
        Database::execute("DROP INDEX IF EXISTS {$quotedIndex}");
        return;
    }

    Database::execute("DROP INDEX {$quotedIndex} ON {$quotedTable}");
}

function ap_create_unique_index_if_missing(
    string $table,
    string $indexName,
    array $columns,
    string $driver
): void {
    if (ap_index_exists($table, $indexName)) {
        return;
    }

    $quotedTable = Database::quoteIdentifier($table);
    $quotedIndex = Database::quoteIdentifier($indexName);
    $quotedColumns = implode(', ', array_map([Database::class, 'quoteIdentifier'], $columns));

    if ($driver === 'pgsql') {
        Database::execute("CREATE UNIQUE INDEX {$quotedIndex} ON {$quotedTable} ({$quotedColumns})");
        return;
    }

    Database::execute("ALTER TABLE {$quotedTable} ADD UNIQUE INDEX {$quotedIndex} ({$quotedColumns})");
}

function ap_index_exists(string $table, string $indexName, bool $uniqueOnly = false): bool
{
    $driver = Database::getDriver();

    if ($driver === 'pgsql') {
        $row = Database::selectOne(
            'SELECT 1 AS index_exists
             FROM pg_indexes
             WHERE tablename = ?
               AND indexname = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return $row !== null;
    }

    $rows = Database::select("SHOW INDEX FROM " . Database::quoteIdentifier($table));
    foreach ($rows as $row) {
        $keyName = $row['Key_name'] ?? $row['key_name'] ?? null;
        if ($keyName !== $indexName) {
            continue;
        }

        if (!$uniqueOnly || (int) ($row['Non_unique'] ?? $row['non_unique'] ?? 1) === 0) {
            return true;
        }
    }

    return false;
}
