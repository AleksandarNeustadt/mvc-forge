# Plan 04 support: route cache i view cache

## Sta je uvedeno

- `DynamicRouteRegistry` sada koristi file cache za mapu dinamicnih ruta (`dynamic_routes.registry.v1`) sa TTL 3600s.
- `DynamicRouteRegistry::clearCache()` brise i in-memory stanje i file cache, pa `DashboardPageService` create/update/delete tokovi i dalje pouzdano invalidiraju rute.
- Ako je cache payload korumpiran, `Cache::get()` ga odbacuje i brise fajl, posle cega se podaci ponovo ucitavaju iz baze.
- `VIEW_CACHE=true|false` je uveden kroz `.env.example` i bootstrap sada mapira taj flag na `ViewEngine::setCacheEnabled()`.
- Cache upisi su atomarni i temp artefakti se brisu na neuspehu u `Cache` i `ViewEngine`.

## Lokalni mikro benchmark

Merenje je radjeno nad `/o-autoru` rutom u test bootstrap-u, sa resetom file cache-a pre cold merenja i resetom samo in-memory statike pre warm merenja.

Rezultat:

- cold lookup: `2.813ms`
- warm cache lookup: `0.026ms`

Zakljucak:

- warm path vise ne mora svaki put da pogodi `pages` tabelu za izgradnju dinamicne route mape,
- dobitak je mali po jednom requestu, ali direktno uklanja ponovljeni DB trosak na svakoj dinamickoj ruti.
