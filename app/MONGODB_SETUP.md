# 🗄️ MongoDB Atlas Setup Guide

## 📋 Šta Mi Treba Od Vas

Da bismo povezali aplikaciju sa MongoDB Atlas, potrebni su nam sledeći podaci:

### 1. **MongoDB Connection String (URI)**

Ovo je najvažnije! Treba da imate oblik:
```
mongodb+srv://username:password@cluster.mongodb.net/database?retryWrites=true&w=majority
```

### 2. **Database Name** (opciono)
Default: `aleksandar_pro`

---

## 🚀 Korak po Korak: MongoDB Atlas Setup

### Korak 1: Registracija na MongoDB Atlas

1. Idite na [https://cloud.mongodb.com/](https://cloud.mongodb.com/)
2. Kliknite **"Sign Up"** (ili **"Try Free"**)
3. Popunite registraciju (email, password)
4. Potvrdite email

### Korak 2: Kreiranje Free Cluster-a

1. Nakon login-a, kliknite **"Build a Database"**
2. Odaberite **"M0 FREE"** (Free tier)
3. Odaberite **Cloud Provider** (AWS, Google Cloud, ili Azure)
4. Odaberite **Region** (najbliže vašoj lokaciji)
5. Unesite **Cluster Name** (npr. `aleksandar-pro-cluster`)
6. Kliknite **"Create"**

⏳ **Napomena:** Cluster se kreira za ~3-5 minuta

### Korak 3: Kreiranje Database User-a

1. Kada se cluster kreira, pojaviće se dialog za **"Create Database User"**
2. Unesite:
   - **Username**: (npr. `aleksandar_admin`)
   - **Password**: (generiši jak password - **SAČUVAJTE GA!**)
3. Odaberite **"Built-in Role"** → **"Atlas Admin"** (ili **"Read and write to any database"**)
4. Kliknite **"Create Database User"**

💡 **VAŽNO:** Sačuvajte username i password! Nećete ih moći da vidite ponovo!

### Korak 4: Whitelist IP Adresa

1. U sekciji **"Network Access"** (levi meni)
2. Kliknite **"Add IP Address"**
3. Za development, kliknite **"Add Current IP Address"**
4. Za produkciju, dodajte IP adresu vašeg servera
5. Ili odaberite **"Allow Access from Anywhere"** (0.0.0.0/0) - **Nije preporučeno za produkciju!**

### Korak 5: Dobijanje Connection String-a

1. Kliknite **"Connect"** dugme pored vašeg cluster-a
2. Odaberite **"Connect your application"**
3. Odaberite **Driver**: `PHP`
4. Odaberite **Version**: `1.11` ili noviji
5. Kopirajte **Connection String**

Connection string izgleda ovako:
```
mongodb+srv://aleksandar_admin:<password>@aleksandar-pro-cluster.xxxxx.mongodb.net/?retryWrites=true&w=majority
```

### Korak 6: Dodavanje Database Name u Connection String

Dodajte ime baze na kraju, pre `?`:
```
mongodb+srv://aleksandar_admin:<password>@aleksandar-pro-cluster.xxxxx.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
```

**Zamenite:**
- `<password>` → Vaš password (bez `<>`)
- `aleksandar_pro` → Vaše ime baze podataka (možete koristiti bilo koje)

---

## 📝 Konfiguracija u Aplikaciji

### Korak 7: Instalacija MongoDB PHP Extension (OBAVEZNO!)

**VAŽNO:** MongoDB PHP Library ZAHTEVA da MongoDB PHP Extension bude instaliran pre toga!

Pogledajte detaljne instrukcije u: **`MONGODB_PHP_EXTENSION_INSTALL.md`**

**Brzi način (preko package manager-a):**
```bash
# Za PHP 8.1
sudo apt install php8.1-mongodb

# Za PHP 8.2
sudo apt install php8.2-mongodb

# Proverite verziju PHP-a:
php -v
```

**Alternativno (preko PECL-a):**
```bash
sudo apt install php-pear php-dev build-essential
sudo pecl install mongodb
# Dodajte "extension=mongodb" u php.ini
```

**Provera:**
```bash
php -m | grep mongodb
```

### Korak 8: Instalacija Composer Dependencies

```bash
# U root direktorijumu projekta
composer install
```

Ovo će instalirati MongoDB PHP library.

### Korak 9: Konfiguracija .env Fajla

Kopirajte `.env.example` u `.env`:

```bash
cp .env.example .env
```

Zatim otvorite `.env` i dodajte vaše MongoDB podatke:

```env
# MongoDB Atlas Connection
MONGODB_URI=mongodb+srv://aleksandar_admin:VAŠ_PASSWORD@aleksandar-pro-cluster.xxxxx.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
MONGODB_DATABASE=aleksandar_pro
```

**VAŽNO:**
- Zamenite `VAŠ_PASSWORD` sa stvarnim password-om
- Zamenite `aleksandar-pro-cluster.xxxxx.mongodb.net` sa vašim cluster host-om
- Zamenite `aleksandar_pro` sa vašim database imenom (ako ste koristili drugačije)

### Korak 10: Test Konekcije

Kreirajte test fajl `test-mongodb.php` u root direktorijumu:

```php
<?php
require_once 'vendor/autoload.php';
require_once 'core/classes/Env.php';
require_once 'core/classes/Database.php';

Env::load(__DIR__ . '/.env');

try {
    if (Database::test()) {
        echo "✅ MongoDB konekcija uspešna!\n";
    } else {
        echo "❌ MongoDB konekcija neuspešna!\n";
    }
} catch (Exception $e) {
    echo "❌ Greška: " . $e->getMessage() . "\n";
}
```

Pokrenite:
```bash
php test-mongodb.php
```

Ako vidite ✅, konekcija radi!

---

## 🔒 Bezbednosne Preporuke

### Za Development:
- ✅ Možete koristiti **"Allow Access from Anywhere"** (0.0.0.0/0)
- ✅ Sačuvajte `.env` fajl u `.gitignore` (već je tamo)

### Za Produkciju:
- ❌ **NE** koristite "Allow Access from Anywhere"
- ✅ Dodajte samo IP adresu vašeg servera
- ✅ Koristite jak password za database user-a
- ✅ Koristite različite credentials za production i development
- ✅ Razmotrite MongoDB Atlas Encryption at Rest

---

## 📊 Šta Dalje?

Nakon što je konekcija uspešna:

1. ✅ **Database klasa je spremna** - `core/classes/Database.php`
2. ✅ **Base Model je spreman** - `core/models/Model.php`
3. ✅ **User Model je spreman** - `core/models/User.php`
4. 🔄 **Sledeći korak**: Integrisati User Model u AuthController

---

## 🆘 Troubleshooting

### Problem: "MongoDB connection failed"

**Rešenje:**
- Proverite da li je password ispravan u connection string-u
- Proverite da li je IP adresa whitelist-ovana u MongoDB Atlas
- Proverite da li je cluster aktivan (ne sleeping)

### Problem: "Authentication failed"

**Rešenje:**
- Proverite username i password
- Proverite da li je user kreiran sa odgovarajućim permissions

### Problem: "Connection timeout"

**Rešenje:**
- Proverite internet konekciju
- Proverite firewall settings
- Proverite da li je IP whitelist-ovan

### Problem: Composer install ne radi

**Rešenje:**
```bash
# Instalirajte Composer ako nemate
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Ili koristite lokalni
php composer.phar install
```

---

## 📚 Dodatni Resursi

- [MongoDB Atlas Documentation](https://docs.atlas.mongodb.com/)
- [MongoDB PHP Library](https://docs.mongodb.com/php-library/current/)
- [MongoDB Connection String Format](https://docs.mongodb.com/manual/reference/connection-string/)

---

## ✅ Checklist

- [ ] MongoDB Atlas account kreiran
- [ ] Free cluster kreiran
- [ ] Database user kreiran
- [ ] IP adresa whitelist-ovana
- [ ] Connection string kopiran
- [ ] `.env` fajl konfigurisan
- [ ] `composer install` izvršen
- [ ] Test konekcije prošao ✅

---

**Kada završite setup, javite mi i mi ćemo nastaviti sa integracijom! 🚀**

