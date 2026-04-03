# Ultimate plan 05: Instalacioni paket, deploy prenosivost i GitHub distribucija

## Cilj

Na kraju refaktora imati sistem koji se moze reproducibilno instalirati na novi hosting ili povuci sa GitHub-a bez rucnog "seti se sta sve treba", uz jasnu razliku izmedju aplikacionog koda, server-specific konfiguracije, tajni i generisanih artefakata.

## Grupa A: Definisati ciljani install/deploy model

- [x] Odabrati primarni scenario:
  - [x] "self-hosted install" na cPanel/HestiaCP/shared VPS,
  - [x] "developer checkout" iz GitHub-a za buduce korisnike,
  - [x] oba, sa jednim zajednickim install tokom i tankim server-specific uputstvom.
- [x] Standardizovati projektnu strukturu koju deploy ocekuje:
  - [x] `public_html/` kao web root,
  - [x] `app/` van web root-a,
  - [x] `app/storage/` kao writable runtime folder,
  - [x] `.env` samo lokalno/server-side, nikad commitovan.
- [x] Napraviti listu sta mora biti generisano posle checkout-a: `app/vendor/`, `node_modules/`, build asseti, cache, storage folderi, `.env`, DB schema + seed.
  - [x] Dokumentovano u root `README.md`.

## Grupa B: Instalacioni skript i CLI komande

- [x] Uvesti `app/bin/console` kao centralnu operativnu tacku, oslonjenu na isti bootstrap i PSR-4 iz plana 01.
- [x] Implementirati minimalni set install/deploy komandi:
  - [x] `install:check` - proverava PHP verziju, ekstenzije, writable foldere, prisustvo `.env`, DB konekciju,
  - [x] `db:migrate` ili `schema:migrate` - izvrsava migracije/builder tok,
  - [x] `db:seed` - ubacuje minimalni admin nalog, osnovne stranice, jezike ili pocetni content gde ima smisla,
  - [x] `cache:clear` - cisti route/view cache,
  - [x] `app:key` ili ekvivalent - generise aplikacionu tajnu ako takav koncept uvedes,
  - [x] `storage:prepare` - pravi potrebne foldere i proverava dozvole.
- [x] Za destruktivne komande dodati eksplicitnu potvrdu ili `--force` flag.
  - [x] `db:migrate` odbija legacy bazu bez `schema_migrations` i trazi eksplicitan `--baseline`.

## Grupa C: Migracije, seed i reproducibilna baza

- [x] Konsolidovati aktivne migracije i legacy setup skripte u jedan jasan put: "ovo se izvrsava pri novoj instalaciji".
  - [x] `db:migrate` sada prolazi kroz `app/core/database/migrations/*.php` u sortiranom redosledu.
- [x] Uvesti tabelu ili mehanizam koji pamti izvrsene migracije da `db:migrate` bude idempotentan.
  - [x] Dodata tabela `schema_migrations`.
- [x] Definisati minimalni seed skup potreban da sajt posle install-a odmah radi:
  - [x] admin user,
  - [x] osnovne role/permission vrednosti,
  - [x] default jezici,
  - [x] pocetna homepage/page ruta,
  - [x] sistemske postavke ako postoje.
    - [x] Trenutno ne postoji posebna `settings` tabela/model, pa nema dodatnog seed koraka za ovu stavku.
- [x] Ako sadrzajni podaci ne smeju ici u standardni seed, razdvojiti "system seed" od "demo seed".
  - [x] `db:seed` radi sistemski seed, a `db:seed --demo` opciono dodaje demo stranicu `/demo`.

## Grupa D: Frontend build i release artefakti

- [x] Standardizovati da `npm run build` proizvodi sve sto web server treba da servira iz `public_html` ili dogovorene asset putanje.
- [x] Dokumentovati da li se build artefakti commituju ili generisu na deploy-u; za GitHub korisnike preporuka je reproducibilan build iz source-a, a za shared hosting moze i release zip sa vec izgradjenim assetima.
  - [x] `README.md` opisuje build iz source-a; release zip za hosting bez SSH ostaje zasebna stavka u Grupi F.
