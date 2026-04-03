# Plan 03: Bezbednosni sloj — revizija, API i higijena logova

**Cilj:** Zatvoriti identifikovane rupice: end-to-end pregled API ruta (CORS, autentikacija, CSRF izuzeci), smanjiti rizik osetljivih podataka u logovima iz middleware-a, potvrditi konzistentnost bezbednosnih zaglavlja.

---

## Faza A: Inventar API površine

- [ ] Pročitati `app/routes/api.php` i `app/routes/dashboard-api.php` (i sve uključene grupe).
- [ ] Napraviti tabelu: metoda, putanja, middleware, tip autentikacije (session / token / javno).
- [ ] Označiti rute koje menjaju stanje (POST/PUT/PATCH/DELETE) i koje zaštitu dobijaju od `CsrfMiddleware` globalno vs izuzetak.

---

## Faza B: CSRF i API

- [ ] Proveriti da li `CsrfMiddleware` ispravno **isključuje** JSON API koji koristi Bearer token (ne formu) — ako ne, dodati precizne `except` uzorke ili grupisati API bez CSRF-a uz dokumentovani razlog.
- [ ] Proveriti da li bilo koji API endpoint prihvata `POST` iz browsera sa cookies bez dodatne zaštite (CSRF ili strict CORS + SameSite) — dokumentovati prihvatljiv rizik ili zatvoriti.
- [ ] Za `dashboard-api` rute: potvrditi da ne postoji XSS preko reflektovanog JSON-a (kratko: Content-Type, escape u klijentu — van PHP-a ali zabeležiti).

---

## Faza C: CORS

- [ ] Pregledati `CorsMiddleware`: dozvoljeni origin-i, metode, zaglavlja.
- [ ] U produkciji zabraniti `*` ako se koriste credentials.
- [ ] Proveriti preflight cache zaglavlja (`Access-Control-Max-Age`) ako je relevantno.

---

## Faza D: Rate limiting

- [ ] Za svaki javni API endpoint koji može biti zloupotrebljen (login, reset, slanje formi preko API): proveriti da li postoji `RateLimitMiddleware` ili ekvivalent.
- [ ] Proveriti da li je ključ limita dovoljno specifičan (IP + ruta već postoji u `RateLimiter::getIdentifier` — potvrditi za API da nije deljen između korisnika iste NAT mreže na način koji je neprihvatljiv).

---

## Faza E: CSP i inline sadržaj

- [ ] Pregledati `SecurityHeadersMiddleware::buildCSP()`: da li svi inline skriptovi u view-ovima koriste `nonce` ili su uklonjeni.
- [ ] Proveriti da li admin/editor (Summernote/TinyMCE) zahteva izuzetke (`unsafe-inline` ili dodatni domen) — dokumentovati kompromis.
- [ ] Proveriti eksterne CDN resurse (ako postoje) da su na beloj listi u CSP.

---

## Faza F: Logovanje u bezbednosnim middleware-ima

- [ ] U `CsrfMiddleware` (i sličnim): zameniti ili ukloniti `error_log` koji ispisuje URI i rezultat verifikacije na svakom zahtevu; prebaciti na `debug` nivo iz plana 02/08.
- [ ] Ne logovati vrednost tokena ni fragmente tela zahteva.

---

## Faza G: Sesija i fiksacija

- [ ] Kratko proveriti da li se `session_regenerate_id` poziva posle uspešnog login-a (ako ne — dodati stavku u checklist kao bugfix).
- [ ] Proveriti timeout i invalidaciju sesije na logout.

---

## Kriterijumi završetka

- [ ] Dokumentovana lista API ruta sa nivoom zaštite.
- [ ] Nema nekontrolisanog CSRF na browser-driven POST formama; API token rute imaju jasan model.
- [ ] Produkcijski log ne sadrži verbose CSRF dijagnostiku na `error` nivou.

---

## Napomena

Ovaj plan je **revizija i hardening**, ne zamena celog bezbednosnog steka. Za pentest i compliance potrebna je spoljašnja provera.
