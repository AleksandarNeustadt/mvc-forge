<?php

require_once __DIR__ . '/../../config/Env.php';
$envFile = getenv('APP_ENV_FILE') ?: '.env';
Env::load(__DIR__ . '/../../../' . $envFile);

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "Adding translation_group_id columns to content tables...\n\n";

try {
    foreach (['pages', 'blog_posts', 'blog_categories', 'blog_tags'] as $tableName) {
        if (!in_array($tableName, DatabaseBuilder::getTables(), true)) {
            continue;
        }

        $columns = DatabaseBuilder::getTableColumns($tableName);
        $hasTranslationGroup = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? null) === 'translation_group_id') {
                $hasTranslationGroup = true;
                break;
            }
        }

        if (!$hasTranslationGroup) {
            Database::execute(
                "ALTER TABLE " . Database::quoteIdentifier($tableName)
                . " ADD COLUMN translation_group_id VARCHAR(64) NULL"
            );
        }

        $indexName = "idx_{$tableName}_translation_group";
        $rows = Database::select("SHOW INDEX FROM " . Database::quoteIdentifier($tableName));
        $indexExists = false;
        foreach ($rows as $row) {
            if (($row['Key_name'] ?? null) === $indexName) {
                $indexExists = true;
                break;
            }
        }

        if (!$indexExists) {
            Database::execute(
                "CREATE INDEX " . Database::quoteIdentifier($indexName)
                . " ON " . Database::quoteIdentifier($tableName)
                . " (translation_group_id)"
            );
        }

        $rows = Database::select(
            "SELECT id FROM " . Database::quoteIdentifier($tableName)
            . " WHERE translation_group_id IS NULL OR translation_group_id = ''"
        );
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            Database::execute(
                "UPDATE " . Database::quoteIdentifier($tableName)
                . " SET translation_group_id = ? WHERE id = ?",
                [$tableName . '-' . $id, $id]
            );
        }
    }

    echo "translation_group_id columns added successfully.\n";
} catch (Exception $exception) {
    echo 'Error: ' . $exception->getMessage() . "\n";
    exit(1);
}
