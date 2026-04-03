# Plan 02 support: model, schema i raw SQL konvencije

Ovaj dokument je radni standard za nove izmene u planu 02 i kasniji install/deploy tok iz plana 05.

## Model konvencije

- Svaki model eksplicitno definise `$table`, `$primaryKey`, `$fillable`, `$hidden`, `$casts` i `$timestamps` kada odstupa od default ponasanja baznog `Model`.
- Javne finder metode koriste `Model::query()`, `Model::findByField()` ili `Model::existsByField()` kada je moguce, da bi bind parametri ostali standardizovani.
- Metode koje vracaju jedan zapis vracaju `?static`; metode koje vracaju liste vracaju niz modela ili pripremljen niz view/API struktura, ali ne mesaju ta dva oblika u istoj metodi.
- Mutacije koje menjaju vise tabela moraju ici kroz `Database::transaction()`.
- Soft-delete i audit pravila ostaju u modelu ili domenskom servisu, ne u template-u.

## Schema konvencije

- Primarni put za programsku izmenu seme u runtime/admin delu je `DatabaseBuilder`, `DatabaseTableBuilder` i `DashboardSchemaService`.
- Dinamicna imena tabela, kolona i indeksa moraju ici kroz `Database::assertIdentifier()` i `Database::quoteIdentifier()`.
- Default vrednosti u schema builder-u moraju biti quote-ovane preko PDO konekcije ili eksplicitno normalizovane za `NULL`/bool/numeric tipove.
- Legacy migracije i raw SQL fajlovi se za sada ne brisu dok plan 05 ne definise install paket i aktivan deploy tok, ali nova schema logika treba da ide kroz builder/service sloj.

## Raw SQL escape hatch pravila

- Preferirani API je `QueryBuilder` ili `DatabaseBuilder`.
- `QueryBuilder::whereRaw()` je dozvoljen samo kada ne postoji typed helper i kada su vrednosti prosledjene kroz bind parametre, a ne interpolirane u SQL string.
- Za multi-column search treba koristiti `QueryBuilder::whereAnyLike()` umesto rucnog sklapanja `OR ... LIKE` izraza.
- `Database::select()`, `selectOne()`, `execute()` i `query()` sa rucno napisanim SQL-om su prihvatljivi za:
  - stabilne staticke upite bez korisnickog unosa u SQL tekstu,
  - kompleksne read/report query-je koji jos nisu prebaceni u QueryBuilder,
  - legacy migracione skripte koje ce biti razdvojene tek u planu 05.
- Nisu prihvatljivi novi upiti koji interpoliraju korisnicki input u identifikatore, `ORDER BY`, `WHERE`, `JOIN` ili `LIMIT/OFFSET` bez whitelist/validator sloja.

## Gde ide nova funkcionalnost

- HTTP request/response, redirect, flash i auth gate: kontroler.
- Validacija i mapiranje ulaza, transakcije, domenski guard-i, audit/log/cache invalidacija: servis.
- Jednostavni finder-i, relacije i per-model invarianti: model.
- Schema introspekcija i tabela/kolona operacije: `DashboardSchemaService` + DB builder klase.
- View treba da dobije pripremljen view model; API response treba da dobije pripremljen asocijativni niz.
