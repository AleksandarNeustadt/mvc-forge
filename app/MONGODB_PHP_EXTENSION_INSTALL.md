# 🔧 MongoDB PHP Extension Instalacija

MongoDB PHP Library (koju koristimo) **ZAHTEVA** da MongoDB PHP Extension bude instaliran pre toga.

## 📋 Provera: Da li je Extension Već Instaliran?

```bash
php -m | grep mongodb
```

Ili:

```bash
php -r "echo extension_loaded('mongodb') ? '✅ Instaliran' : '❌ Nije instaliran';"
```

Ako vidite `✅ Instaliran`, možete preskočiti instalaciju i ići na `composer install`.

---

## 🚀 Instalacija MongoDB PHP Extension (Ubuntu/WSL)

### Korak 1: Instalirajte PECL (ako nemate)

```bash
sudo apt update
sudo apt install php-pear php-dev
```

### Korak 2: Instalirajte Build Dependencies

```bash
sudo apt install build-essential pkg-config
```

### Korak 3: Instalirajte MongoDB Extension

```bash
sudo pecl install mongodb
```

Ovo može potrajati nekoliko minuta (kompajlira se iz source-a).

**Napomena:** Ako dobijete grešku tipa "autoconf", instalirajte:
```bash
sudo apt install autoconf
```

### Korak 4: Dodajte Extension u PHP Config

Pronađite gde se nalazi vaš `php.ini`:

```bash
php --ini
```

Zatim otvorite `php.ini` i dodajte liniju:

```ini
extension=mongodb
```

**Alternativa:** Ako koristite `php.ini` u specifičnom direktorijumu, možete kreirati fajl:

```bash
echo "extension=mongodb" | sudo tee /etc/php/8.X/mods-available/mongodb.ini
sudo phpenmod mongodb  # Aktivira extension
```

**Zamenite `8.X`** sa vašom PHP verzijom (npr. `8.1`, `8.2`).

### Korak 5: Proverite Instalaciju

```bash
php -m | grep mongodb
```

Trebalo bi da vidite `mongodb` u listi.

Ili:

```bash
php -r "echo extension_loaded('mongodb') ? '✅ MongoDB extension je instaliran!' : '❌ Nije instaliran';"
```

### Korak 6: Restart Web Server-a (ako koristite)

```bash
# Za Apache
sudo systemctl restart apache2

# Za Nginx + PHP-FPM
sudo systemctl restart php8.X-fpm
sudo systemctl restart nginx

# Za PHP built-in server (nije potreban restart)
```

---

## 🔄 Alternativna Metoda: Preko Package Manager-a (Lakše)

Neki Ubuntu repozitorijumi imaju MongoDB extension kao package:

```bash
# Za PHP 8.1
sudo apt install php8.1-mongodb

# Za PHP 8.2
sudo apt install php8.2-mongodb

# Za PHP 8.3
sudo apt install php8.3-mongodb
```

**Napomena:** Zamenite verziju sa vašom PHP verzijom. Proverite verziju sa:

```bash
php -v
```

Ovo je **LAKŠE** nego PECL instalacija jer automatski postavlja sve!

---

## ✅ Sledeći Korak: Composer Install

Nakon što je extension instaliran, možete instalirati PHP Library:

```bash
composer install
```

---

## 🆘 Troubleshooting

### Problem: "pecl command not found"

**Rešenje:**
```bash
sudo apt install php-pear
```

### Problem: "autoconf not found"

**Rešenje:**
```bash
sudo apt install autoconf
```

### Problem: "Unable to locate package php8.X-mongodb"

**Rešenje:** Koristite PECL metodu umesto package manager-a.

### Problem: Extension se ne učitava

**Rešenje:**
1. Proverite da li je linija `extension=mongodb` u `php.ini`
2. Proverite putanju do `mongodb.so` fajla
3. Restart-ujte web server
4. Proverite da li je extension build-ovan za vašu PHP verziju

### Problem: PECL instalacija traje predugo

**Rešenje:** To je normalno - kompajlira se iz source-a. Probajte package manager metodu (brža).

---

## 📚 Dodatni Resursi

- [MongoDB PHP Extension Documentation](https://www.mongodb.com/docs/php-library/current/tutorial/install-php-extension/)
- [PECL MongoDB Package](https://pecl.php.net/package/mongodb)

