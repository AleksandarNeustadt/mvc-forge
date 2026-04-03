# ⚡ Quick Setup - Database i .env

## 🎯 Najbrži Način (2 koraka)

### Korak 1: Kreirajte Database i User-a

**Opcija A: Python Script (preporučeno - bolji za paste)**

```bash
chmod +x setup-database.py
python3 setup-database.py
```

**Opcija B: Bash Script (takođe podržava paste)**

```bash
chmod +x setup-database-simple.sh
./setup-database-simple.sh
```

**Oba scripta:**
- ✅ Prihvataju paste password-a (skriveno)
- ✅ Traže potvrdu password-a
- ✅ Automatski kreiraju database, user-a i tabelu

---

### Korak 2: Ažurirajte .env Fajl

**Opcija A: Automatski (script)**

```bash
chmod +x update-env-database.sh
./update-env-database.sh
```

**Opcija B: Ručno**

Dodajte u `.env` fajl:

```env
# Database Configuration (PDO)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=aleksandar_user
DB_PASSWORD=VAŠA_ŠIFRA_OVDE
DB_CHARSET=utf8mb4
```

---

### Korak 3: Testirajte

```bash
php test-pdo.php
```

---

## 💡 O Paste Funkciji

Kada script traži password:
- Možete **paste-ovati** password (Ctrl+Shift+V ili desni klik → Paste)
- Password **neće biti vidljiv** na ekranu (bezbedno)
- Script će tražiti potvrdu password-a

---

**To je to! 🚀**

