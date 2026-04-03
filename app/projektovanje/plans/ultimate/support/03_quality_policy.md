# Plan 03 support: quality policy za buduce izmene

## Pravilo 1: test ili eksplicitni smoke za svaku vecu promenu

- Svaki novi servis, middleware, router rule ili security fix mora da dobije bar jedan PHPUnit test ili jasno dokumentovan smoke scenario.
- Ako promena zavisi od baze, prvo proveriti da li moze da koristi `_test` seed ciklus kroz `composer test-db:seed`.
- Ako test nije odmah realan, PR/opis promene mora da navede zasto i koji je manuelni smoke minimum.

## Pravilo 2: zabrana novih mega-kontrolera

- Novi kontroleri treba da ostanu adapteri/tanki koordinatori.
- Domensku logiku, validaciju, mapiranje i relation sync prebacivati u servise ili modele.
- Ako jedan kontroler predje prakticni prag citljivosti, odmah ga cepati po resource domenima pre dodavanja novih feature-a.

## Pravilo 3: breaking API/route promene moraju imati migracionu belesku

- Svaka breaking promena rute, payload-a, auth pravila ili URL strukture treba da dobije:
  - sta se menja,
  - koji postojeći klijent/page tok moze da pukne,
  - kako se radi rollback ili kompatibilni bridge,
  - koje smoke/test komande potvrduju da je migracija prosla.

## Pravilo 4: CI je obavezan alarm pre veceg spajanja

- Minimalni lokalni gate pre predaje vecih izmena:
  - `cd app && composer check`
  - `npm run build`
- GitHub Actions workflow `app-ci.yml` mora ostati zelen; ako se svesno uvodi poznat dug, to treba zapisati u odgovarajuci plan/support dokument.
