# Plan 04: Baza podataka, modeli i konvencije koda

**Cilj:** Smanjiti dupliranje SQL-a, ujednačiti pristup upitima i pripremiti model sloj za lakše testiranje, bez uvodenja punog ORM-a ako nije cilj.

---

## Faza A: Stanje šeme i migracije

- [ ] Inventarisati sve PHP skripte / builder pozive koji kreiraju ili menjaju tabele (`DatabaseTableBuilder`, migracije u `app/` ako postoje folder).
- [ ] Uveriti se da postoji jedan kanonički način „kako se šema menja“ (dokumentovati u jednoj rečenici u kodu ili postojećem dev uputstvu).
- [ ] Proveriti da li `DatabaseBuilder::getTables()` i ostalo služe samo runtime-u ili i deploy-u — izbeći dvostruko vođenje šeme (SQL ručno + PHP builder) bez razloga.

---

## Faza B: QueryBuilder i sirovi SQL

- [ ] Pretražiti `mvc/models` za `Database::select` / sirov SQL stringove.
- [ ] Za svaki upit sa konkatenacijom korisničkog ulaza u string: **obavezno** prebaciti na parametrizovane upite (QueryBuilder ili `prepare` sa bind).
- [ ] Za ponavljajuće obrasce (npr. `findById`, `findBySlug`): izvući u privatne metode modela ili u mali `*Repository` razred da se ne kopira isti SQL.

---

## Faza C: Model konvencije

- [ ] Dogovoriti imenovanje: `$table`, `$primaryKey`, `$fillable` ili ekvivalent ako već postoji u `Model` bazi — uskladiti sve modele.
- [ ] Za svaki model dokumentovati (kratki PHPDoc) koje tabele i ključeve koristi.
- [ ] Gde model vraća niz redova: dogovoriti da li uvek vraća asocijativni niz iz baze ili mali DTO / stdClass (konzistentno kroz projekat).

---

## Faza D: Transakcije

- [ ] Identifikovati operacije koje diraju više tabela (npr. brisanje stranice + vezani sadržaj).
- [ ] Gde nedostaje: omotati u `Database::transaction` ili ekvivalent koji već postoji u `Database` klasi (proveriti API).

---

## Faza E: Testiranje (opciono ali preporučeno)

- [ ] Dodati jedan PHPUnit test koji koristi SQLite in-memory ili test bazu i proverava jedan `QueryBuilder` put ili jedan model `find`.
- [ ] U `phpunit.xml` konfigurisati env za test DB da ne dira produkciju.

---

## Faza F: Povezanost sa PSR-4 (plan 01)

- [ ] Nakon namespace migracije: svi `use` u modelima ka `App\Core\Database` i sl. moraju biti ispravni.
- [ ] Proveriti da li `class_exists('Page')` u `DynamicRouteRegistry` postaje `class_exists(Page::class)` sa importom.

---

## Kriterijumi završetka

- [ ] Nema novih sirovih SQL upita bez bind parametara u izmenjenom kodu.
- [ ] Modeli slede istu baznu konvenciju (bar za nove/izmenjene fajlove).
- [ ] Kritične višetabelarne operacije su u transakcijama gde ima smisla.

---

## Šta ovaj plan namerno ne radi

- Ne uvodi Doctrine/Eloquent osim ako se eksplicitno odluči drugačije (veliki obim).
- Ne menja šemu baze bez posebnog migracionog plana i bekapa.
