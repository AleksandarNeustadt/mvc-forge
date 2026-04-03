# 🌍 Geolocation & Language Redirect System

## Overview

Automatski sistem za detekciju zemlje korisnika na osnovu IP adrese i preusmjeravanje na odgovarajući jezik.

## Kako radi

### 1. **Automatska detekcija**
Kada korisnik poseti sajt **bez jezika u URL-u** (npr. `aleksandar.pro/` ili `aleksandar.pro/about`), sistem:
- Detektuje IP adresu korisnika
- Odredi zemlju pomoću besplatnog API servisa (ip-api.com)
- Mapira zemlju na odgovarajući jezik
- Preusmjerava korisnika na URL sa jezikom (npr. `aleksandar.pro/de/`)

### 2. **Sprechavanje pristupa bez jezika**
Sajt **VIŠE NE MOŽE** da se otvori bez jezika u URL-u. Svaki pristup bez jezika automatski se preusmjerava.

### 3. **Fallback na engleski**
Ako korisnik dolazi iz zemlje čiji jezik nije podržan (npr. Tajvan), automatski se preusmjerava na **engleski jezik**.

## Podržani jezici

Sistem podržava **30 jezika**:
```
sr, en, de, fr, es, it, pt, nl, pl, ru, uk, cs, hu, el, ro,
hr, bg, sk, sv, da, no, fi, lt, et, lv, sl, zh, ja, ko, tr
```

## Mapiranje zemalja na jezike

Primjeri mapiranja:
- 🇷🇸 Srbija → `sr`
- 🇩🇪 Njemačka, Austrija, Švajcarska → `de`
- 🇺🇸 USA, Kanada, Australija, UK → `en`
- 🇫🇷 Francuska, Monako → `fr`
- 🇪🇸 Španija, Latinska Amerika → `es`
- 🇷🇺 Rusija, Bjelorusija → `ru`
- 🇨🇳 Kina, Tajvan, Hong Kong → `zh`
- 🇯🇵 Japan → `ja`
- 🇰🇷 Južna Koreja → `ko`
- 🇹🇷 Turska → `tr`

**Kompletan popis:** Vidi `core/services/GeoLocation.php:26-131`

## Keširanje

Sistem koristi **session keširanje** za optimizaciju:
- Detekcija se vrši samo jednom po sesiji
- Keš traje **1 sat** (3600 sekundi)
- Nakon isteka keša, detekcija se ponovo izvršava

## Tehnička implementacija

### Komponente

1. **GeoLocation servis** (`core/services/GeoLocation.php`)
   - Detektuje IP adresu korisnika
   - Poziva API za geolokaciju
   - Mapira zemlju na jezik
   - Kešira rezultate

2. **Router modifikacija** (`core/classes/Router.php:28-82`)
   - Provjerava da li URL ima jezik
   - Ako ne, poziva GeoLocation servis
   - Vrši 302 redirect na URL sa jezikom
   - Čuva query parametre tokom redirect-a

### API koji se koristi

**ip-api.com** - Besplatni geolocation API
- 45 zahtjeva po minuti (dovoljno za većinu sajtova)
- Brz odgovor (~100-300ms)
- Pouzdana baza IP adresa

## Testiranje

### Web test
Otvorite u browseru:
```
http://aleksandar.pro/test-redirect.php
```

Ova stranica prikazuje:
- Vašu IP adresu
- Detektovanu zemlju i jezik
- Test linkove za redirect

### Command line test
```bash
php test-geo.php
```

## Primjeri

### Scenario 1: Korisnik iz Njemačke
```
Posjeta: aleksandar.pro/
Redirect: aleksandar.pro/de/
Razlog: IP adresa -> Njemačka -> jezik 'de'
```

### Scenario 2: Korisnik iz Tajvana
```
Posjeta: aleksandar.pro/about
Redirect: aleksandar.pro/en/about
Razlog: Tajvan nije podržan -> fallback na 'en'
```

### Scenario 3: Direktan pristup sa jezikom
```
Posjeta: aleksandar.pro/sr/contact
Redirect: NEMA (jezik već postoji u URL-u)
```

## Konfiguracija

### Promjena default jezika

U `core/services/GeoLocation.php:6`:
```php
private const DEFAULT_LANG = 'en'; // Promijeni na 'sr', 'de', itd.
```

### Dodavanje novih mapiranja

U `core/services/GeoLocation.php:26-131`, dodaj nove zemlje:
```php
'XX' => 'en', // Dodaj ISO kod zemlje i jezik
```

### Promjena trajanja keša

U `core/services/GeoLocation.php:7`:
```php
private const CACHE_DURATION = 3600; // Sekunde (3600 = 1 sat)
```

## Sigurnost i performanse

### ✅ Prednosti
- Automatsko preusmjeravanje poboljšava UX
- Keširanje smanjuje API pozive
- Fallback osigurava da sajt uvijek radi
- Podrška za proxy i load balancer

### ⚠️ Napomene
- API ima limit (45 req/min)
- Lokalne IP adrese se ne detektuju (localhost, private ranges)
- Koristite CDN ili proxy za bolju detekciju u produkciji

## Troubleshooting

### Problem: Sve IP adrese vraćaju US
**Razlog:** API vidi zahtjeve sa servera, ne od klijenta
**Rješenje:** Testirajte u browseru, ne preko CLI

### Problem: Redirect loop
**Razlog:** Sesija nije pokrenuta prije Router-a
**Rješenje:** Provjerite da `session_start()` postoji u `public/index.php:36`

### Problem: Detekcija ne radi
**Razlog:** API timeout ili rate limit
**Rješenje:** Provjeri error log: `storage/logs/error.log`

## Budući razvoj

Mogući unaprijedjenja:
- Memorija/Redis keš umjesto session-a
- MaxMind GeoIP2 baza (offline detekcija)
- A/B testiranje defaultnih jezika
- Analytics za detekciju korištenih jezika
