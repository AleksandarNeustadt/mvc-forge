<?php
/**
 * Migration: Add Country and Language Tracking
 * 
 * Migration 030
 * 
 * Adds:
 * - is_site_language column to languages table (boolean - true if language is used on site)
 * - country_code column to languages table (ISO 3166-1 alpha-2 country code for flag display)
 * - language_id column to ip_tracking table (foreign key to languages table)
 */

// Load environment first
require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

// Load required classes
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📋 Migration 030: Adding country and language tracking...\n\n";

try {
    $driver = Database::getDriver();
    
    // 1. Add is_site_language column to languages table
    echo "1. Adding 'is_site_language' column to languages table...\n";
    if ($driver === 'mysql') {
        $columns = Database::select("SHOW COLUMNS FROM languages LIKE 'is_site_language'");
        if (empty($columns)) {
            Database::execute("ALTER TABLE languages ADD COLUMN is_site_language BOOLEAN DEFAULT TRUE AFTER is_active");
            echo "   ✅ Added 'is_site_language' column\n";
        } else {
            echo "   ⚠️  Column 'is_site_language' already exists. Skipping...\n";
        }
    } elseif ($driver === 'pgsql') {
        try {
            $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'languages' AND column_name = 'is_site_language'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE languages ADD COLUMN is_site_language BOOLEAN DEFAULT TRUE");
                echo "   ✅ Added 'is_site_language' column\n";
            } else {
                echo "   ⚠️  Column 'is_site_language' already exists. Skipping...\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️  Could not check/add column: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Add country_code column to languages table
    echo "2. Adding 'country_code' column to languages table...\n";
    if ($driver === 'mysql') {
        $columns = Database::select("SHOW COLUMNS FROM languages LIKE 'country_code'");
        if (empty($columns)) {
            Database::execute("ALTER TABLE languages ADD COLUMN country_code VARCHAR(2) NULL AFTER flag");
            // Check if index exists before creating
            $indexes = Database::select("SHOW INDEX FROM languages WHERE Key_name = 'idx_languages_country_code'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_languages_country_code ON languages(country_code)");
            }
            echo "   ✅ Added 'country_code' column with index\n";
        } else {
            echo "   ⚠️  Column 'country_code' already exists. Skipping...\n";
            // Check if index exists, create if not
            $indexes = Database::select("SHOW INDEX FROM languages WHERE Key_name = 'idx_languages_country_code'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_languages_country_code ON languages(country_code)");
                echo "   ✅ Created index for 'country_code'\n";
            }
        }
    } elseif ($driver === 'pgsql') {
        try {
            $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'languages' AND column_name = 'country_code'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE languages ADD COLUMN country_code VARCHAR(2) NULL");
                Database::execute("CREATE INDEX IF NOT EXISTS idx_languages_country_code ON languages(country_code)");
                echo "   ✅ Added 'country_code' column with index\n";
            } else {
                echo "   ⚠️  Column 'country_code' already exists. Skipping...\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️  Could not check/add column: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Add language_id column to ip_tracking table
    echo "3. Adding 'language_id' column to ip_tracking table...\n";
    if ($driver === 'mysql') {
        $columns = Database::select("SHOW COLUMNS FROM ip_tracking LIKE 'language_id'");
        if (empty($columns)) {
            Database::execute("ALTER TABLE ip_tracking ADD COLUMN language_id INT NULL AFTER country_name");
            // Check if index exists before creating
            $indexes = Database::select("SHOW INDEX FROM ip_tracking WHERE Key_name = 'idx_ip_tracking_language_id'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_ip_tracking_language_id ON ip_tracking(language_id)");
            }
            echo "   ✅ Added 'language_id' column with index\n";
        } else {
            echo "   ⚠️  Column 'language_id' already exists. Skipping...\n";
            // Check if index exists, create if not
            $indexes = Database::select("SHOW INDEX FROM ip_tracking WHERE Key_name = 'idx_ip_tracking_language_id'");
            if (empty($indexes)) {
                Database::execute("CREATE INDEX idx_ip_tracking_language_id ON ip_tracking(language_id)");
                echo "   ✅ Created index for 'language_id'\n";
            }
        }
    } elseif ($driver === 'pgsql') {
        try {
            $columns = Database::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'ip_tracking' AND column_name = 'language_id'");
            if (empty($columns)) {
                Database::execute("ALTER TABLE ip_tracking ADD COLUMN language_id INTEGER NULL");
                Database::execute("CREATE INDEX IF NOT EXISTS idx_ip_tracking_language_id ON ip_tracking(language_id)");
                echo "   ✅ Added 'language_id' column with index\n";
            } else {
                echo "   ⚠️  Column 'language_id' already exists. Skipping...\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️  Could not check/add column: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Add foreign key constraint (optional, but recommended)
    echo "4. Adding foreign key constraint...\n";
    try {
        if ($driver === 'mysql') {
            // Check if foreign key already exists
            $fks = Database::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'ip_tracking' 
                AND COLUMN_NAME = 'language_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($fks)) {
                Database::execute("
                    ALTER TABLE ip_tracking 
                    ADD CONSTRAINT fk_ip_tracking_language_id 
                    FOREIGN KEY (language_id) REFERENCES languages(id) 
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
                echo "   ✅ Added foreign key constraint\n";
            } else {
                echo "   ⚠️  Foreign key constraint already exists. Skipping...\n";
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL foreign key check
            $fks = Database::select("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'ip_tracking' 
                AND constraint_type = 'FOREIGN KEY' 
                AND constraint_name LIKE '%language_id%'
            ");
            
            if (empty($fks)) {
                Database::execute("
                    ALTER TABLE ip_tracking 
                    ADD CONSTRAINT fk_ip_tracking_language_id 
                    FOREIGN KEY (language_id) REFERENCES languages(id) 
                    ON DELETE SET NULL ON UPDATE CASCADE
                ");
                echo "   ✅ Added foreign key constraint\n";
            } else {
                echo "   ⚠️  Foreign key constraint already exists. Skipping...\n";
            }
        }
    } catch (Exception $e) {
        echo "   ⚠️  Could not add foreign key constraint: " . $e->getMessage() . "\n";
        echo "   (This is not critical, continuing...)\n";
    }
    
    // 5. Set default is_site_language values for existing languages
    echo "5. Setting default is_site_language values...\n";
    // Mark all existing languages as site languages (they are already being used)
    Database::execute("UPDATE languages SET is_site_language = TRUE WHERE is_site_language IS NULL");
    echo "   ✅ Set default values\n";
    
    echo "\n✅ Migration 030 completed successfully!\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

