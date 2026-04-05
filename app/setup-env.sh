#!/bin/bash

# Create .env file with MongoDB connection string

cat > .env << 'EOF'
# Application
APP_DEBUG=true
APP_ENV=local

# Brand
BRAND_NAME=aleksandar.pro
BRAND_TAGLINE=Dark Protocol

# Session
SESSION_LIFETIME=120
SESSION_SECURE=false

# MongoDB Atlas Connection
MONGODB_URI=mongodb+srv://your_username:your_password@your-cluster.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
MONGODB_DATABASE=aleksandar_pro
EOF

echo "✅ .env file created successfully!"
