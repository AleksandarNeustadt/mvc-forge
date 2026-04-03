<?php
/**
 * Test MongoDB Connection
 * 
 * Usage: php test-mongodb.php
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("❌ Composer autoloader not found. Run 'composer install' first.\n");
}

// Load environment and database
require_once __DIR__ . '/core/config/Env.php';
require_once __DIR__ . '/core/database/Database.php';

try {
    // Load .env file
    Env::load(__DIR__ . '/.env');
    
    echo "🔍 Testing MongoDB connection...\n\n";
    
    // Test connection
    if (Database::test()) {
        echo "✅ MongoDB connection successful!\n\n";
        
        // Test database info
        $db = Database::getDatabase();
        $dbName = Database::getDatabaseName();
        
        echo "📊 Database Information:\n";
        echo "   Name: {$dbName}\n";
        
        // List collections (if any)
        $collections = $db->listCollections();
        $collectionCount = 0;
        foreach ($collections as $collection) {
            $collectionCount++;
        }
        
        echo "   Collections: {$collectionCount}\n";
        
        if ($collectionCount === 0) {
            echo "\n💡 Tip: No collections found. This is normal for a new database.\n";
        }
        
        echo "\n✅ Everything looks good! You can now use MongoDB in your application.\n";
        
    } else {
        echo "❌ MongoDB connection failed!\n";
        echo "   Please check your MONGODB_URI in .env file.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting:\n";
    echo "   1. Check if MONGODB_URI is set in .env file\n";
    echo "   2. Verify your MongoDB Atlas credentials\n";
    echo "   3. Ensure your IP is whitelisted in MongoDB Atlas\n";
    echo "   4. Check your internet connection\n";
    exit(1);
}

