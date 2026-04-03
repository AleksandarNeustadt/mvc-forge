# Kako dodati Contact Formu u Navigaciju kroz Page Manager

## Koraci

### 1. Otvorite Page Manager
- Idite na Dashboard → Pages (`/dashboard/pages`)
- Kliknite na "Create Page"

### 2. Popunite formu sa sledećim podacima:

**Osnovni podaci:**
- **Title**: `Kontakt` (ili `Contact`)
- **Slug**: `contact` (ili bilo koji drugi slug, npr. `kontakt`, `contact-us`)
- **Route**: Možete postaviti bilo koji route (npr. `/contact`, `/kontakt`, `/contact-us`, `/kontaktirajte-nas`)

**Application:**
- **Application**: Izaberite `Contact Form` ⭐ (ovo je najbolji način!)
  - Contact aplikacija omogućava dinamičko kreiranje ruta
  - Možete postaviti bilo koji route - sistem ne forsira `/contact`
  - Prikazuje se info poruka o mogućnostima dinamičkog route-a
  - Route se automatski generiše iz slug-a ako ga ne unesete

**Status:**
- ✅ **Active**: Označite (stranica mora biti aktivna)
- ✅ **Show in Menu**: Označite (ovo je ključno za navigaciju!)
- **Menu Order**: Unesite broj (npr. `10`) - određuje redosled u meniju

**Ostalo:**
- **Content**: Možete ostaviti prazno (contact forma se renderuje kroz MainController)
- **Template**: `default`
- **Meta Title**: `Kontakt - aleksandar.pro` (opciono)
- **Meta Description**: `Kontaktirajte nas` (opciono)

### 3. Sačuvajte stranicu
- Kliknite na "Create Page"
- Stranica će biti kreirana i automatski dodata u navigaciju

## Kako funkcioniše?

1. **Navigacija koristi `Page::getMenuItems()`** koji vraća sve stranice gde je:
   - `is_in_menu = true`
   - `is_active = true`
   - Sortirano po `menu_order`

2. **Ruta `/contact` već postoji** u `routes/web.php` sa:
   - Autentifikacijom (korisnik mora biti prijavljen)
   - Rate limiting-om
   - CSRF zaštitom
   - Handler: `MainController::contact()`

3. **Kada korisnik klikne na "Kontakt" u navigaciji:**
   - Link vodi na `/contact`
   - Router prvo proverava statičke rute (iz `web.php`)
   - Pronalazi rutu `/contact` i poziva `MainController::contact()`
   - Contact forma se renderuje sa svom zaštitom

## Prednosti korišćenja Contact aplikacije

✅ **Standardizovan CMS pristup** - Contact forma je sada integrisana kao aplikacija u Page Manager sistemu  
✅ **Dinamičko kreiranje ruta** - Možete kreirati bilo koji route (npr. `/contact`, `/kontakt`, `/contact-us`)  
✅ **Fleksibilnost** - Nema hardkodiranih ruta - sve je dinamičko kroz Page Manager  
✅ **Konzistentnost** - Isti pristup kao Blog aplikacija - sve je centralizovano kroz Page Manager  
✅ **Lakše održavanje** - Sve stranice su na jednom mestu, lako se vidi koja je Contact aplikacija  
✅ **Multi-language support** - Možete kreirati različite rute za različite jezike (npr. `/contact` za EN, `/kontakt` za SR)  

## Napomena

- Stranica u Page Manageru služi za **dodavanje u navigaciju** i **označavanje kao Contact aplikacija**
- Stvarni handler je `MainController::contact()` koji je već implementiran
- Ne morate da menjate ništa u kodu - samo kreirajte stranicu kroz dashboard sa Contact aplikacijom

## Provera

Nakon kreiranja stranice:
1. Osvežite sajt
2. Proverite navigaciju - trebalo bi da vidite "Kontakt" link
3. Kliknite na link - trebalo bi da se otvori contact forma
4. Proverite da li je forma zaštićena (zahteva login)

