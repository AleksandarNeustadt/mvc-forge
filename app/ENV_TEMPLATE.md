# .env Template - Database Configuration

Kopirajte ovaj sadržaj u `.env` fajl u root direktorijumu projekta.

```env
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

# ============================================
# TinyMCE Configuration
# ============================================
TINYMCE_API_KEY=brq1pfwpevqpv06j06zrtua89frjkoc4h8rlso9r5htxws2m

# ============================================
# Email Configuration (SMTP)
# ============================================
# Use SMTP (true) or PHP mail() function (false)
MAIL_USE_SMTP=true

# SMTP Settings (for MailHog or real SMTP server)
# For MailHog (development): localhost:1025
# For production: your-smtp-server.com:587
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_AUTH=false
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=

# Email From Address
MAIL_FROM_ADDRESS=noreply@aleksandar.pro
MAIL_FROM_NAME=aleksandar.pro

# Application URL (for email links)
APP_URL=https://aleksandar.pro
```

## Kako kreirati .env fajl:

### Opcija 1: Direktno kreiranje
```bash
cd /var/www/aleksandar.pro
nano .env
# ili
vim .env
# ili
code .env
```

Zatim kopirajte sadržaj iznad.

### Opcija 2: Koristeći skriptu
```bash
cd /var/www/aleksandar.pro
bash create-env-database.sh
```

Zatim editirajte `.env` fajl i unesite prave podatke za bazu.

### Opcija 3: Jednom komandom
```bash
cd /var/www/aleksandar.pro
cat > .env << 'EOF'
APP_DEBUG=true
APP_ENV=local

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=aleksandar_user
DB_PASSWORD=your_password_here
DB_CHARSET=utf8mb4

SESSION_LIFETIME=120
SESSION_SECURE=false

BRAND_NAME=aleksandar.pro
BRAND_TAGLINE=Dark Protocol
PRIVACY_POLICY_URL=https://policies.google.com/privacy

TINYMCE_API_KEY=brq1pfwpevqpv06j06zrtua89frjkoc4h8rlso9r5htxws2m
EOF
```

## ⚠️ VAŽNO:

1. **Zamenite `your_password_here`** sa pravom šifrom za bazu podataka
2. **Zamenite `aleksandar_pro`** sa nazivom vaše baze (ako je drugačiji)
3. **Zamenite `aleksandar_user`** sa vašim korisničkim imenom za bazu
4. **Proverite da baza i korisnik postoje** (koristite `setup-database-simple.sh`)

