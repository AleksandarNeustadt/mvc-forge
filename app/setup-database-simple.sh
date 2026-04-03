#!/bin/bash

# Simple Database Setup - Password can be pasted (hidden)

echo "🗄️  MySQL Database Setup"
echo ""
echo "💡 Tip: You can paste passwords - they won't be visible"
echo ""

# Get database name
read -p "Database name [aleksandar_pro]: " DB_NAME
DB_NAME=${DB_NAME:-aleksandar_pro}

# Get username
read -p "Database username [aleksandar_user]: " DB_USER
DB_USER=${DB_USER:-aleksandar_user}

# Get password (hidden - supports paste)
echo ""
echo "📝 Enter database password (you can paste it - it's hidden):"
read -sp "Password: " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    echo "❌ Password cannot be empty!"
    exit 1
fi

# Confirm password
read -sp "Confirm password: " DB_PASS_CONFIRM
echo ""

if [ "$DB_PASS" != "$DB_PASS_CONFIRM" ]; then
    echo "❌ Passwords don't match!"
    exit 1
fi

# Get MySQL root password
echo ""
echo "🔐 Enter MySQL root password:"
read -sp "Root password: " ROOT_PASS
echo ""

if [ -z "$ROOT_PASS" ]; then
    echo "❌ MySQL root password cannot be empty!"
    exit 1
fi

# Create SQL
SQL=$(cat <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';

GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

FLUSH PRIVILEGES;

USE \`${DB_NAME}\`;

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

SELECT '✅ Database created successfully!' AS message;
EOF
)

# Execute
echo ""
echo "🔄 Creating database and user..."
echo ""

mysql -u root -p"${ROOT_PASS}" <<< "$SQL" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database setup completed successfully!"
    echo ""
    echo "📋 Database Information:"
    echo "   Database: ${DB_NAME}"
    echo "   Username: ${DB_USER}"
    echo "   Password: [hidden]"
    echo ""
    echo "💡 Next steps:"
    echo "   1. Update .env file with these credentials"
    echo "   2. Run: php test-pdo.php"
else
    echo ""
    echo "❌ Database setup failed!"
    echo "   Please check your MySQL root password and try again."
    exit 1
fi

