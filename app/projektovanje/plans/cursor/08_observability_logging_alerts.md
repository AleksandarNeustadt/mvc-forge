# Plan 08: Opservabilnost — centralni log, nivoi, spoljni alerti

**Cilj:** Jedinstvena politika logovanja kroz aplikaciju, rotacija fajlova, i opciono slanje kritičnih grešaka na spoljni servis ili e-mail. Nadovezuje se na plan 02 (verbose u jezgru).

---

## Faza A: Apstrakcija loggera

- [ ] Uvesti klasu `App\Core\Logging\AppLogger` (ili proširiti postojeći `Logger` u `app/core/logging/Logger.php` ako već odgovara) sa metodama: `error`, `warning`, `info`, `debug`.
- [ ] Implementacija piše u `storage/logs/app.log` ili zadržava postojeći `error.log` — **jedna** primarna datoteka po okruženju ili jasno razdvajanje (`app.log` vs `security.log`).
- [ ] Mapiranje `APP_LOG_LEVEL` na koji nivo ide u fajl (npr. `warning` znači da se `info` i `debug` odbacuju).

---

## Faza B: Integracija sa PHP `error_log`

- [ ] Odlučiti: da li ostaje `error_log()` za PHP native greške ili se registruje custom `set_error_handler` / `set_exception_handler` koji sve prosleđuje `AppLogger`.
- [ ] Izbegavati duplo logovanje iste greške (handler + default PHP log).

---

## Faza C: Struktura poruke

- [ ] Standardizovati jedan format linije: npr. ISO8601, nivo, request_id (opciono), poruka, JSON kontekst.
- [ ] Dodati opcioni `X-Request-Id` header (middleware) za korelaciju logova jednog zahteva.

---

## Faza D: Rotacija i disk

- [ ] Proveriti da li HestiaCP / logrotate već rotira `storage/logs/*`; ako ne, dodati `logrotate` konfiguraciju ili dokumentovati ručno čišćenje.
- [ ] Postaviti max veličinu ili broj fajlova da disk ne iscuri pri grešci u petlji.

---

## Faza E: Spoljni sink (opciono)

- [ ] Za Sentry (ili slično): dodati zavisnost u `composer.json` samo ako je prihvaćeno; inicijalizacija u bootstrap-u pod uslovom `SENTRY_DSN` u `.env`.
- [ ] Slati samo `error` / `fatal`; ne slati `debug` ni PII (lične podatke) bez filtriranja.
- [ ] Alternativa: `MAIL_ALERT_ON_ERROR` sa jednostavnim `mail()` ili PHPMailer za kritične izuzetke (throttle da ne flood-uje).

---

## Faza F: `.env` i primeri

- [ ] Ažurirati `.env.example` sa: `APP_LOG_LEVEL`, `SENTRY_DSN` (opciono), `MAIL_ALERT_ON_ERROR` (opciono).
- [ ] Dokumentovati default vrednosti za lokalni dev vs produkciju.

---

## Kriterijumi završetka

- [ ] Svi novi `debug` pozivi iz plana 02 koriste isti logger API.
- [ ] Produkcija: `APP_LOG_LEVEL=error` ne puni disk debug porukama.
- [ ] Jedna kritična greška (namerno podignuta u staging-u) stiže na izabrani sink ili ostaje jasno označena u centralnom logu.

---

## Veza sa drugim planovima

- Plan 02: smanjenje `error_log` u `Router` / `index.php`.
- Plan 05: ExceptionHandler šalje greške u logger umesto ad-hoc `error_log`.
