#!/bin/bash

# Create .env file with PDO database configuration
# This script creates/updates .env file with database parameters

ENV_FILE=".env"

echo "📝 Creating/Updating .env file for PDO database..."
echo ""

# Check if .env exists
if [ -f "$ENV_FILE" ]; then
    echo "⚠️  .env file already exists!"
    read -p "Do you want to overwrite it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cancelled. .env file unchanged."
        exit 0
    fi
    echo "Backing up existing .env to .env.backup.$(date +%Y%m%d_%H%M%S)"
    cp "$ENV_FILE" ".env.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Create new .env file
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
echo "📋 Next steps:"
echo "1. Edit .env file and update database credentials:"
echo "   nano .env"
echo "   # or: vim .env"
echo "   # or: code .env"
echo ""
echo "2. Update these values:"
echo "   - DB_DATABASE=aleksandar_pro  (your database name)"
echo "   - DB_USERNAME=aleksandar_user (your database username)"
echo "   - DB_PASSWORD=your_password_here (your database password)"
echo ""
echo "3. Make sure the database and user exist:"
echo "   bash setup-database-simple.sh"
echo ""
echo "4. Test connection:"
echo "   php test-pdo.php"

