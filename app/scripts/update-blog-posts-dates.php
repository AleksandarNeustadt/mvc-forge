<?php
/**
 * Script to update blog_posts published_at and created_at dates
 * 
 * Sets all blog posts to have:
 * - published_at: 07.01.2026 12:00:00
 * - created_at: 07.01.2026 12:00:00
 * 
 * Run with: php scripts/update-blog-posts-dates.php
 */

$appPath = dirname(__DIR__);
require_once $appPath . '/bootstrap/app.php';
ap_bootstrap_cli_application($appPath);

echo "📝 Updating blog_posts dates...\n\n";

// Target date: 07.01.2026 12:00:00
$targetDate = '2026-01-07 12:00:00';
$targetTimestamp = strtotime($targetDate);

if ($targetTimestamp === false) {
    echo "❌ Error: Invalid date format\n";
    exit(1);
}

echo "Target date: {$targetDate}\n";
echo "Target timestamp: {$targetTimestamp}\n";
echo "Formatted: " . date('d.m.Y H:i:s', $targetTimestamp) . "\n\n";

// Check if table exists
try {
    $tables = Database::select("SHOW TABLES LIKE 'blog_posts'");
    if (empty($tables)) {
        echo "❌ Error: Table 'blog_posts' does not exist\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
    exit(1);
}

// Get current count
try {
    $count = Database::selectOne("SELECT COUNT(*) as count FROM blog_posts");
    $totalPosts = $count['count'] ?? 0;
    echo "Found {$totalPosts} blog posts\n\n";
    
    if ($totalPosts === 0) {
        echo "⚠️  No blog posts to update\n";
        exit(0);
    }
} catch (Exception $e) {
    echo "❌ Error counting posts: " . $e->getMessage() . "\n";
    exit(1);
}

// Ask for confirmation (skip if running non-interactively)
if (php_sapi_name() === 'cli' && !isset($argv[1])) {
    echo "⚠️  This will update ALL blog posts:\n";
    echo "   - published_at will be set to: {$targetDate}\n";
    echo "   - created_at will be set to: {$targetDate}\n\n";
    echo "Continue? (yes/no): ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "⏭️  Cancelled\n";
        exit(0);
    }
} else {
    echo "⚠️  This will update ALL blog posts:\n";
    echo "   - published_at will be set to: {$targetDate}\n";
    echo "   - created_at will be set to: {$targetDate}\n\n";
    echo "Running update...\n";
}

// Check column types
try {
    $columns = Database::select("SHOW COLUMNS FROM blog_posts WHERE Field IN ('published_at', 'created_at', 'updated_at')");
    $columnTypes = [];
    foreach ($columns as $col) {
        $columnTypes[$col['Field']] = strtoupper($col['Type']);
    }
    
    echo "Column types:\n";
    foreach ($columnTypes as $field => $type) {
        echo "   - {$field}: {$type}\n";
    }
    echo "\n";
    
    // Handle each column based on its type
    $publishedAtType = $columnTypes['published_at'] ?? 'INT';
    $createdAtType = $columnTypes['created_at'] ?? 'INT';
    $updatedAtType = $columnTypes['updated_at'] ?? 'INT';
    
    // Build UPDATE query based on column types
    $setParts = [];
    $params = [];
    
    // published_at - INT column, use integer timestamp
    if (strpos($publishedAtType, 'TIMESTAMP') !== false) {
        $setParts[] = "published_at = FROM_UNIXTIME(?)";
        $params[] = $targetTimestamp;
    } else {
        $setParts[] = "published_at = ?";
        $params[] = $targetTimestamp;
    }
    
    // created_at - TIMESTAMP column, use STR_TO_DATE for proper conversion
    if (strpos($createdAtType, 'TIMESTAMP') !== false) {
        // Use STR_TO_DATE to ensure proper conversion to TIMESTAMP
        $setParts[] = "created_at = STR_TO_DATE(?, '%Y-%m-%d %H:%i:%s')";
        $params[] = $targetDate; // Use date string directly: '2026-01-07 12:00:00'
    } else {
        $setParts[] = "created_at = ?";
        $params[] = $targetTimestamp;
    }
    
    // updated_at - TIMESTAMP column, use STR_TO_DATE for proper conversion
    // Note: updated_at might have ON UPDATE CURRENT_TIMESTAMP, but we can override it
    if (strpos($updatedAtType, 'TIMESTAMP') !== false) {
        $setParts[] = "updated_at = STR_TO_DATE(?, '%Y-%m-%d %H:%i:%s')";
        $params[] = $targetDate; // Use date string directly: '2026-01-07 12:00:00'
    } else {
        $setParts[] = "updated_at = ?";
        $params[] = $targetTimestamp;
    }
    
    $sql = "UPDATE blog_posts SET " . implode(", ", $setParts);
    echo "SQL: {$sql}\n";
    echo "Params: " . json_encode($params) . "\n\n";
    
    // Execute update
    try {
        Database::execute($sql, $params);
        $affectedRows = Database::selectOne("SELECT ROW_COUNT() as affected");
        echo "Affected rows: " . ($affectedRows['affected'] ?? 'unknown') . "\n";
    } catch (Exception $e) {
        echo "❌ Error during update: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    // Verify the update
    echo "Verifying update...\n";
    $sample = Database::selectOne("SELECT id, published_at, created_at, updated_at FROM blog_posts LIMIT 1");
    if ($sample) {
        echo "\nSample result:\n";
        echo "   - published_at: " . ($sample['published_at'] ?? 'NULL') . "\n";
        echo "   - created_at: " . ($sample['created_at'] ?? 'NULL') . "\n";
        echo "   - updated_at: " . ($sample['updated_at'] ?? 'NULL') . "\n";
    }
    
    echo "\n✅ Successfully updated {$totalPosts} blog posts\n";
    echo "   - published_at: {$targetDate}\n";
    echo "   - created_at: {$targetDate}\n";
    echo "   - updated_at: {$targetDate}\n";
    
} catch (Exception $e) {
    echo "\n❌ Error updating posts: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Done!\n";
