# Plan 02 support: view-model i template dug

Ovaj dokument popisuje gde je u view sloju jos ostala poslovna logika ili mesanje model objekata i nizova, i koji standard treba koristiti u sledecim rezovima.

## Nadjene business-logika tacke u template-ima

- `mvc/views/pages/user/profile.php` i `mvc/views/pages/user/profile.template.php`
  - `User::find($profileUser['id'])` i rucno mapiranje rola kroz `toArray()`.
- `mvc/views/pages/dashboard/user-manager/edit.php` i `edit.template.php`
  - `User::find($_SESSION['user_id'])` i permission gate logika direktno u template-u.
- `mvc/views/pages/dashboard/ip-tracking/index.php` i `index.template.php`
  - direktan `Database::select(...)` za korisnike sa iste IP adrese.
- `mvc/views/pages/dashboard/partials/sidebar.php`
  - `User::find($_SESSION['user_id'])` i permission-driven menu grananje.
- `mvc/views/pages/homepage.php` i `homepage.template.php`
  - `BlogPost::find(...)`, status provera i `toArray()` priprema u samom template-u.
- `mvc/views/components/header.php`
  - `User::find(...)`, `Language::findByCode(...)`, permission grananje i router/lang introspekcija.
- `mvc/views/components/footer.php` i `footer.template.php`
  - `Language::findByCode(...)` direktno u komponenti.

## Nadjeno mesanje objekata i nizova

- `mvc/views/pages/user/profile*.php`
- `mvc/views/pages/dashboard/user-manager/permissions/index*.php`
- `mvc/views/pages/dashboard/user-manager/roles/create*.php`
- `mvc/views/pages/dashboard/user-manager/roles/edit*.php`
- `mvc/views/pages/dashboard/user-manager/edit*.php`
- `mvc/views/pages/dashboard/navigation-menu-manager/edit*.php`

Ovi template-i trenutno rade `is_object(...) ? $model->toArray() : $value`, sto znaci da view-model ugovor nije ujednacen.

## Standard za sledece izmene

- Kontroler/servis priprema jedan stabilan view-model oblik pre poziva `$this->view(...)`.
- Template ne treba da radi `Model::find(...)`, `Database::select(...)`, permission lookup ili status/business guard.
- Template ne treba da normalizuje "objekat ili niz"; dobija jedan dogovoreni oblik.
- Ako je potreban ulogovani korisnik u sidebar/header, kontroler ili shared view composer treba da prosledi vec pripremljen `currentUser`/`currentUserPermissions` model.

## Kandidati za partial/component ekstrakciju

- dashboard form shell za `create/edit` stranice sa istim breadcrumb/header/action rasporedom
- role permission checkbox grupe u user-role formama
- language selector blokovi u page/blog/navigation formama
- image upload blokovi u blog post formama
- tabelarni list header/filter/action bar u dashboard index view-ovima
