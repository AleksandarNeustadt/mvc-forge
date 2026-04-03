# Upload Directory Permissions Fix

## Problem
Upload ne radi jer direktorijum nema prava za pisanje. PHP proces radi kao `www-data` korisnik, ali direktorijum je vlasništvo drugog korisnika.

## Rešenje

Pokrenite sledeće komande u WSL Ubuntu terminalu:

```bash
cd /var/www/aleksandar.pro

# Kreirajte direktorijume ako ne postoje
mkdir -p storage/uploads/blog

# Postavite ownership na www-data (web server korisnik)
sudo chown -R www-data:www-data storage/uploads

# Postavite prava (775 omogućava pisanje za vlasnika i grupu)
sudo chmod -R 775 storage/uploads

# Proverite da li je sve u redu
ls -la storage/uploads/
ls -la storage/uploads/blog/
```

## Alternativno rešenje (ako nemate sudo pristup)

Ako nemate sudo pristup, možete koristiti 777 prava (manje sigurno, ali radi):

```bash
chmod -R 777 storage/uploads
```

## Provera

Nakon postavljanja prava, proverite:

```bash
# Proverite ownership
ls -la storage/uploads/ | grep blog

# Proverite da li www-data može da piše
sudo -u www-data touch storage/uploads/blog/test.txt && sudo -u www-data rm storage/uploads/blog/test.txt && echo "OK - www-data can write"
```

## Napomena

Ako koristite drugi web server (nginx, Apache), možda je korisnik drugačiji:
- Apache: `www-data` ili `apache`
- Nginx: `www-data` ili `nginx`
- PHP-FPM: proverite u `/etc/php-fpm.d/www.conf`

