# 🚀 Final Setup Instructions

## ✅ Šta je Urađeno

1. ✅ MongoDB PHP Extension je instaliran
2. ✅ Password je dobijen
3. ✅ Composer.json je spreman

## 📝 Korak 1: Kreirati .env Fajl

Kreirajte `.env` fajl u root direktorijumu projekta sa sledećim sadržajem:

```bash
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
```

**ILI** koristite pripremljenu skriptu:

```bash
chmod +x setup-env.sh
./setup-env.sh
```

## 📦 Korak 2: Instalirati Composer Dependencies

```bash
composer install
```

## ✅ Korak 3: Testirati Konekciju

```bash
php test-mongodb.php
```

Ako vidite ✅, konekcija radi!

## 🎉 Gotovo!

Nakon uspešne konekcije, možete:
- Koristiti User Model u aplikaciji
- Implementirati autentikaciju
- Raditi sa bazom podataka

