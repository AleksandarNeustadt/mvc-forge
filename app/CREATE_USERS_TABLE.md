# 📋 Kreiranje Users Tabele i Inicijalnog Korisnika

## 🎯 Šta treba uraditi:

1. Kreirati tabelu `users` sa svim potrebnim poljima
2. Dodati inicijalnog admin korisnika

## 🚀 Brzi Start

### Opcija 1: Sve u jednoj komandi (preporučeno)

```bash
cd /var/www/aleksandar.pro
php setup-initial-database.php
```

Ova skripta će:
- ✅ Kreirati tabelu `users` sa svim potrebnim poljima
- ✅ Interaktivno kreirati admin korisnika
- ✅ Automatski hash-ovati lozinku
- ✅ Generisati slug

### Opcija 2: Odvojeno (migration + seed)

```bash
# 1. Kreiraj tabelu
php core/database/migrations/001_create_users_table.php

# 2. Kreiraj admin korisnika
php core/database/seeds/001_create_admin_user.php
```

## 📊 Struktura Users Tabele

Tabela `users` sadrži sledeća polja:

| Polje | Tip | Opis |
|-------|-----|------|
| `id` | INT PRIMARY KEY | Auto-increment ID |
| `username` | VARCHAR(100) UNIQUE | Korisničko ime |
| `email` | VARCHAR(255) UNIQUE | Email adresa |
| `password_hash` | VARCHAR(255) | Hash-ovana lozinka |
| `first_name` | VARCHAR(100) NULL | Ime |
| `last_name` | VARCHAR(100) NULL | Prezime |
| `slug` | VARCHAR(255) UNIQUE NULL | SEO-friendly slug |
| `avatar` | VARCHAR(500) NULL | Avatar URL |
| `newsletter` | BOOLEAN | Newsletter pretplata |
| `email_verified_at` | INT NULL | Timestamp verifikacije |
| `last_login_at` | INT NULL | Poslednji login timestamp |
| `last_login_ip` | VARCHAR(45) NULL | IP adresa poslednjeg logina |
| `created_at` | TIMESTAMP | Kreiranje timestamp |
| `updated_at` | TIMESTAMP | Ažuriranje timestamp |

## 👤 Inicijalni Admin Korisnik

Skripta će vas pitati za:
- **Email** (default: `admin@aleksandar.pro`)
- **Username** (default: `admin`)
- **Password** (obavezno)
- **First name** (default: `Admin`)
- **Last name** (default: `User`)
- **Newsletter** (y/N)

## ✅ Provera

Nakon kreiranja, možete proveriti:

```bash
# Konektujte se na bazu
mysql -u aleksandar_user -p aleksandar_pro

# Proverite tabelu
DESCRIBE users;

# Proverite korisnika
SELECT id, username, email, first_name, last_name FROM users;
```

## 🔐 Login

Nakon kreiranja, možete se ulogovati na:
```
/sr/login
```

Koristite email i password koji ste uneli tokom kreiranja.

## ⚠️ Napomene

- Tabela koristi TableBuilder za kreiranje (ne rucno SQL)
- Lozinke se automatski hash-uju koristeći `Security::hashPassword()`
- Slug se automatski generiše iz username-a
- Ako tabela već postoji, skripta će pitati da li želite da je obrišete i ponovo kreirate

