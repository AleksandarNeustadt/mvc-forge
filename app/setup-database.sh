#!/bin/bash

# Database Setup Script
# This script helps you create MySQL database and user interactively

echo "🗄️  MySQL Database Setup for aleksandar.pro"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}❌ MySQL client is not installed!${NC}"
    echo "   Install it with: sudo apt install mysql-client"
    exit 1
fi

echo -e "${YELLOW}📝 Enter MySQL root password when prompted${NC}"
echo ""

# Prompt for database name
read -p "Database name [aleksandar_pro]: " DB_NAME
DB_NAME=${DB_NAME:-aleksandar_pro}

# Prompt for username
read -p "Database username [aleksandar_user]: " DB_USER
DB_USER=${DB_USER:-aleksandar_user}

# Prompt for password
read -sp "Database password: " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    echo -e "${RED}❌ Password cannot be empty!${NC}"
    exit 1
fi

# Create SQL commands
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

SELECT '✅ Database and user created successfully!' AS message;
SELECT '✅ Users table created successfully!' AS message;
EOF
)

# Execute SQL
echo ""
echo -e "${YELLOW}🔄 Creating database and user...${NC}"
echo ""

mysql -u root -p <<< "$SQL"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Database setup completed successfully!${NC}"
    echo ""
    echo "📋 Database Information:"
    echo "   Database: ${DB_NAME}"
    echo "   Username: ${DB_USER}"
    echo "   Password: [hidden]"
    echo ""
    echo -e "${YELLOW}💡 Next steps:${NC}"
    echo "   1. Update .env file with these credentials"
    echo "   2. Run: php test-pdo.php"
else
    echo ""
    echo -e "${RED}❌ Database setup failed!${NC}"
    echo "   Please check your MySQL root password and try again."
    exit 1
fi

