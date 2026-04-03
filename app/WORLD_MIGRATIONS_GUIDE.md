# World Migrations Guide

## Pokretanje migracija za Continents i Regions

Da biste kreirali tabele i popunili početne podatke za Continents i Regions, pokrenite sledeću komandu:

```bash
php run-world-migrations.php
```

Ova skripta će automatski pokrenuti sledeće migracije redom:

1. **033_create_continents_table.php** - Kreira `continents` tabelu
2. **034_create_regions_table.php** - Kreira `regions` tabelu  
3. **035_add_continent_region_to_languages.php** - Dodaje `continent_id` i `region_id` kolone u `languages` tabelu
4. **036_seed_continents_regions.php** - Popunjava početne podatke (7 kontinenata i 25+ regija)

## Alternativno: Pokretanje pojedinačnih migracija

Ako želite da pokrenete migracije pojedinačno:

```bash
php core/database/migrations/033_create_continents_table.php
php core/database/migrations/034_create_regions_table.php
php core/database/migrations/035_add_continent_region_to_languages.php
php core/database/migrations/036_seed_continents_regions.php
```

## Popunjeni podaci

### Kontinenti (7):
- Europe (eu)
- Asia (as)
- North America (na)
- South America (sa)
- Africa (af)
- Oceania (oc)
- Antarctica (an)

### Regije (25+):
- **Europe**: Western Europe, Eastern Europe, Northern Europe, Southern Europe, Central Europe, Balkans
- **Asia**: East Asia, South Asia, Southeast Asia, Central Asia, West Asia, Middle East
- **North America**: North America, Central America, Caribbean
- **South America**: South America
- **Africa**: North Africa, West Africa, East Africa, Central Africa, Southern Africa
- **Oceania**: Australasia, Polynesia, Melanesia, Micronesia

## Pristup u Dashboard-u

Nakon pokretanja migracija, možete pristupiti:

- **Languages**: `/sr/dashboard/languages` (ili drugi jezik)
- **Continents**: `/sr/dashboard/continents`
- **Regions**: `/sr/dashboard/regions`

Sve opcije su dostupne kroz **World** meni u sidebar navigaciji.
