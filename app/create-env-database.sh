#!/bin/bash

# Create .env file with database configuration template
# This script creates a .env file with all necessary database parameters

ENV_FILE=".env"

echo "Creating .env file with database configuration..."

# Check if .env already exists
if [ -f "$ENV_FILE" ]; then
    echo "⚠️  .env file already exists!"
    read -p "Do you want to overwrite it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cancelled."
        exit 1
    fi
    echo "Backing up existing .env to .env.backup.$(date +%Y%m%d_%H%M%S)"
    cp "$ENV_FILE" ".env.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Create .env file with database configuration
cat > "$ENV_FILE" << 'EOF'
# ============================================
# Application Configuration
# ============================================
APP_DEBUG=true
APP_ENV=local

# ============================================
# Database Configuration (PDO)
# ============================================
# Supported drivers: mysql, pgsql, sqlite
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=aleksandar_user
DB_PASSWORD=your_password_here
DB_CHARSET=utf8mb4

# ============================================
# Session Configuration
# ============================================
SESSION_LIFETIME=120
SESSION_SECURE=false

# ============================================
# Brand Configuration
# ============================================
BRAND_NAME=aleksandar.pro
BRAND_TAGLINE=Dark Protocol
PRIVACY_POLICY_URL=https://policies.google.com/privacy
EOF

echo "✅ .env file created successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Edit .env file and update database credentials:"
echo "   - DB_DATABASE=your_database_name"
echo "   - DB_USERNAME=your_username"
echo "   - DB_PASSWORD=your_password"
echo ""
echo "2. Make sure the database and user exist (use setup-database-simple.sh)"
echo ""
echo "3. Test connection with: php test-pdo.php"

