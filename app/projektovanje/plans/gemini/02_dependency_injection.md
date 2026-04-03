# 📋 Plan 02: Uvođenje Dependency Injection (DI) Container-a
**Cilj:** Smanjiti zavisnost od statičkih metoda (`Static::method()`) i omogućiti lakše testiranje koda.

---

## 🛠️ Detaljni Koraci

### Faza 1: Izrada `Container` klase
- [ ] Kreirati `core/classes/Container.php`.
- [ ] Implementirati `bind($key, $resolver)` i `make($key)` metode.
- [ ] Napraviti kontejner kao Singleton koji se inicijalizuje u `index.php`.

### Faza 2: Registracija Core Servisa
- [ ] Registrovati `Database` u kontejneru.
- [ ] Registrovati `Router` u kontejneru.
- [ ] Registrovati `Translator` u kontejneru.
- [ ] Umesto `global $router`, u helperima koristiti `Container::getInstance()->make('router')`.

### Faza 3: Refaktoring Kontrolera (Constructor Injection)
- [ ] Izmeniti baznu `Controller` klasu da prihvata `Container` (opciono).
- [ ] Izmeniti konkretne kontrolere da primaju zavisnosti kroz konstruktor:
  ```php
  public function __construct(private Database $db, private Translator $lang) {}
  ```
- [ ] Ažurirati `Router` da prilikom instanciranja kontrolera koristi kontejner kako bi automatski "ubrizgao" ove zavisnosti.

---

## 🔍 Detalji na koje treba obratiti pažnju
- **Lakoća korišćenja:** Za početak zadržati "Facade" pristup (statičke omotače), ali tako da oni unutar sebe pozivaju kontejner.
- **Performanse:** Korišćenje `ReflectionClass` u kontejneru može malo usporiti sistem, pa treba implementirati keširanje instanci (Singleton pattern unutar kontejnera).

## 🏁 Rezultat
Kôd koji je moguće testirati. Moći ćete da napišete test koji kaže: "Zameni pravu bazu sa lažnom (mock) i proveri da li kontroler ispravno šalje podatke".
