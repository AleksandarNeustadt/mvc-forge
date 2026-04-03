<?php
/**
 * Run World Migrations (Continents and Regions)
 * 
 * Executes migrations for continents and regions tables
 * Run with: php run-world-migrations.php
 */

echo "🌍 Running World migrations (Continents and Regions)...\n\n";

$migrations = [
    '033_create_continents_table.php',
    '034_create_regions_table.php',
    '035_add_continent_region_to_languages.php',
    '036_seed_continents_regions.php',
];

$migrationDir = __DIR__ . '/core/database/migrations';
$failed = [];

foreach ($migrations as $migration) {
    $file = $migrationDir . '/' . $migration;
    
    if (!file_exists($file)) {
        echo "⚠️  Migration file not found: {$migration}\n";
        continue;
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Running: {$migration}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    try {
        require $file;
        echo "\n✅ {$migration} completed successfully!\n\n";
    } catch (Exception $e) {
        echo "\n❌ {$migration} failed: " . $e->getMessage() . "\n\n";
        $failed[] = $migration;
    } catch (Error $e) {
        echo "\n❌ {$migration} failed: " . $e->getMessage() . "\n\n";
        $failed[] = $migration;
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
if (empty($failed)) {
    echo "✅ All World migrations completed successfully!\n";
} else {
    echo "❌ Some migrations failed:\n";
    foreach ($failed as $migration) {
        echo "   - {$migration}\n";
    }
}
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
