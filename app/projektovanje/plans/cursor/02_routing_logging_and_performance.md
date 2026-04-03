# Plan 02: Ruting, logovanje zahteva i performanse

**Cilj:** Smanjiti šum i I/O u produkciji zbog `error_log()` u `Router`, `DynamicRouteRegistry` i ulaznoj tački, zadržati duboku dijagnostiku kada je eksplicitno uključena. Pripremiti teren za keš dinamičkih ruta (detalji u planu 06).

**Obuhvat fajlova (trenutno stanje):** `public_html/index.php`, `app/core/routing/Router.php`, `app/core/routing/DynamicRouteRegistry.php` (i srodni pozivi u dispatch putu).

---

## Faza A: Centralizovana politika logovanja

- [ ] Uvesti jednu pomoćnu funkciju ili statičku metodu, npr. `App\Core\Log::debug(string $message, array $context = [])`, koja **ništa ne loguje** ako je nivo ispod praga (veza sa planom 08: `APP_LOG_LEVEL`).
- [ ] Definisati nivoe: npr. `error`, `warning`, `info`, `debug` gde trenutni verbose `error_log` u ruteru spada u `debug`.
- [ ] Zameniti direktne `error_log("Router::...")` pozive u `Router` pozivima na novi helper sa nivoom `debug`.
- [ ] Isto za `DynamicRouteRegistry::findRoute` i `loadFromDatabase` (poruke tipa „Available routes“, „Registered route“).
- [ ] U `index.php` blok koji loguje svaki zahtev (`=== NEW REQUEST ===`, `REQUEST_URI`, itd.) omotati istom politikom — u produkciji podrazumevano **isključeno** osim `error`.

---

## Faza B: Ponašanje po okruženju

- [ ] U `.env.example` dokumentovati `APP_LOG_LEVEL=debug|info|warning|error` (ili ekvivalent).
- [ ] Mapiranje: `APP_DEBUG=true` može automatski podići log na `debug` ako `APP_LOG_LEVEL` nije eksplicitno postavljen (eksplicitno dokumentovati ovo pravilo u kodu).
- [ ] Proveriti da u produkciji (`APP_DEBUG=false`, `APP_LOG_LEVEL=error`) nema curenja telom zahteva ili tokena u log (pregledati poruke koje se i dalje šalju na `info`).

---

## Faza C: Performanse bez keša (brze pobede)

- [ ] U `DynamicRouteRegistry::loadFromDatabase`: ukloniti ili usloviti `error_log` unutar `foreach` po svakoj registrovanoj ruti (najskuplje pri velikom broju stranica).
- [ ] U `Router::__construct` / `extractLanguage`: spojiti više `error_log` linija u jedan `debug` poziv sa JSON kontekstom ako je potrebno — ili ukloniti duplikate (isti podatak više puta).
- [ ] Promeriti broj `error_log` poziva po jednom HTTP zahtevu pre i posle (opciono: jednostavan brojač u dev modu).

---

## Faza D: Ispravnost i bezbednost logova

- [ ] Uveriti se da se u log ne upisuju: lozinke, CSRF tokeni, puni session ID, API tajni ključevi.
- [ ] Za Geo/IP redirect: logovati samo odluku (izabrani jezik), ne kompletan geo objekat ako sadrži lične podatke.

---

## Faza E: Dokumentacija za operatere

- [ ] Kratak odeljak u internom README (samo ako već postoji ops dokumentacija — inače jedna rečenica u `.env.example`) šta znači svaki `APP_LOG_LEVEL`.

---

## Kriterijumi završetka

- [ ] Jedan zahtev ka javnoj stranici u produkcijskom režimu generiše **nula** `debug` linija u `error.log` (ili ih agregira u jednu po želji).
- [ ] Sa `APP_LOG_LEVEL=debug` developer i dalje vidi detalje rutiranja kao danas.
- [ ] Nema funkcionalne regresije: jezik, API putanje, method spoofing rade kao pre.

---

## Veza sa drugim planovima

- Plan 06: trajno keširanje liste dinamičkih ruta (smanjuje DB upite, ne log sam po sebi).
- Plan 08: centralni logger i rotacija / spoljni sink.
