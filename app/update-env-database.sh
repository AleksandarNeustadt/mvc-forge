#!/bin/bash

# Update .env file with database credentials
# Usage: bash update-env-database.sh

echo "🔧 Updating .env file with database configuration..."
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "   Run: bash create-env.sh first"
    exit 1
fi

# Prompt for database credentials
read -p "Database name [aleksandar_pro]: " DB_NAME
DB_NAME=${DB_NAME:-aleksandar_pro}

read -p "Database username [aleksandar_user]: " DB_USER
DB_USER=${DB_USER:-aleksandar_user}

read -sp "Database password: " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    echo "❌ Password cannot be empty!"
    exit 1
fi

read -p "Database host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "Database port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

# Check if database section already exists
if grep -q "DB_CONNECTION=" .env; then
    echo "⚠️  Database configuration already exists in .env"
    read -p "Do you want to overwrite it? (y/n): " OVERWRITE
    if [ "$OVERWRITE" != "y" ]; then
        echo "❌ Cancelled"
        exit 0
    fi
    
    # Remove old database config
    sed -i '/^DB_CONNECTION=/d' .env
    sed -i '/^DB_HOST=/d' .env
    sed -i '/^DB_PORT=/d' .env
    sed -i '/^DB_DATABASE=/d' .env
    sed -i '/^DB_USERNAME=/d' .env
    sed -i '/^DB_PASSWORD=/d' .env
    sed -i '/^DB_CHARSET=/d' .env
fi

# Add database configuration before MongoDB section (if exists) or at the end
if grep -q "MONGODB_URI=" .env; then
    # Insert before MongoDB section
    sed -i '/^# MongoDB/i\
# Database Configuration (PDO)\
DB_CONNECTION=mysql\
DB_HOST='"$DB_HOST"'\
DB_PORT='"$DB_PORT"'\
DB_DATABASE='"$DB_NAME"'\
DB_USERNAME='"$DB_USER"'\
DB_PASSWORD='"$DB_PASS"'\
DB_CHARSET=utf8mb4\
' .env
else
    # Append at the end
    cat >> .env << EOF

# Database Configuration (PDO)
DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS
DB_CHARSET=utf8mb4
EOF
fi

echo ""
echo "✅ .env file updated successfully!"
echo ""
echo "📋 Database Configuration:"
echo "   DB_CONNECTION=mysql"
echo "   DB_HOST=$DB_HOST"
echo "   DB_PORT=$DB_PORT"
echo "   DB_DATABASE=$DB_NAME"
echo "   DB_USERNAME=$DB_USER"
echo "   DB_PASSWORD=[hidden]"
echo "   DB_CHARSET=utf8mb4"
echo ""
echo "💡 Next step: Run 'php test-pdo.php' to test connection"

