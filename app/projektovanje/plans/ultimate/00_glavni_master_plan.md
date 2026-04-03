# Glavni master plan konsolidacije sistema

Datum: 2026-04-02

Cilj ovog seta planova je da objedini sve postojece audite i planske dokumente u jedan operativni roadmap koji vodi ka verziji sistema koja je vidno bolja od trenutnog stanja opisanog u auditima, uz jasnu pripremu za instalacioni paket i GitHub distribuciju.

## Strateski cilj

- [x] Podici sistem iz kategorije "sposoban monolit u prestrukturisanju" u kategoriju "stabilna, modularna, prenosiva platforma".
- [x] Smanjiti bootstrap i coupling tako da dodavanje funkcionalnosti vise ne zahteva rucno diranje centralnog ulaza i prevelikih kontrolera.
- [x] Podici testabilnost i operativnu sigurnost tako da veci refaktor bude kontrolisan, a ne hrabar.
- [x] Uvesti deploy/install disciplinu tako da se isti kod moze brzo preneti sa jednog hostinga na drugi i kasnije reproducibilno instalirati iz GitHub repozitorijuma.

## Ciljani rezultat po audit metrikama

- [x] Arhitektura: sa ~7.5-8.5/10 na 9+/10 kroz PSR-4, modularni bootstrap i jasne servisne granice.
- [x] Odrzivost koda: sa ~6/10 na 8.5+/10 kroz razbijanje ogromnih kontrolera, servisni sloj, repozitorijume i konvencije.
- [x] Bezbednost: sa ~7-9/10 na 9+/10 kroz API security matricu, cistije logove, konzistentan CORS/CSRF/token model i session hardening.
- [x] Testabilnost: sa ~4.5/10 na 8+/10 kroz ispravljen PHPUnit bootstrap, test DB profil, feature/unit smoke paket i CI gate.
- [x] Performanse i operativna dijagnostika: sa "radi, ali sumno i I/O skupo" na "merljivo brze, tise u produkciji, detaljno u debug rezimu".
- [x] Prenosivost/distribucija: sa "projekat koji radi na trenutnom hostingu" na "projekat koji ima instalacioni postupak, migracije, seed, asset build i release artefakt".

## Redosled realizacije

### Talas 1: Temelj
- [x] Izvesti `01_arhitektura_bootstrap_i_di.md`.
- [x] Izvesti osnovni deo `03_testovi_ci_i_kvalitet.md` cim PSR-4 i bootstrap budu stabilni.

### Talas 2: Modularizacija i domenska disciplina
- [x] Izvesti `02_modularizacija_kontrolera_modela_i_servisa.md`.
- [x] Paralelno uvoditi testove iz `03_testovi_ci_i_kvalitet.md` za svaki izdvojeni servis ili kontroler.

### Talas 3: Runtime, bezbednost i opservabilnost
- [x] Izvesti `04_security_performanse_cache_i_logovanje.md`.
- [x] Uvezati exception handling, centralni logger, route/view cache i sigurnosnu matricu API ruta.

### Talas 4: Instalacioni paket i GitHub distribucija
- [x] Izvesti `05_instalacioni_paket_deploy_i_github_distribucija.md`.
- [x] Proveriti "fresh install" iz cistog checkout-a na novom hostingu ili staging okruzenju.

## Zajednicka pravila za sve planove

- [x] Ne uvoditi "big bang rewrite"; menjati sistem u malim, proverljivim etapama.
- [x] Svaku vecu izmenu vezati za regresioni test ili barem definisanu rucnu smoke proceduru.
- [x] Ne brisati postojecu dokumentaciju i audite; novi `ultimate` planovi postaju operativni sloj iznad njih.
- [x] Kod svakog zavrsenog taska dopisati sta je uradjeno, gde je izmenjeno i kako je provereno.
- [x] Ako se pojavi konflikt izmedju "brze isporuke" i "dugorocne prenosivosti", prednost dati resenju koje olaksava instalaciju i odrzavanje na vise hosting okruzenja.

## Definicija "gotovo"

- [x] `public_html/index.php` postaje tanak ulaz, bez rucnog require lanca i bez `glob()` autoloada kontrolera.
- [x] Najveci kontroleri su razbijeni na manje kontrolere, servise i repozitorijume sa jasnim odgovornostima.
- [x] Postoji centralni logger sa nivoima, debug sum je ugasen u produkciji, a greske su korelisane i citljive.
- [x] Dinamicke rute i view cache imaju pouzdanu invalidaciju i fallback.
- [x] API rute imaju dokumentovan auth/CORS/CSRF/rate-limit model.
- [x] PHPUnit testovi se stvarno pokrecu i postaju obavezan sigurnosni pojas za dalji refaktor.
- [x] Postoji dokumentovan i skriptovan install/deploy tok za novi server i GitHub korisnika.
