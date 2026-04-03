# Dinamičko Dodavanje Jezika - Dokumentacija

## Pregled

Sistem sada podržava **dinamičko dodavanje jezika** bez potrebe za izmenom koda. Umesto hardkodovanih mapa flag kodova, koristi se nova globalna funkcija `get_flag_code()` koja:

1. **Prvo proverava bazu podataka** - ako jezik ima `flag` polje popunjeno, koristi ga
2. **Koristi fallback mapu** - ako flag nije u bazi, koristi poznate mape (npr. 'sr' => 'rs')
3. **Koristi jezički kod** - kao poslednji izbor, koristi sam jezički kod (npr. 'mk' => 'mk')

## Kako Dodati Novi Jezik

### 1. Dodajte jezik kroz Dashboard

Idite na `/dashboard/languages/create` i popunite formu:
- **Language Code**: ISO kod (npr. `mk`, `ar`, `hi`)
- **Language Name**: Ime na engleskom (npr. `Macedonian`, `Arabic`, `Hindi`)
- **Native Name**: Ime na maternjem jeziku (npr. `Македонски`, `العربية`, `हिन्दी`)
- **Flag Emoji**: Unesite **flag kod** (npr. `mk`, `sa`, `in`) - **NE emoji, već kod!**

### 2. Flag Kod Format

U polju "Flag Emoji" unesite **2-slova kod države** (lowercase):
- `mk` za Makedoniju
- `sa` za Saudijsku Arabiju  
- `in` za Indiju
- `eg` za Egipat
- itd.

**VAŽNO**: Unesite samo kod (npr. `mk`), ne emoji (npr. 🇲🇰).

### 3. Automatsko Prikazivanje

Nakon što sačuvate jezik sa flag kodom, zastava će se automatski prikazivati:
- U tabeli jezika (`/dashboard/languages`)
- U header navigaciji
- U svim dashboard tabelama gde se prikazuju jezici
- U formama za izbor jezika

## Tehnički Detalji

### Nova Funkcija: `get_flag_code()`

```php
/**
 * Get flag code for a language code (dynamic from database or fallback map)
 * 
 * @param string $langCode Language code (e.g., 'mk', 'sr', 'en')
 * @param array|null $languageData Optional language data array with 'flag' key
 * @return string Flag code for flag-icons library (e.g., 'mk', 'rs', 'gb')
 */
function get_flag_code(string $langCode, ?array $languageData = null): string
```

### Primer Korišćenja

```php
// U view fajlu
<?php
$langCode = strtolower($language['code'] ?? '');
$flagCode = get_flag_code($langCode, $language); // Koristi flag iz $language niza
?>
<span class="fi fi-<?= htmlspecialchars($flagCode) ?>"></span>
```

### Fallback Logika

1. **Proverava `$languageData['flag']`** - ako je prosleđen niz sa flag poljem
2. **Proverava bazu podataka** - poziva `Language::findByCode()` i proverava `flag` polje
3. **Koristi fallback mapu** - za poznate jezike (sr=>rs, en=>gb, itd.)
4. **Koristi jezički kod** - kao poslednji izbor (mk=>mk, pl=>pl, itd.)

## Migracija Postojećeg Koda

### Stari Način (Hardkodovana Mapa)
```php
$flagCodes = [
    'sr' => 'rs', 'hr' => 'hr', 'mk' => 'mk',
    // ... mora se dodavati ručno
];
$flagCode = $flagCodes[$langCode] ?? 'xx';
```

### Novi Način (Dinamički)
```php
// Automatski proverava bazu i fallback mapu
$flagCode = get_flag_code($langCode, $language);
```

## Prednosti

✅ **Nema potrebe za izmenom koda** - samo dodajte jezik kroz dashboard  
✅ **Automatsko mapiranje** - sistem sam pronalazi odgovarajući flag kod  
✅ **Fallback mehanizam** - uvek će prikazati nešto, čak i ako flag nije postavljen  
✅ **Cache** - flag kodovi se keširaju za bolje performanse  
✅ **Backward compatible** - stari kod i dalje radi, samo treba zameniti funkciju  

## Napomene

- Flag kod mora biti **2 slova** (ISO 3166-1 alpha-2 country code)
- Ako flag kod nije postavljen u bazi, sistem će pokušati da koristi fallback mapu
- Za nove jezike koji nisu u fallback mapi, koristi se sam jezički kod (što često radi)
- Funkcija koristi statički cache za bolje performanse
