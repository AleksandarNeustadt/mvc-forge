# 🗄️ Database Setup Guide

## 📋 Opcije za Kreiranje Database i User-a

Imate **3 načina** da kreiramo database i korisnika:

---

## 🚀 Opcija 1: Interaktivni Script (PREPORUČENO)

Najlakši način - script će vas pitati za sve podatke:

```bash
chmod +x setup-database.sh
./setup-database.sh
```

Script će vas pitati za:
- Database name (default: `aleksandar_pro`)
- Username (default: `aleksandar_user`)
- Password (morate uneti)

**Primer:**
```bash
$ ./setup-database.sh
📝 Enter MySQL root password when prompted

Database name [aleksandar_pro]: 
Database username [aleksandar_user]: 
Database password: [hidden]
```

---

## 📝 Opcija 2: SQL Fajl (Ručno)

Ako želite više kontrole:

### Korak 1: Editujte `setup-database-interactive.sql`

Promenite vrednosti na vrhu fajla:
```sql
SET @db_name = 'aleksandar_pro';
SET @db_user = 'aleksandar_user';
SET @db_pass = 'VAŠA_ŠIFRA_OVDE';  -- PROMENITE OVO!
```

### Korak 2: Pokrenite SQL fajl

```bash
mysql -u root -p < setup-database-interactive.sql
```

---

## 🎯 Opcija 3: Direktno u MySQL Console

### Korak 1: Prijavite se u MySQL

```bash
mysql -u root -p
```

### Korak 2: Pokrenite SQL komande

```sql
-- Kreiraj database
CREATE DATABASE aleksandar_pro 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Kreiraj korisnika
CREATE USER 'aleksandar_user'@'localhost' IDENTIFIED BY 'vaša_šifra_ovde';

-- Dodeli privilegije
GRANT ALL PRIVILEGES ON aleksandar_pro.* TO 'aleksandar_user'@'localhost';

-- Primeni izmene
FLUSH PRIVILEGES;

-- Koristi database
USE aleksandar_pro;

-- Kreiraj users tabelu (koristite SQL iz database-setup.sql)
-- ... kopirajte CREATE TABLE komandu ...
```

---

## 📝 Korak 4: Ažurirajte .env Fajl

Nakon kreiranja database i user-a, ažurirajte `.env` fajl:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aleksandar_pro
DB_USERNAME=aleksandar_user
DB_PASSWORD=vaša_šifra_ovde
DB_CHARSET=utf8mb4
```

**VAŽNO:** Zamenite `aleksandar_user` i `vaša_šifra_ovde` sa stvarnim vrednostima!

---

## ✅ Korak 5: Testirajte Konekciju

```bash
php test-pdo.php
```

Trebalo bi da vidite:
```
✅ Database connection successful!
✅ QueryBuilder works!
```

---

## 🔒 Bezbednosni Saveti

1. ✅ **Koristite jak password** (min 12 karaktera)
2. ✅ **NE commit-ujte .env fajl** (već je u .gitignore)
3. ✅ **Za produkciju**, koristite različite credentials
4. ✅ **Za produkciju**, ograničite user privilegije (samo SELECT, INSERT, UPDATE, DELETE, bez DROP, CREATE DATABASE, itd.)

---

## 🆘 Troubleshooting

### Problem: "Access denied for user 'root'@'localhost'"

**Rešenje:**
- Proverite da li koristite pravilnu root password
- Pokušajte: `sudo mysql` (za Ubuntu/Debian)

### Problem: "ERROR 1045 (28000): Access denied"

**Rešenje:**
- Proverite username i password
- Pokušajte da se prijavite direktno: `mysql -u aleksandar_user -p`

### Problem: "ERROR 1064: Syntax error"

**Rešenje:**
- Proverite da li koristite MySQL 5.7+ ili MariaDB 10.2+
- `CREATE USER IF NOT EXISTS` zahteva MySQL 5.7.6+

### Problem: "Unknown database 'aleksandar_pro'"

**Rešenje:**
- Database nije kreiran
- Proverite da li je SQL komanda uspešno izvršena
- Pokrenite: `CREATE DATABASE aleksandar_pro;`

---

## 📚 Dodatni Resursi

- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Creating MySQL Users](https://dev.mysql.com/doc/refman/8.0/en/create-user.html)
- [Granting Privileges](https://dev.mysql.com/doc/refman/8.0/en/grant.html)

