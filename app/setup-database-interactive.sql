-- Interactive Database Setup
-- Replace the values below with your preferences
-- Then run: mysql -u root -p < setup-database-interactive.sql

-- ============================================
-- CONFIGURATION (Edit these values)
-- ============================================
SET @db_name = 'aleksandar_pro';
SET @db_user = 'aleksandar_user';
SET @db_pass = 'change_this_password';  -- CHANGE THIS!

-- ============================================
-- EXECUTION (Don't edit below)
-- ============================================

-- Create database
SET @sql = CONCAT('CREATE DATABASE IF NOT EXISTS `', @db_name, '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create user
SET @sql = CONCAT("CREATE USER IF NOT EXISTS '", @db_user, "'@'localhost' IDENTIFIED BY '", @db_pass, "'");
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Grant privileges
SET @sql = CONCAT('GRANT ALL PRIVILEGES ON `', @db_name, '`.* TO ''', @db_user, '''@''localhost''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Flush privileges
FLUSH PRIVILEGES;

-- Use database
SET @sql = CONCAT('USE `', @db_name, '`');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    newsletter BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ Database setup completed!' AS message;

