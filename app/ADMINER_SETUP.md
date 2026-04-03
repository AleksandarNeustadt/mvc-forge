# 🔧 Adminer Setup Guide

## 📋 Šta je Adminer?

Adminer je lightweight database management tool (alternativa phpMyAdmin-u) koji vam omogućava da upravljate bazom podataka direktno iz browser-a.

## ✅ Instalacija

Fajl `baza.php` je već preuzet i pomeran u `public/` direktorijum.

## 🔐 Pristup Adminer-u

### URL

```
http://your-domain.com/baza.php
```

Ili lokalno:
```
http://localhost/baza.php
http://127.0.0.1/baza.php
```

## 🔑 Login Podaci

Kada se prijavljujete u Adminer, koristite:

- **System:** MySQL
- **Server:** `127.0.0.1` ili `localhost`
- **Username:** Vaš database username (npr. `aleksandar_user`)
- **Password:** Vaš database password
- **Database:** `aleksandar_pro` (ili vaš database name)

## ⚙️ .htaccess Izuzetak

Dodat je izuzetak u `.htaccess` fajlu da `baza.php` može da se pristupa direktno bez rutiranja:

```apache
# Allow direct access to specific files (Adminer, etc.)
RewriteCond %{REQUEST_URI} ^/(baza\.php|adminer\.php|phpmyadmin) [NC]
RewriteRule ^ - [L]
```

## 🔒 Bezbednost

**VAŽNO za produkciju:**

1. ✅ **Zakljucajte Adminer sa password-om**
   - Kreirajte `.htpasswd` fajl
   - Dodajte u `.htaccess`:
   ```apache
   <Files "baza.php">
       AuthType Basic
       AuthName "Restricted Access"
       AuthUserFile /path/to/.htpasswd
       Require valid-user
   </Files>
   ```

2. ✅ **Ili preimenujte fajl** u nešto složenije (npr. `db_admin_xyz123.php`)

3. ✅ **Ili uklonite iz produkcije** - koristite samo za development

4. ✅ **Koristite jak database password** - Adminer koristi database credentials

## 📚 Dodatne Informacije

- [Adminer Documentation](https://www.adminer.org/)
- [Adminer Features](https://www.adminer.org/en/)

---

**Adminer je spreman za korišćenje! 🚀**

