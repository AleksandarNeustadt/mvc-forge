# 🌍 IP Geo Tracking System

## 📋 Pregled

Sistem za praćenje IP adresa sa automatskim prepoznavanjem zemlje i servisa, optimizovan da **ne usporava sajt**.

## ✨ Karakteristike

- ✅ **Keširanje geo podataka** - izbegava ponovljene API pozive
- ✅ **Brza detekcija servisa** - prepoznaje Google, Cloudflare, AWS, itd.
- ✅ **Asinhrona obrada** - ne blokira zahteve
- ✅ **Automatsko popunjavanje** - za stare IP adrese

## 🏗️ Arhitektura

### 1. **ip_geo_cache** tabela
Keš tabela koja čuva geo podatke za IP adrese:
- `country_code`, `country_name`
- `region`, `city`
- `isp`, `organization`
- `is_proxy`, `is_vpn`, `is_hosting`
- `known_service` (Google, Cloudflare, AWS, itd.)

### 2. **IpGeoCache** servis
Upravlja kešom i API pozivima:
- Proverava keš pre API poziva
- Čuva rezultate u bazi
- Detektuje poznate servise iz ISP/org podataka

### 3. **IpTracking** model
Ažuriran da koristi keš:
- Automatski popunjava `country_code` i `country_name`
- Detektuje `known_service` (Google, Cloudflare, itd.)
- Ne usporava sajt - koristi keš kada je moguće

## 🚀 Instalacija

### Korak 1: Kreirati tabele

```bash
# Kreirati ip_geo_cache tabelu
php core/database/migrations/019_create_ip_geo_cache_table.php

# Proveriti da li ip_tracking ima known_service kolonu
php core/database/migrations/019_add_known_service_to_ip_tracking.php
```

### Korak 2: Popuniti geo podatke za stare IP adrese (opciono)

```bash
# Popuniti geo podatke za IP adrese koje nemaju zemlju
php scripts/populate-ip-geo-data.php --limit=100 --delay=1
```

**Opcije:**
- `--limit=N` - Broj IP adresa za obradu (default: 100)
- `--delay=N` - Kašnjenje između API poziva u sekundama (default: 1)

**Napomena:** ip-api.com ima limit od 45 zahteva/minut, pa koristite `--delay=1.5` za sigurnost.

## 💻 Korišćenje

### Automatsko praćenje

Sistem automatski prati IP adrese kroz `IpTrackingMiddleware`. Svaki zahtev se loguje sa:
- IP adresom
- Zemljom (country_code, country_name)
- Poznatim servisom (ako je detektovan)

### Ručno dobijanje geo podataka

```php
require_once __DIR__ . '/core/services/IpGeoCache.php';

// Dobiti geo podatke za IP (koristi keš ako postoji)
$geoData = IpGeoCache::getGeoData('8.8.8.8');

// Rezultat:
// [
//     'country_code' => 'US',
//     'country_name' => 'United States',
//     'region' => 'California',
//     'city' => 'Mountain View',
//     'isp' => 'Google LLC',
//     'organization' => 'Google LLC',
//     'is_proxy' => false,
//     'is_vpn' => false,
//     'is_hosting' => false,
//     'known_service' => 'Google'
// ]
```

### Detekcija poznatih servisa

```php
require_once __DIR__ . '/core/services/KnownServiceDetector.php';

// Brza detekcija (bez API poziva)
$service = KnownServiceDetector::detect($ipAddress, $userAgent);

// Može vratiti: 'Google', 'Cloudflare', 'Amazon AWS', 'Microsoft Azure', itd.
```

## 🔧 Konfiguracija

### API Limit

ip-api.com (besplatni plan):
- **45 zahteva/minut**
- Nema API ključa potrebnog
- Podaci: zemlja, region, grad, ISP, organizacija, proxy, hosting

### Keš trajanje

Geo podaci se čuvaju u `ip_geo_cache` tabeli **30 dana**. Nakon toga se mogu osvežiti.

