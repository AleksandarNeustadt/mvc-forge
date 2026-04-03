# 📋 Plan 04: Profesionalizacija Handling-a Grešaka
**Cilj:** Siguran sistem koji ne otkriva osetljive podatke korisnicima i detaljno beleži probleme za programere.

---

## 🛠️ Detaljni Koraci

### Faza 1: Unapređenje `ExceptionHandler`-a
- [ ] Detektovati `APP_ENV` iz `.env` fajla.
- [ ] Ako je `APP_ENV=production`, onemogućiti ispis `SQLSTATE` i PHP grešaka na ekranu.
- [ ] Implementirati "Whoops"-like prikaz za `APP_ENV=local` (lepši prikaz stack trace-a).
- [ ] Osigurati da se `Fatal Error` i `Parse Error` (koji se dešavaju pre `try-catch` bloka) hvataju preko `register_shutdown_function`.

### Faza 2: Centralizovani Logger
- [ ] Kreirati `core/classes/Logger.php` koji podržava nivoe: `DEBUG`, `INFO`, `WARNING`, `ERROR`, `CRITICAL`.
- [ ] Implementirati automatsku rotaciju logova (npr. svaki dan novi fajl `error-2026-04-01.log`) da se izbegnu ogromni fajlovi od 1GB.
- [ ] Dodati kontekst u logove (npr. trenutni URL, ID korisnika koji je bio logovan, IP adresa).

### Faza 3: Alert sistem (Opciono)
- [ ] Dodati funkciju koja šalje email adminu čim se desi `CRITICAL` greška (npr. baza ne može da se poveže).
- [ ] Implementirati limit (throtling) za emailove, da ne dobijete 1000 mailova u minuti ako sajt padne.

---

## 🔍 Detalji na koje treba obratiti pažnju
- **Bezbednost:** Nikada ne upisivati lozinke iz `.env` fajla u log fajlove (maskirati `DB_PASSWORD` ako se desi greška pri konekciji).
- **Format:** Koristiti JSON format za logove ako planirate kasnije da koristite alate poput ELK stack-a ili Graylog-a.

## 🏁 Rezultat
Sistem u koji možete imati poverenja. Čak i ako nešto pukne, znaćete tačno šta, gde i kome se desilo, dok će običan posetilac videti samo lepu "500 Error" stranicu.
