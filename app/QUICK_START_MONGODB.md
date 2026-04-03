# ⚡ Quick Start: MongoDB Atlas Povezivanje

## 📦 Korak 1: Instalacija MongoDB PHP Extension (OBAVEZNO!)

**VAŽNO:** MongoDB PHP Library ZAHTEVA da MongoDB PHP Extension bude instaliran pre Composer-a!

**Brzi način:**
```bash
# Proverite PHP verziju
php -v

# Instalirajte extension (zamenite 8.1 sa vašom verzijom)
sudo apt install php8.1-mongodb

# Proverite instalaciju
php -m | grep mongodb
```

Detaljne instrukcije: `MONGODB_PHP_EXTENSION_INSTALL.md`

## 📦 Korak 2: Instalacija Composer Dependencies

```bash
composer install
```

## 🔑 Korak 3: MongoDB Atlas Setup

### 3.1. Registrujte se na MongoDB Atlas
1. Idite na [https://cloud.mongodb.com/](https://cloud.mongodb.com/)
2. Kliknite **"Sign Up"** (ili **"Try Free"**)
3. Potvrdite email

### 3.2. Kreirajte Free Cluster
1. Kliknite **"Build a Database"**
2. Odaberite **"M0 FREE"** (Free tier)
3. Odaberite Cloud Provider i Region
4. Unesite Cluster Name
5. Kliknite **"Create"**

### 3.3. Kreirajte Database User
1. Unesite **Username** i **Password** (SAČUVAJTE GA!)
2. Odaberite **"Atlas Admin"** role
3. Kliknite **"Create Database User"**

### 3.4. Whitelist IP Adresu
1. U **"Network Access"** sekciji
2. Kliknite **"Add IP Address"**
3. Kliknite **"Add Current IP Address"** (za development)
4. Ili unesite IP vašeg servera (za produkciju)

### 3.5. Dobijte Connection String
1. Kliknite **"Connect"** pored cluster-a
2. Odaberite **"Connect your application"**
3. Driver: `PHP`, Version: `1.11` ili noviji
4. Kopirajte Connection String

**Primer:**
```
mongodb+srv://username:password@cluster.mongodb.net/?retryWrites=true&w=majority
```

**Dodajte database name:**
```
mongodb+srv://username:password@cluster.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
```

## 📝 Korak 4: Konfiguracija .env Fajla

```bash
# Kopirajte .env.example
cp .env.example .env
```

Zatim otvorite `.env` i dodajte:

```env
MONGODB_URI=mongodb+srv://username:VAŠ_PASSWORD@cluster.mongodb.net/aleksandar_pro?retryWrites=true&w=majority
MONGODB_DATABASE=aleksandar_pro
```

**Zamenite:**
- `username` → Vaš database username
- `VAŠ_PASSWORD` → Vaš database password (bez `<>`)
- `cluster.mongodb.net` → Vaš cluster host
- `aleksandar_pro` → Ime vaše baze (opciono)

## ✅ Korak 5: Test Konekcije

```bash
php test-mongodb.php
```

Ako vidite ✅, sve radi!

## 🎯 Šta Dalje?

Nakon uspešne konekcije:
- ✅ Database klasa je spremna
- ✅ Model base klasa je spremna  
- ✅ User Model je spreman
- 🔄 Sledeći korak: Integracija u AuthController

## 📚 Detaljne Instrukcije

Za detaljnije instrukcije, pogledajte: `MONGODB_SETUP.md`

