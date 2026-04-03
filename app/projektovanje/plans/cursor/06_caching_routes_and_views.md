# Plan 06: Keširanje dinamičkih ruta i view kompilata

**Cilj:** Smanjiti broj SQL upita pri učitavanju ruta iz `pages` tabele po PHP procesu / zahtevu i ubrzati cold start, uz pouzdanu invalidaciju kada admin promeni stranicu ili rutu.

---

## Faza A: Merenje pre promene

- [ ] U dev okruženju izmeriti (jednostavno: broj poziva `loadFromDatabase` po zahtevu, ili SQL log) koliko puta se lista stranica čita po jednom HTTP requestu.
- [ ] Zabeležiti prosečan broj redova u `pages` za produkciju (red veličine).

---

## Faza B: Dizajn keša za dinamičke rute

- [ ] Odabrati backend:
  - [ ] Fajl u `app/storage/cache/` (npr. `dynamic_routes.php` ili JSON), ili
  - [ ] APCu / OPcache-friendly PHP fajl koji vraća niz ruta (brže učitavanje od JSON decode na velikim listama).
- [ ] Definisati **ključ verzije** keša: npr. `max(updated_at)` iz `pages` ili inkrement „schema verzije“ u bazi pri izmeni.
- [ ] Pri `DynamicRouteRegistry::loadFromDatabase()`: prvo proveriti keš; ako je verzija ista, učitati iz keša i postaviti `self::$dynamicRoutes` bez SQL-a.
- [ ] Ako keš ne postoji ili je zastareo: učitati iz baze, upisati keš, nastaviti kao danas.

---

## Faza C: Invalidacija

- [ ] Na svim mestima gde se menja `pages.route`, `is_active` ili slično što utiče na rutiranje: pozvati `DynamicRouteRegistry::clearCache()` (već postoji za memoriju) i **obrisati fajl keša** ili ažurirati verziju.
- [ ] Proveriti admin kontrolere / servise koji kreiraju ili ažuriraju stranice — centralizovati „after save page“ hook.
- [ ] Za brisanje stranice: ista invalidacija.

---

## Faza D: View kompilat (već postoji — uskladiti)

- [ ] Pregledati `ViewEngine::$cacheEnabled`: da li se isključuje preko env u produkciji/dev (predlog: `VIEW_CACHE=true|false` u `.env`).
- [ ] Pri deploy-u: dokumentovati da li treba obrisati `storage/cache/views` kada se menja `ViewEngine::compile` logika (verzija engine-a u imenu keš foldera opciono).

---

## Faza E: Konkurentnost i bezbednost

- [ ] Pri upisu keš fajla koristiti atomarni upis (upis u temp pa `rename`) da paralelni request ne čita polupisan fajl.
- [ ] Proveriti dozvole na `storage/cache` (isti kao ostatak `storage`).

---

## Faza F: Fallback

- [ ] Ako čitanje keša padne (korumpiran fajl): uhvatiti grešku, obrisati keš, učitati iz baze i regenerisati.

---

## Kriterijumi završetka

- [ ] Tipičan GET zahtev ne izvršava pun `SELECT` liste svih aktivnih stranica na svakom koraku gde je keš validan.
- [ ] Posle izmene stranice u adminu, nova ruta je vidljiva bez ručnog brisanja keša na serveru (osim ako je keš eksplicitno isključen u dev-u).
- [ ] Performanse: merljivo smanjenje SQL broja ili vremena (čak i grubo mereno) na homepage-u ili teškim URL-u.

---

## Rizici

| Rizik | Mitigacija |
|-------|------------|
| Zastarele rute zbog propuštene invalidacije | Jedan centralni `PageService::save()` koji uvek invalidira |
| Stale file na više servera | Ako ikad bude horizontalni skal; preći na Redis/Memcached umesto fajla |
