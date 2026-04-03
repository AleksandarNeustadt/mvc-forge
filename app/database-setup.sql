-- MySQL Database Setup Script
-- Usage: mysql -u root -p < database-setup.sql

-- Create database
CREATE DATABASE IF NOT EXISTS aleksandar_pro 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Create user (adjust username and password as needed)
CREATE USER IF NOT EXISTS 'aleksandar_user'@'localhost' IDENTIFIED BY 'change_this_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON aleksandar_pro.* TO 'aleksandar_user'@'localhost';

-- Flush privileges to apply changes
FLUSH PRIVILEGES;

-- Use the database
USE aleksandar_pro;

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

-- Show success message
SELECT 'Database and user created successfully!' AS message;

