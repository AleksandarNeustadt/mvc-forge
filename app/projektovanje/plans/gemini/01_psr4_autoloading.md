# 📋 Plan 01: Implementacija PSR-4 Autoloadinga
**Cilj:** Ukloniti `glob()` i manualne `require_once` pozive iz `index.php` i preći na Composer standard.

---

## 🛠️ Detaljni Koraci

### Faza 1: Priprema `composer.json`
- [ ] Analizirati trenutne putanje (`core/`, `mvc/models/`, `mvc/controllers/`).
- [ ] Definisati korenski namespace (predlog: `App\`).
- [ ] Ažurirati `composer.json` sa sledećom mapom:
  ```json
  "autoload": {
      "psr-4": {
          "App\\Core\\": "core/",
          "App\\Models\\": "mvc/models/",
          "App\\Controllers\\": "mvc/controllers/",
          "App\\Services\\": "core/services/"
      },
      "files": [
          "core/helpers.php"
      ]
  }
  ```

### Faza 2: Namespacing Klasa (Najbitniji deo)
- [ ] Dodati `namespace App\Core;` na vrh svih klasa u `core/` folderu.
- [ ] Dodati `namespace App\Models;` za sve modele u `mvc/models/`.
- [ ] Dodati `namespace App\Controllers;` za sve kontrolere.
- [ ] Ažurirati sve `use` naredbe unutar klasa da reflektuju nove namespace (npr. `use App\Core\Database;` unutar modela).

### Faza 3: Čišćenje `index.php`
- [ ] Obrisati sve `require_once` linije koje učitavaju klase.
- [ ] Obrisati `glob()` petlju za učitavanje kontrolera.
- [ ] Ostaviti samo `require __DIR__ . '/../app/vendor/autoload.php';`.
- [ ] Inicijalizovati ruter koristeći puni namespace (npr. `$router = new \App\Core\Routing\Router($routeCollection);`).

---

## 🔍 Detalji na koje treba obratiti pažnju
- **Case Sensitivity:** PSR-4 zahteva da ime fajla i ime klase budu identični (uključujući velika/mala slova). Proveriti sve fajlove.
- **Helperi:** Globalne funkcije u `helpers.php` ne mogu imati namespace ako želimo da ostanu globalno dostupne.
- **DynamicRouteRegistry:** Ažurirati mehanizam koji instancira kontrolere iz stringa (baze) da dodaje `App\Controllers\` prefiks.

## 🏁 Rezultat
Čist `index.php` sa manje od 20 linija koda i drastično brže učitavanje klasa samo onda kada su potrebne.
