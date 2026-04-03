# 🔧 Setup .env Fajla za Database

## Problem
`.env` fajl nije u root direktorijumu projekta, a potreban je za PDO database connection.

## Rešenje

Kreiranje `.env` fajla sa potrebnim parametrima.

### Korak 1: Kreirajte .env fajl

U root direktorijumu projekta (`/var/www/aleksandar.pro`) kreirajte `.env` fajl sa sledećim sadržajem:

```env
# Application
APP_DEBUG=true
APP_ENV=local

# Database (PDO)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=aleksandar_user
DB_PASSWORD=VAŠA_ŠIFRA_OVDE
DB_CHARSET=utf8mb4

# Session
SESSION_LIFETIME=120
SESSION_SECURE=false

# Brand
BRAND_NAME=aleksandar.pro
BRAND_TAGLINE=Dark Protocol
PRIVACY_POLICY_URL=https://policies.google.com/privacy
```

### Korak 2: Unesite podatke za bazu

**Zamenite:**
- `DB_DATABASE=aleksandar_pro` - sa nazivom vaše baze podataka
- `DB_USERNAME=aleksandar_user` - sa vašim korisničkim imenom
- `DB_PASSWORD=VAŠA_ŠIFRA_OVDE` - sa vašom šifrom

### Korak 3: Proverite da baza postoji

Ako baza još ne postoji, kreirajte je:

```bash
cd /var/www/aleksandar.pro
bash setup-database-simple.sh
```

Ili ručno:

```bash
mysql -u root -p
CREATE DATABASE aleksandar_pro;
CREATE USER 'aleksandar_user'@'localhost' IDENTIFIED BY 'vaša_šifra';
GRANT ALL PRIVILEGES ON aleksandar_pro.* TO 'aleksandar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Korak 4: Testirajte konekciju

```bash
cd /var/www/aleksandar.pro
php test-pdo.php
```

## Brz način (jedna komanda)

```bash
cd /var/www/aleksandar.pro && cat > .env << 'ENVEOF'
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
ENVEOF

# Zatim editirajte i unesite prave podatke
nano .env
```

## 📝 Napomene

- `.env` fajl je u `.gitignore`, tako da se ne commit-uje u git
- Nikada ne delite `.env` fajl javno - sadrži osetljive podatke
- Za produkciju, postavite `APP_DEBUG=false` i `SESSION_SECURE=true`