### Čišćenje starog keša

```php
require_once __DIR__ . '/core/services/IpGeoCache.php';

// Obrisati keš stariji od 90 dana
$deleted = IpGeoCache::clearOldCache(90);
```

## 📊 Performanse

### Optimizacije

1. **Keširanje** - Prvi API poziv za IP, ostali iz keša
2. **Kratak timeout** - 2 sekunde maksimalno za API poziv
3. **Brza detekcija servisa** - User-Agent i HTTP headers (bez API poziva)
4. **Batch processing** - Može se obraditi više IP adresa odjednom

### Metrike

- **Prvi zahtev za IP:** ~100-500ms (API poziv)
- **Sledeći zahtevi:** ~1-5ms (iz keša)
- **Detekcija servisa:** ~0.1ms (bez API poziva)

## 🎯 Prepoznati servisi

Sistem automatski prepoznaje:

- **Cloud provajderi:** Google Cloud, Amazon AWS, Microsoft Azure, DigitalOcean, Linode, Vultr, OVH, Hetzner
- **CDN servisi:** Cloudflare, Fastly, Akamai, MaxCDN
- **Botovi:** Google Bot, Bing Bot, Yahoo Bot, Facebook Bot, Twitter Bot, LinkedIn Bot, Apple Bot
- **Druge kompanije:** Facebook, Twitter, LinkedIn, Apple, Microsoft, GitHub

## 🔍 Upit podataka

### Najčešće IP adrese

```php
$stats = IpTracking::getIpStats(50);
// Vraća IP adrese sa brojem zahteva, zemljom, servisom
```

### Statistika po zemljama

```php
$countryStats = IpTracking::getCountryStats(10);
// Vraća top 10 zemalja po broju zahteva
```

### IP adrese sa poznatim servisom

```sql
SELECT * FROM ip_tracking 
WHERE known_service IS NOT NULL 
ORDER BY created_at DESC 
LIMIT 100;
```

## ⚠️ Napomene

1. **Rate limiting:** Poštujte limit od 45 zahteva/minut za ip-api.com
2. **Privatne IP adrese:** Lokalne IP adrese (127.x, 192.168.x, itd.) se preskaču
3. **VPN/Proxy detekcija:** ip-api.com besplatni plan ne detektuje VPN, samo proxy i hosting

## 🔄 Alternativna rešenja

### MaxMind GeoIP2 (preporučeno za produkciju)

Za veće sajtove, razmotrite korišćenje **MaxMind GeoIP2 Lite** baze:
- Besplatna verzija dostupna
- Lokalna baza podataka (nema API poziva)
- Brže i pouzdanije
- Potrebna integracija: `geoip2/geoip2` Composer paket

### IP2Location

Kompletan servis sa lokalnom bazom:
- Detaljniji podaci
- VPN/Proxy detekcija
- Plaćeni planovi za više funkcionalnosti

## 📝 Migracije

```bash
# Kreirati ip_geo_cache tabelu
php core/database/migrations/019_create_ip_geo_cache_table.php

# Dodati known_service kolonu (ako ne postoji)
php core/database/migrations/019_add_known_service_to_ip_tracking.php
```

## 🐛 Troubleshooting

### Geo podaci se ne popunjavaju

1. Proverite da li `ip_geo_cache` tabela postoji
2. Proverite da li API pozivi rade: `curl http://ip-api.com/json/8.8.8.8`
3. Proverite error log: `tail -f storage/logs/error.log`

### Sporiji sajt

1. Proverite da li se koristi keš (proverite `ip_geo_cache` tabelu)
2. Smanjite timeout u `IpGeoCache::API_TIMEOUT`
3. Razmotrite background processing za API pozive

## 📚 Dodatni resursi

- [ip-api.com dokumentacija](http://ip-api.com/docs)
- [MaxMind GeoIP2](https://www.maxmind.com/en/geoip2-databases)
- [IP2Location](https://www.ip2location.com/)

