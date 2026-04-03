# 📧 MailHog Setup - Email Testing

MailHog je lokalni SMTP server koji hvata sve emailove za testiranje. Idealno za development!

## 🚀 Instalacija (WSL/Ubuntu)

### Opcija 1: Go Binary (Najlakše - Nema potrebe za Docker)

```bash
# Preuzmi MailHog
cd /tmp
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
sudo mv MailHog_linux_amd64 /usr/local/bin/mailhog

# Pokreni MailHog
mailhog
```

**Ili direktno pokreni bez instalacije:**
```bash
cd /tmp
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
./MailHog_linux_amd64
```

### Opcija 2: Docker (Ako imate Docker)

```bash
# Preuzmi MailHog
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
sudo mv MailHog_linux_amd64 /usr/local/bin/mailhog

# Pokreni MailHog
mailhog
```

## ⚙️ Konfiguracija

### 1. Dodaj u `.env` fajl:

```env
# Email Configuration
MAIL_USE_SMTP=true
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_AUTH=false
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=

MAIL_FROM_ADDRESS=noreply@aleksandar.pro
MAIL_FROM_NAME=aleksandar.pro
APP_URL=https://aleksandar.pro
```

### 2. Instaliraj PHPMailer (ako nije već instaliran):

```bash
cd /var/www/aleksandar.pro
composer install
# ili
composer require phpmailer/phpmailer
```

## 🎯 Korišćenje

### Pokreni MailHog:

```bash
# Docker
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

# Go Binary
mailhog
```

### Pristupi Web UI:

Otvori u browseru: **http://localhost:8025**

Svi emailovi će se prikazati tamo!

## 📝 Testiranje

1. **Registruj novog korisnika** - trebalo bi da primiš verification email
2. **Zatraži password reset** - trebalo bi da primiš reset email
3. **Proveri MailHog UI** - svi emailovi će biti tamo

## 🔧 Produkcija

Za produkciju, promeni `.env` na pravi SMTP server:

```env
MAIL_USE_SMTP=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_AUTH=true
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

## 🐛 Troubleshooting

### MailHog ne radi?
```bash
# Proveri da li je pokrenut
docker ps | grep mailhog
# ili
ps aux | grep mailhog

# Proveri portove
netstat -tulpn | grep 1025
netstat -tulpn | grep 8025
```

### Email se ne šalje?
- Proveri da li je `MAIL_USE_SMTP=true` u `.env`
- Proveri da li je MailHog pokrenut
- Proveri logove: `storage/logs/error.log`
- Proveri da li je PHPMailer instaliran: `composer show phpmailer/phpmailer`

## 📚 Više Informacija

- [MailHog GitHub](https://github.com/mailhog/MailHog)
- [PHPMailer Dokumentacija](https://github.com/PHPMailer/PHPMailer)

