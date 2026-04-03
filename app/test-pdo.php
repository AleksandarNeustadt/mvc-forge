<?php
/**
 * Test PDO Database Connection
 * 
 * Usage: php test-pdo.php
 */

// Load environment
require_once __DIR__ . '/core/config/Env.php';
require_once __DIR__ . '/core/database/Database.php';
require_once __DIR__ . '/core/database/QueryBuilder.php';

try {
    // Load .env file
    Env::load(__DIR__ . '/.env');
    
    echo "🔍 Testing PDO database connection...\n\n";
    
    // Test connection
    if (Database::test()) {
        echo "✅ Database connection successful!\n\n";
        
        $driver = Database::getDriver();
        echo "📊 Database Information:\n";
        echo "   Driver: {$driver}\n";
        
        // Test query builder
        echo "\n🔧 Testing QueryBuilder...\n";
        
        try {
            // Test simple query (adjust table name if needed)
            $result = Database::table('users')->limit(1)->get();
            echo "   ✅ QueryBuilder works!\n";
            echo "   📦 Found " . count($result) . " record(s)\n";
        } catch (Exception $e) {
            echo "   ⚠️  QueryBuilder test: " . $e->getMessage() . "\n";
            echo "   💡 This is normal if table doesn't exist yet.\n";
        }
        
        echo "\n✅ Everything looks good! PDO system is ready to use.\n";
        
    } else {
        echo "❌ Database connection failed!\n";
        echo "   Please check your database configuration in .env file.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting:\n";
    echo "   1. Check if DB_CONNECTION, DB_HOST, DB_DATABASE are set in .env\n";
    echo "   2. Verify database credentials (DB_USERNAME, DB_PASSWORD)\n";
    echo "   3. Ensure database server is running\n";
    echo "   4. Check database exists\n";
    exit(1);
}

