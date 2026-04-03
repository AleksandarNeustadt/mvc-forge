# 📋 Plan 03: Razvoj "Aleksandar CLI" Alata
**Cilj:** Automatizacija dosadnih zadataka (pravljenje fajlova, migracije) putem komandne linije.

---

## 🛠️ Detaljni Koraci

### Faza 1: Ulazna tačka (`bin/console`)
- [ ] Kreirati fajl `app/scripts/console` (ili `bin/console`).
- [ ] Dodati `#!/usr/bin/php` na početak fajla.
- [ ] Implementirati osnovnu petlju koja prihvata argumente (`$argv`).
- [ ] Inicijalizovati Core aplikacije (učitati `.env` i Composer) unutar skripte kako bi CLI imao pristup bazi.

### Faza 2: Implementacija Scaffolding komandi
- [ ] `make:controller`: Kreira `.php` fajl u `mvc/controllers/` sa boilerplate kodom.
- [ ] `make:model`: Kreira model sa osnovnim CRUD metodama.
- [ ] `make:migration`: Kreira novi migracioni fajl sa timestamp-om u imenu.

### Faza 3: Database & Route komande
- [ ] `migrate`: Pokreće sve neizvršene migracije koristeći `DatabaseTableBuilder`.
- [ ] `route:list`: Ispisuje sve registrovane rute (statičke i dinamičke) u tabelarnom pregledu u terminalu.
- [ ] `cache:clear`: Briše fajlove iz `storage/cache/views/`.

---

## 🔍 Detalji na koje treba obratiti pažnju
- **Templating:** CLI alat treba da koristi "stub" fajlove (šablone) koje će samo popunjavati imenom klase.
- **Dozvole:** Osigurati da fajl `console` ima `+x` (executable) dozvolu.
- **Interaktivnost:** Dodati potvrdu pre brisanja baze ili pokretanja destruktivnih komandi.

## 🏁 Rezultat
Drastično ubrzanje razvoja. Umesto 5 minuta za ručno pravljenje i linkovanje kontrolera, biće vam potrebno 5 sekundi: `php console make:controller PortfolioController`.
