# Plan 05: View engine — dijagnostika grešaka i developer iskustvo

**Cilj:** Kada šablon ili kompilacija padne, developer dobija jasnu poruku (putanja fajla, ime šablona), a posetioc u produkciji vidi kontrolisanu stranicu bez belog ekrana. Uskladiti sa `ExceptionHandler` i `APP_DEBUG`.

---

## Faza A: Mapiranje grešaka na izvor

- [ ] Pregledati `app/core/exceptions/ExceptionHandler.php`: kako tretira `Throwable`, da li razlikuje `ViewEngine` / template greške.
- [ ] U `ViewEngine::render` i `compile`: obmotati kritične delove tako da se u izuzetak doda **kontekst** (npr. „template: `pages/about`“, pun putanja do izvornog fajla).
- [ ] Gde se koristi `eval` ili `include` kompilovanog koda: u `catch` blokovima prepakovati PHP parse error poruke u čitljiviju poruku sa imenom šablona.

---

## Faza B: Režimi prikaza

- [ ] Kada je `APP_DEBUG=true`: prikazati stack trace ili bar putanju šablona + liniju (bez curenja `.env` vrednosti u HTML).
- [ ] Kada je `APP_DEBUG=false`: generička 500 stranica; puni detalj samo u log (`error` nivo).
- [ ] Proveriti da li postoji dvostruko `display_errors` podešavanje u `index.php` koje može da konfliktuje sa handlerom — uskladiti jednu politiku.

---

## Faza C: Keš kompilata i greške

- [ ] U `ViewEngine`: ako kompilacija padne, **ne ostavljati** polupisan keš fajl u `storage/cache/views` (brisati ili ne upisivati do uspeha).
- [ ] Dodati opciju „flush view cache“ (CLI plan 07 ili admin akcija) dokumentovanu u jednoj rečenici.

---

## Faza D: Direktive i poruke

- [ ] Za nepoznatu direktivu ili nezatvoren `@if` / `@section`: gde je moguće, baciti `InvalidArgumentException` sa jasnim tekstom umesto tihog pogrešnog HTML-a.
- [ ] Dokumentovati u zaglavlju `ViewEngine.php` koja je minimalna verzija podržanih direktiva (već delom postoji — dopuniti ako treba).

---

## Faza E: CSP i šablon

- [ ] Proveriti da li greška u produkciji i dalje šalje CSP zaglavlja (middleware redosled) — izbeći slučaj gde se HTML greške renderuje bez zaglavlja.

---

## Kriterijumi završetka

- [ ] Namerna sintaksna greška u jednom `.template.php` fajlu u dev modu pokazuje identifikovani šablon u poruci ili logu.
- [ ] U produkciji korisnik ne vidi PHP warning u telu odgovora.
- [ ] Nema korumpiranih keš fajlova posle neuspele kompilacije.

---

## Veza sa planovima

- Plan 02: verbose log samo na `debug` nivou za render put.
- Plan 06: keš ponašanje i invalidacija.