- [x] Obezbediti da cache/build artefakti nisu pomesani sa source fajlovima koje developer treba da menja.
  - [x] Root `.gitignore` odvaja `public_html/dist/`, `app/storage/*`, `node_modules/`, `app/vendor/`.

## Grupa E: GitHub-ready repozitorijum i bezbedan `.gitignore`

- [x] Proveriti da u repozitorijum ne ulaze `.env`, logovi, cache, upload-ovani privatni fajlovi, vendor/node_modules ako to nije namerna distribuciona strategija.
- [x] Dodati ili osveziti `.env.example` tako da sadrzi sve potrebne promenljive, ukljucujuci log/cache/security i DB parametre.
- [x] Napraviti kratak ali jasan `README.md` install tok:
  - [x] clone,
  - [x] `composer install`,
  - [x] `npm ci && npm run build`,
  - [x] kopiranje `.env.example` u `.env`,
  - [x] podesavanje DB kredencijala,
  - [x] `php app/bin/console install:check`,
  - [x] `php app/bin/console db:migrate --seed`,
  - [x] podesavanje web root-a na `public_html`.
- [x] Ako zelis korisnike sa GitHub-a, dodati "system requirements" sekciju: PHP verzija, potrebne ekstenzije, DB, web server rewrite pravila.

## Grupa F: Prenos sa hostinga na hosting

- [x] Definisati "migration checklist" za selidbu:
  - [x] backup baze,
  - [x] backup upload/storage fajlova,
  - [x] kopiranje source koda ili checkout release taga,
  - [x] prenos `.env` vrednosti i server-specific domen/URL podesavanja,
  - [x] composer/npm build,
  - [x] migracije ako nova verzija menja semu,
  - [x] `cache:clear`,
  - [x] smoke test glavnih ruta i admin login.
  - [x] Zapisano u `support/05_hosting_transfer_i_release.md`.
- [x] Ako hosting nema SSH ili Composer/NPM, pripremiti release paket koji se moze uploadovati kao zip sa vec instaliranim PHP vendor-om i build assetima, ali bez `.env` i bez runtime cache/log foldera.
  - [x] Dodat `scripts/build-release-package.sh`.
- [x] Dokumentovati minimalna server pravila: document root, rewrite, PHP ekstenzije, writable folderi i file permissions.
  - [x] Pokriveno u `README.md` i `support/05_hosting_transfer_i_release.md`.

## Grupa G: Verzije, release proces i rollback

- [x] Uvesti semver ili bar jasne Git tag release oznake, npr. `v1.0.0`.
- [x] Za svaku release verziju zapisati sta se menja u `CHANGELOG.md`, posebno breaking promene u `.env`, migracijama i rutama.
- [x] Definisati rollback proceduru:
  - [x] vracanje prethodnog release taga,
  - [x] restore DB backup-a ako migracija nije reverzibilna,
  - [x] restore storage/upload fajlova ako je potrebno,
  - [x] `cache:clear`.
- [x] Ako migracije mogu imati rollback, dodati `db:rollback`; ako ne, jasno dokumentovati da je DB backup obavezan pre deploy-a.
  - [x] Trenutno nema generičkog `db:rollback`, pa je DB backup obavezan i to je eksplicitno dokumentovano.

## Kriterijumi zavrsetka

- [x] Novi hosting moze dobiti radnu instalaciju iz cistog checkout-a ili release paketa uz jasan, ponovljiv niz koraka.
  - [x] Validirano 2026-04-03 kroz privremeni fresh checkout u `/tmp/ap-fresh-install`: `composer install`, `npm ci`, `npm run build`, `storage:prepare`, `app:key --force`, `install:check`, `db:migrate --seed --demo`, `cache:clear`, zatim HTTP smoke `/sr`, `/sr/login`, `/sr/demo`.
- [x] Buduci GitHub korisnik moze da procita jedan README i podigne projekat bez lova po desetinama internih markdown fajlova.
- [x] `.env`, logovi, cache i privatni runtime fajlovi su jasno odvojeni od source koda.
- [x] Selidba izmedju hostinga ima checklistu i realan fallback/rollback scenario.
