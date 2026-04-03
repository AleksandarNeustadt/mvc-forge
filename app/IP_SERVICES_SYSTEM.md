# 🔧 IP Services System - Centralizovano Mapiranje Servisa

## 📋 Problem

Prethodno, servis se detektovao samo pri logovanju novog zahteva, što je dovodilo do:
- **Nekonzistentnosti** - iste IP adrese imale različite servise u različitim zapisima
- **Nedostajući servisi** - stari zapisi bez servisa
- **Dupliranje logike** - servis se detektovao svaki put umesto da se koristi keš

## ✨ Rešenje

Kreiran je **centralizovani sistem** sa `ip_services` tabelom koja:
- ✅ **Čuva jedinstveno mapiranje** - jedna IP adresa = jedan servis
- ✅ **Osigurava konzistentnost** - svi zapisi za istu IP imaju isti servis
- ✅ **Koristi JOIN** - automatski povezuje servise sa zapisima u `ip_tracking`
- ✅ **Ažurira postojeće zapise** - skripta popunjava servise za stare IP adrese

## 🏗️ Arhitektura

### 1. **ip_services** tabela
Centralizovana tabela koja čuva IP -> servis mapiranje:
- `ip_address` (unique) - IP adresa
- `known_service` - Ime servisa (Google, Cloudflare, AWS, itd.)
- `isp`, `organization` - Dodatne informacije
- `is_proxy`, `is_vpn`, `is_hosting` - Flagovi
- `detection_method` - Kako je servis detektovan
- `detection_count` - Koliko puta je detektovan
- `first_detected_at`, `last_detected_at` - Vremenski podaci

### 2. **IpServiceMapper** servis
Upravlja servisima:
- `getService()` - Vraća servis za IP (iz keša ili detektuje)
- `storeService()` - Čuva servis u `ip_services` tabelu
- `updateTrackingRecords()` - Ažurira zapise u `ip_tracking`
- `batchUpdateAllServices()` - Batch ažuriranje svih IP adresa

### 3. **Ažuriran IpTracking model**
- Koristi `IpServiceMapper` umesto direktne detekcije
- `getRecent()` i `getIpStats()` koriste JOIN sa `ip_services` tabelom
- Osigurava konzistentnost - uvek koristi servis iz `ip_services` tabele

## 🚀 Instalacija

### Korak 1: Kreirati ip_services tabelu

```bash
php core/database/migrations/020_create_ip_services_table.php
```

### Korak 2: Ažurirati postojeće IP adrese sa servisima

```bash
# Detektovati i popuniti servise za sve IP adrese
php scripts/update-ip-services.php --limit=100
```

**Opcije:**
- `--limit=N` - Broj IP adresa za obradu (default: 100)
- `--delay=N` - Kašnjenje između obrade (default: 0.5)

## 💻 Kako Radi

### Automatsko praćenje

1. **Novi zahtev dolazi** → `IpTracking::logRequest()` se poziva
2. **IpServiceMapper::getService()** se poziva:
   - Proverava `ip_services` tabelu (keš)
   - Ako ne postoji, detektuje servis (User-Agent, Headers, Geo API)
   - Čuva u `ip_services` tabelu
3. **Servis se upisuje** u `ip_tracking` zapis

### Prikaz podataka

- **getRecent()** - Koristi JOIN sa `ip_services` za konzistentne podatke
- **getIpStats()** - Koristi JOIN za grupisanje po IP adresama
- **Dashboard** - Automatski prikazuje servise iz `ip_services` tabele

## 🔍 SQL Upiti

### Pronaći sve IP adrese sa servisom

```sql
SELECT ip_address, known_service, detection_count, last_detected_at
FROM ip_services
WHERE known_service IS NOT NULL
ORDER BY last_detected_at DESC;
```

### Pronaći IP adrese bez servisa u ip_tracking

```sql
SELECT DISTINCT t.ip_address
FROM ip_tracking t
LEFT JOIN ip_services s ON t.ip_address = s.ip_address
WHERE s.known_service IS NULL
AND t.ip_address NOT LIKE '127.%'
AND t.ip_address NOT LIKE '192.168.%';
```

### Ažurirati sve zapise za IP adresu sa servisom

```sql
UPDATE ip_tracking t
INNER JOIN ip_services s ON t.ip_address = s.ip_address
SET t.known_service = s.known_service
WHERE t.known_service IS NULL OR t.known_service = '';
```

## 📊 Prednosti

### Pre (stari sistem)
- ❌ Servis se detektovao svaki put
- ❌ Nekonzistentnost - različiti servisi za istu IP
- ❌ Stari zapisi bez servisa
- ❌ Sporije (ponovljena detekcija)

### Posle (novi sistem)
- ✅ Centralizovano mapiranje
- ✅ Konzistentnost - isti servis za istu IP
- ✅ Brže - koristi keš
- ✅ Automatsko ažuriranje postojećih zapisa
- ✅ JOIN za konzistentne podatke

## 🔄 Ažuriranje Postojećih Podataka

### Automatsko ažuriranje

Skripta `update-ip-services.php` automatski:
1. Pronalazi IP adrese bez servisa
2. Detektuje servis (ako postoji)
3. Čuva u `ip_services` tabelu
4. Ažurira sve zapise u `ip_tracking`

### Ručno ažuriranje

```php
require_once __DIR__ . '/core/services/IpServiceMapper.php';

// Ažurirati jednu IP adresu
$updated = IpServiceMapper::updateTrackingRecords('8.8.8.8');

// Batch ažuriranje
$stats = IpServiceMapper::batchUpdateAllServices(100);
```

## 🎯 Detekcija Servisa

Sistem koristi **više metoda** za detekciju (redosled):

1. **KnownServiceDetector** (brzo)
   - User-Agent analiza
   - HTTP headers (Cloudflare, Fastly, itd.)
   - IP range provere

2. **IpGeoCache** (API poziv)
   - ISP/Organization podaci
   - Geo API (ip-api.com)

3. **Reverse DNS** (sporo, retko korišćen)
   - gethostbyaddr() lookup

## ⚠️ Napomene

1. **Prva detekcija** - Može biti sporija (API poziv)
2. **Sledeće detekcije** - Brze (iz `ip_services` keša)
3. **Ažuriranje** - Stari zapisi se automatski ažuriraju kroz JOIN
4. **Konzistentnost** - JOIN osigurava da svi zapisi imaju isti servis

## 🐛 Troubleshooting

### Servisi se ne prikazuju

1. Proverite da li `ip_services` tabela postoji
2. Pokrenite `update-ip-services.php` skriptu
3. Proverite da li IP adresa ima servis u `ip_services` tabeli

### Nekonzistentni servisi

1. JOIN bi trebalo da reši ovo automatski
2. Ako i dalje imate problem, pokrenite:
   ```sql
   UPDATE ip_tracking t
   INNER JOIN ip_services s ON t.ip_address = s.ip_address
   SET t.known_service = s.known_service;
   ```

## 📚 Dodatni Resursi

- `IP_GEO_TRACKING.md` - Geo tracking sistem
- `core/services/IpServiceMapper.php` - Servis za upravljanje servisima
- `scripts/update-ip-services.php` - Skripta za ažuriranje

