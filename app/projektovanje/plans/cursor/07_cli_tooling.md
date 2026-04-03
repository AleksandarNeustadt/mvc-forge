# Plan 07: CLI alat (`bin/console`)

**Cilj:** Uvesti mali komandni interfejs iz `app/` za ponavljajuće dev/ops zadatke: čišćenje keša, generisanje skeleta, pokretanje buildera šeme, bez ručnog kopiranja fajlova.

---

## Faza A: Osnove pokretanja

- [ ] Kreirati `app/bin/console` (executable PHP skripta sa `#!/usr/bin/env php` ako je unix).
- [ ] U skripti: `require __DIR__ . '/../vendor/autoload.php';` i minimalan bootstrap (Env, Database ako treba) — **nakon** plana 01 autoload mora biti stabilan.
- [ ] Dokumentovati pokretanje: `php app/bin/console` ili `./app/bin/console` iz root-a projekta.

---

## Faza B: Arhitektura komandi

- [ ] Odabrati stil: jednostavna `match`/`switch` na `$argv[1]` ili mala `CommandInterface` sa `run(array $args): int`.
- [ ] Konvencija izlaznog koda: `0` uspeh, `1` greška (za CI skripte).

---

## Faza C: Početni skup komandi (MVP)

- [ ] `cache:clear` — briše `storage/cache/views`, fajl keša dinamičkih ruta (kada plan 06 uvede fajl), opciono `DynamicRouteRegistry::clearCache()` logika iz CLI konteksta.
- [ ] `route:dump` — ispisuje spisak statičkih ruta iz `RouteCollection` (zahteva učitavanje `routes/*.php` bez HTTP-a; može biti faza 2 ako je teško).
- [ ] `make:controller {Name}` — generiše `NameController.php` u `mvc/controllers` sa osnovnim extend-om i jednom praznom akcijom (usklađeno sa namespace-om iz plana 01).
- [ ] `make:model {Name}` — generiše model skelet u `mvc/models`.

---

## Faza D: Šema baze (opciono)

- [ ] Ako postoji ulazna tačka za `DatabaseTableBuilder` / migracije: komanda `schema:migrate` ili `db:build` koja pokreće poznate skripte (samo ako već postoji jedan kanalski način u projektu).

---

## Faza E: Bezbednost

- [ ] CLI ne sme biti izložen preko veba (proveriti da `bin/` nije pod `public_html`).
- [ ] Ne logovati DB lozinku u output komandi.

---

## Faza F: Dokumentacija

- [ ] U postojećem dev README ili u help izlazu `php bin/console list` kratko opisati svaku komandu.

---

## Kriterijumi završetka

- [ ] Bar tri komande rade lokalno: `cache:clear`, `make:controller`, `make:model`.
- [ ] Generisani fajlovi se uklapaju u stil postojećih kontrolera/modela (namespace, extend).
- [ ] Nema fatal error kada se pokrene bez argumenata — prikaže se help.

---

## Zavisnosti

- Plan 01: PSR-4 olakšava autoload u CLI bez kopiranja `index.php` lanca.
- Plan 06: `cache:clear` briše i route keš fajl kada postoji.
