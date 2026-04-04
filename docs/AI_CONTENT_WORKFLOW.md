# AI Content Workflow

Ovaj vodič je namenjen baš scenariju: *"Instalirao sam MVC Forge na domen, sada želim da AI agent napravi sadržaj, SEO strukturu i objavi ga kroz postojeći API/admin model."*

## Šta AI treba da zna o MVC Forge sajtu

- Backend je PHP MVC/CMS aplikacija sa API rutama pod `/api/*`.
- API autentifikacija ide preko `POST /api/auth/login`, a zatim svaki zahtev koristi `Authorization: Bearer <TOKEN>`.
- Sadržaj se najčešće modeluje kroz:
  - **Pages** za statične i landing stranice,
  - **Posts** za blog/projekat članke,
  - **Categories** i **Tags** za blog taksonomiju,
  - **Menus** za navigacione strukture,
  - **Languages** za lokalizovane varijante.
- API rute rade bez jezičkog prefiksa, npr. `https://example.com/api/pages`.
- Detaljna tehnička specifikacija endpoint-a je u [app/API_DOCUMENTATION.md](../app/API_DOCUMENTATION.md).

## Pravilo pre rada

Pre nego što AI krene da kreira sadržaj, treba da uradi ovaj redosled:

1. Prijavi se na API preko `POST /api/auth/login`.
2. Sačuva token iz `data.token`.
3. Učita postojeće jezike preko `GET /api/languages`.
4. Učita postojeće stranice, kategorije, tagove i menije da ne pravi duplikate:
   - `GET /api/pages?language_code=sr`
   - `GET /api/posts?language_code=sr`
   - `GET /api/categories?language_code=sr`
   - `GET /api/tags?language_code=sr`
   - `GET /api/menus?language_code=sr`
5. Tek onda kreira ili ažurira sadržaj.

## SEO pravila za AI generisanje sadržaja

Za svaki page ili post, AI treba da poštuje ova pravila:

- `title` treba da bude prirodan i jasan, sa glavnom ključnom frazom.
- `slug` treba da bude kratak, čitljiv, lowercase i bez nepotrebnih reči.
- `route` za strane treba da bude stabilan i semantičan, npr. `/seo-usluge`, `/o-nama`, `/kontakt`.
- `meta_title` treba da bude SEO fokusiran, ali ne spammy.
- `meta_description` treba da jasno objasni sadržaj i razlog za klik.
- `excerpt` kod blog posta treba da bude kratak uvod koji može da se koristi i u listing karticama.
- `content` treba da bude validan HTML, sa jasnom strukturom `h2`, `h3`, paragrafima, listama i internim linkovima gde ima smisla.
- Ako postoji više jezika, prvo napravi osnovnu verziju na primarnom jeziku, pa tek onda lokalizacije.
- Ne objavljuj `status=published` dok tekst nije logički završen i SEO polja nisu popunjena.

## Predloženi prompt za AI agenta

Ovaj prompt možeš direktno kopirati i prilagoditi:

```text
Ti si content i SEO agent za MVC Forge sajt.

API dokumentacija je u app/API_DOCUMENTATION.md, a domen je https://forgeng.dev.
Koristi postojeći API, nemoj menjati PHP kod osim ako to eksplicitno tražim.

Zadatak:
1. Prijavi se na /api/auth/login pomoću admin naloga koji ću ti dati.
2. Učitaj postojeće jezike, stranice, blog kategorije, tagove i menije.
3. Proveri da li sadržaj koji tražim već postoji, da ne napraviš duplikat.
4. Napravi novu stranicu ili blog post sa SEO-friendly title, slug, route, meta_title, meta_description i HTML sadržajem.
5. Ako su potrebne kategorije ili tagovi, napravi ih pre kreiranja posta.
6. Ako sadržaj treba da bude u meniju, napravi ili ažuriraj menu zapis.
7. Na kraju mi vrati kratak izveštaj: šta je kreirano, koji su ID-jevi, koji su javni URL-ovi, i šta je ostalo u draft statusu.

Content brief:
- Jezik: sr
- Tip sadržaja: [page|post]
- Tema:
- Ciljna ključna fraza:
- Stil tona:
- Status: [draft|published]
- Posebni zahtevi:

Nemoj izmišljati endpoint-e. Ako neki endpoint ili polje nije jasno iz dokumentacije, prvo mi reci šta nedostaje.
```

## Primer prompta za landing stranicu

```text
Koristeći MVC Forge API na https://forgeng.dev, napravi novu SEO landing stranicu na srpskom jeziku za uslugu "izrada web aplikacija".

Zahtevi:
- route: /izrada-web-aplikacija
- primarna ključna fraza: izrada web aplikacija
- ton: profesionalan, jasan, bez praznog marketing teksta
- sadržaj treba da ima hero uvod, 3 glavne sekcije, FAQ sekciju i CTA ka kontakt strani
- popuni title, slug, meta_title i meta_description
- stranicu objavi kao aktivnu, ali nemoj je dodavati u meni dok ne pregledam

Pre kreiranja proveri da li već postoji page sa istim route ili slug.
Na kraju vrati page ID i javni URL.
```

## Primer prompta za blog post

```text
Koristeći MVC Forge API na https://forgeng.dev, napravi blog post na srpskom jeziku o temi "Kako izabrati PHP MVC framework za SEO orijentisan sajt".

Zahtevi:
- status neka prvo bude draft
- napravi ili iskoristi kategoriju "Web development"
- dodaj tagove "PHP", "MVC", "SEO", "CMS"
- napiši SEO-friendly title, slug, excerpt, meta_title, meta_description i HTML content
- content treba da ima uvod, poređenje kriterijuma, praktične preporuke i zaključak
- nemoj preterivati sa keyword stuffing-om

Pre pisanja učitaj postojeće kategorije i tagove da izbegneš duplikate.
Na kraju vrati post ID, slug, kategorije i tagove koje si koristio.
```

## Minimalni API primer za AI agenta

```bash
BASE_URL="https://forgeng.dev/api"

TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YOUR_PASSWORD"}' \
  | php -r '$r=json_decode(stream_get_contents(STDIN), true); echo $r["data"]["token"] ?? "";')

curl -s "$BASE_URL/languages" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

curl -s "$BASE_URL/pages?language_code=sr" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

## Kada AI ne treba direktno da objavljuje

Za sledeće slučajeve bolje je da AI prvo pripremi nacrt, a da objava ide tek posle ručne provere:

- homepage i glavne prodajne landing stranice,
- pravni tekstovi, politika privatnosti, uslovi korišćenja,
- visoko osetljiv SEO sadržaj gde ne želiš slučajan duplikat ruta/slova/slugova,
- sadržaj koji menja postojeću navigaciju ili URL strukturu.

## Predlog operativnog workflow-a

1. Ti AI-ju daš ovaj vodič + API dokumentaciju + content brief.
2. AI prvo uradi inventar postojećeg sadržaja.
3. AI predloži strukturu novih stranica/postova.
4. Ti potvrdiš.
5. AI kreira draft sadržaj kroz API.
6. Ti proveriš u admin panelu.
7. AI tek onda ažurira `status` u `published` ili menja menu pozicije.
