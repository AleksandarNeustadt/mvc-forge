<?php
/**
 * Migration: Create Contact Messages Table
 * 
 * Creates the contact_messages table for storing contact form submissions
 * Run with: php core/database/migrations/021_create_contact_messages_table.php
 */

require_once __DIR__ . '/../../config/Env.php';
Env::load(__DIR__ . '/../../../.env');

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../DatabaseTableBuilder.php';
require_once __DIR__ . '/../DatabaseBuilder.php';

echo "📧 Creating contact_messages table...\n\n";

try {
    $tables = DatabaseBuilder::getTables();
    if (in_array('contact_messages', $tables)) {
        echo "⚠️  Table 'contact_messages' already exists.\n";
        echo "❓ Drop and recreate? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'y') {
            echo "⏭️  Skipping...\n";
            exit(0);
        }
        
        echo "🗑️  Dropping existing table...\n";
        Database::execute("DROP TABLE IF EXISTS contact_messages");
    }

    $builder = new DatabaseTableBuilder('contact_messages');
    $builder->id()
        ->integer('user_id')->nullable()  // FK to users (if logged in)
        ->string('name', 255)  // Name from form
        ->string('email', 255)  // Email from form
        ->string('subject', 255)  // Subject
        ->text('message')  // Message content
        ->string('ip_address', 45)->nullable()  // IPv4 or IPv6
        ->text('user_agent')->nullable()  // Browser user agent
        ->string('status', 20)->default('unread')  // 'unread', 'read', 'replied'
        ->integer('read_at')->nullable()  // Timestamp when read
        ->integer('replied_at')->nullable()  // Timestamp when replied
        ->timestamps()
        ->create();

    // Create indexes
    Database::execute("CREATE INDEX idx_contact_messages_user_id ON contact_messages(user_id)");
    Database::execute("CREATE INDEX idx_contact_messages_status ON contact_messages(status)");
    Database::execute("CREATE INDEX idx_contact_messages_created_at ON contact_messages(created_at)");
    Database::execute("CREATE INDEX idx_contact_messages_email ON contact_messages(email)");

    // Add foreign key constraint if users table exists
    try {
        if (in_array('users', $tables)) {
            Database::execute("
                ALTER TABLE contact_messages 
                ADD CONSTRAINT fk_contact_messages_user 
                FOREIGN KEY (user_id) REFERENCES users(id) 
                ON DELETE SET NULL
            ");
            echo "✅ Foreign key constraint added!\n";
        }
    } catch (Exception $e) {
        echo "⚠️  Could not add foreign key constraint: " . $e->getMessage() . "\n";
        echo "   (This is okay if the constraint already exists or users table doesn't exist)\n";
    }

    echo "✅ Table 'contact_messages' created successfully!\n";
    echo "✅ Indexes created!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

