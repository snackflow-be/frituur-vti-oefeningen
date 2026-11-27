# Hack de frituur 🍟

Frituur VTI heeft een bestelsite: **https://frituurvti.staging.snackflow.be**. Achter die site zit een **API**: een
stuk software op de server dat bestellingen aanneemt. Jullie gaan die API vandaag rechtstreeks aanspreken, zonder
de knoppen van de site, en proberen hem te foppen.

Werk **per twee**. Elk level geeft punten. Het duo met de meeste punten wint.

> **Spelregels**
> - Alleen deze site, alleen de opdrachten hieronder.
> - **Geen lussen** (`for`, `while`) die bestellingen spammen: de hele klas zit op één wifi, dus als jij spamt,
>   kan niemand nog bestellen.
> - Gebruik je voornaam (of een bijnaam die op het scherm mag) en een **verzonnen** gsm-nummer zoals
>   `0470 00 00 42`. Verander de laatste twee cijfers, zo heeft elk duo een eigen nummer.
> - Het keukenscherm hangt op de beamer. Wat je als naam stuurt, ziet de hele klas.

> **Nog nooit gehackt of gedebugd? Perfect.** Je hoeft niets te kunnen programmeren. Elk level is
> **kopiëren, plakken, één ding veranderen en kijken wat er terugkomt**. Klaarzetten, level 1 en level 2
> doen we eerst **samen** op de beamer.

### Zo lees je een antwoord

Na elke opdracht toont de console twee dingen: een **getal** en een **antwoord**. Klik op het pijltje ▶ om het
antwoord open te klappen.

| Getal | Betekent | Wat nu? |
|---|---|---|
| `201` | Gelukt, bestelling aangemaakt | Kijk naar de beamer! |
| `200` | Gelukt, hier is wat je vroeg | |
| `422` | Je gegevens kloppen niet | Klap open: bij `errors` staat wat er mis is |
| `404` | Bestaat niet | |
| `429` | Te veel na elkaar | Wacht een minuut |
| `500` | De server crasht | Een echte bug! Steek je hand op |

Rode tekst in de console is **niet erg**: zo zie je wat de server niet goedkeurt. Daar gaat het juist om.

Iets fout getypt? Druk op **pijltje omhoog** in de console: je vorige opdracht komt terug, en die kun je aanpassen.

## Klaarzetten (2 min)

1. Open de site op je laptop (Chrome, Edge of Firefox).
2. Druk op **F12** en klik op het tabblad **Console**.
3. Typ eerst `allow pasting` en druk op Enter. De browser blokkeert plakken in de console standaard, om je te
   beschermen tegen oplichters. Hier mag het.
4. Plak dit blok in de console en druk op Enter. Het maakt twee hulpfuncties: `bestel(...)` en `volg(...)`.

```js
async function bestel(data) {
  const r = await fetch('/api/orders', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });
  const json = await r.json();
  console.log(r.status, json);
  return json;
}

async function volg(token) {
  const r = await fetch('/api/orders/' + token, { headers: { 'Accept': 'application/json' } });
  const json = await r.json();
  console.log(r.status, json);
  return json;
}
```

Zie je `undefined`? Dan is het gelukt.

---

## Level 1: wat verkopen ze? (1 punt)

Vraag het menu op aan de API:

```js
const menu = await (await fetch('/api/menu')).json();
console.table(menu.categories.flatMap(c => c.products));
```

- Hoeveel producten zijn er?
- Wat is het **id** van de Bicky Burger? En van de Grote friet?
- Waarom staan de prijzen er twee keer in (`price_cents` en `price`)?

Tip: de volledige uitleg van de API staat op https://frituurvti.staging.snackflow.be/api/docs

## Level 2: bestellen zonder knoppen (1 punt)

```js
const mijn = await bestel({
  customer_name: 'Jens',
  customer_phone: '0470 00 00 42',
  lines: [ { product_id: 7, quantity: 1 } ],
});
```

- Welke **statuscode** krijg je? Wat betekent die?
- Welk bestelnummer kreeg je? **Kijk naar de beamer:** staat jouw bestelling op het keukenscherm?
- Bestel nu iets met twee verschillende producten in één keer.

## Level 3: frieten voor 1 cent (2 punten)

Je bent slim. Je stuurt gewoon zelf een prijs mee:

```js
await bestel({
  customer_name: 'Jens',
  customer_phone: '0470 00 00 42',
  total_cents: 1,
  lines: [ { product_id: 3, quantity: 2, price_cents: 1 } ],
});
```

- Wat kost je bestelling volgens de server?
- Waarom lukt dit niet? Leg het in één zin uit aan je buur.

## Level 4: breek de controle (1 punt per verschillende foutmelding, max 10)

De server controleert alles wat binnenkomt. Probeer zoveel mogelijk **verschillende foutmeldingen** te krijgen.
Verander telkens één ding in je bestelling. De eerste twee krijg je cadeau:

```js
// aantal 0
await bestel({ customer_name: 'Jens', customer_phone: '0470 00 00 42', lines: [ { product_id: 3, quantity: 0 } ] });

// leeg mandje
await bestel({ customer_name: 'Jens', customer_phone: '0470 00 00 42', lines: [] });
```

Druk op **pijltje omhoog**, verander één ding, Enter. Nog meer ideeën:

- een negatief aantal, of 0, of heel veel
- een kommagetal als aantal, of tekst
- een product dat niet bestaat
- hetzelfde product twee keer in je mandje
- een leeg mandje
- een raar gsm-nummer, of een vast telefoonnummer
- een naam van één letter, of alleen spaties

Noteer elke foutmelding die je krijgt. Welke **statuscode** hoort erbij?

> **C# / Blazor?** In ASP.NET doe je hetzelfde met `[Required]`, `[Range(1, 20)]` en `[StringLength(40)]`
> boven je properties. Dezelfde regels, een andere taal.

## Level 5: code in je naam (2 punten)

Stuur als naam een stukje HTML, bv. `'<b>Jens</b>'` of `'<h1>Jens</h1>'`.

- Wordt je naam op het keukenscherm **vet** of **groot**? Wat zie je wel?
- Waarom is het gevaarlijk als het wél vet zou worden? Zoek op: **XSS**.

## Level 6: de spion (2 punten)

Elke bestelling krijgt een geheime `token`. Daarmee kun je ze opvolgen:

```js
await volg(mijn.order.token);
```

(Foutmelding `mijn is not defined`? Je hebt de pagina herladen. Plak de helpers opnieuw en doe level 2 nog eens.)

- Vraag nu de bestelling van je **buren** op. Je kent hun bestelnummer (bv. `VTI-012`). Lukt `volg('VTI-012')`?
- Waarom gebruikt de frituur zo'n lange willekeurige token en niet gewoon het bestelnummer?

## Level 7: de spammer (2 punten)

Bestel **met de hand** (dus geen lus!) vier keer snel na elkaar met **hetzelfde** gsm-nummer.

- Wat gebeurt er bij de vierde keer? Welke statuscode?
- Waarom bouwt een frituur zoiets in?

## Eindbaas: wat houdt de server níet tegen? (5 punten)

De server controleert prijzen, aantallen, producten, namen en nummers. En toch kan iemand de frituur hier nog altijd
mee in de problemen brengen. **Bedenk wat** (je hoeft het niet uit te voeren). Schrijf het op en roep Briek.

## Bug bounty 🏆

Vind je iets wat écht stuk is? Een foutmelding `500`, een bestelling die lukt terwijl ze niet zou mogen, een
prijs die niet klopt? Steek je hand op. Een echte bug levert een extra prijs op.

---

## Thuis: hetzelfde in C#

Nieuw consoleproject in Visual Studio (.NET 8 of nieuwer), vervang `Program.cs` door dit:

```csharp
using System.Net.Http.Json;
using System.Text.Json;

var http = new HttpClient { BaseAddress = new Uri("https://frituurvti.staging.snackflow.be") };
http.DefaultRequestHeaders.Add("Accept", "application/json");

// 1. Het menu ophalen
var menu = await http.GetFromJsonAsync<JsonElement>("/api/menu");
foreach (var categorie in menu.GetProperty("categories").EnumerateArray())
    foreach (var product in categorie.GetProperty("products").EnumerateArray())
        Console.WriteLine($"{product.GetProperty("id")}: {product.GetProperty("name")} {product.GetProperty("price")}");

// 2. Een bestelling plaatsen (dit mandje is leeg: wat antwoordt de server?)
var antwoord = await http.PostAsJsonAsync("/api/orders", new
{
    customer_name = "Jens",
    customer_phone = "0470 00 00 42",
    lines = Array.Empty<object>()
});
Console.WriteLine((int)antwoord.StatusCode);
Console.WriteLine(await antwoord.Content.ReadAsStringAsync());
```

Lukt het? Maak er een echte bestelling van, en daarna een eigen class `Bestelling` in plaats van `new { ... }`.

---

# Over deze repo

Dit is de code van de bestelsite van Frituur VTI. Je hebt ze niet nodig voor de workshop hierboven. Meer willen? In [docs/opdrachtblad.md](docs/opdrachtblad.md) staan extra oefeningen met de code zelf: zoek twee verstopte bugs in een Codespace (knop **Code → Codespaces → Create codespace on main** bovenaan deze pagina).

Online bestellen voor Frituur VTI in Waregem (een fictieve klant voor de gastles op campus VTI). **Klanten** openen
het menu op hun gsm, kiezen frieten, snacks, sauzen en dranken, en sturen hun bestelling door met naam en
gsm-nummer. Ze krijgen een groot bestelnummer en kunnen live volgen of hun bestelling bezig is, klaar ligt of
afgehaald is. Betalen gebeurt gewoon aan de toog bij het afhalen; er is geen online betaling.

In de **keuken** hangt een scherm aan de muur (`/keuken`, na inloggen) met drie kolommen: nieuw, bezig, klaar. Een
nieuwe bestelling valt op met een flits of geluid, en één grote knop per bestelling zet ze een stap vooruit (nooit
terug). Is er iets op, dan zet de keuken het product op "uitverkocht" en kan niemand het nog bestellen. De **baas**
beheert alles zelf in het adminpaneel (`/admin`): producten en categorieën (naam, prijs, foto, volgorde), alle
bestellingen zoeken, cijfers van vandaag en deze week, bestellen tijdelijk sluiten met een boodschap,
openingsuren en personeel. Alle personeel gebruikt dezelfde login; registreren kan niet.

## Lokaal draaien (10 minuten)

Nodig: PHP 8.4 of hoger, Composer, Node 24 of hoger. Geen aparte databank nodig: lokaal gebruikt de app SQLite.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite          # enkel als dit bestand nog niet bestaat
php artisan migrate --seed              # menu (27 producten), instellingen en twee gebruikers
composer run dev                        # server + Vite; of: php artisan serve  en  npm run dev
```

Open dan <http://localhost:8000> voor het menu. Inloggen op <http://localhost:8000/login> kan met
`keuken@frituurvti.be` of `test@example.com`, wachtwoord `password` (enkel lokaal; op staging komt het wachtwoord
uit `STAGING_SEED_PASSWORD`). Je landt op het keukenscherm; het adminpaneel staat op `/admin`.

Er zijn nog geen echte foto's: zonder bestand in `public/img/menu/<slug>.jpg` (of `.webp`) toont de app een
placeholder per categorie.

## Testen

```bash
php artisan test                                  # alle PHP-tests (SQLite, in het geheugen)
DB_CONNECTION=mysql DB_DATABASE=frituurvti_test php artisan test   # zelfde op MariaDB, zoals CI
vendor/bin/pint --test                            # PHP-stijl
vendor/bin/phpstan analyse --memory-limit=1G      # statische analyse
npm run types:check                               # TypeScript
npm run check                                     # oxlint + oxfmt (Vite+), ook markdown en json
npm run build
```

Playwright-rooktesten staan in `tests/e2e/`. Ze draaien na elke staging-uitrol vanzelf; personeelsstappen slaan ze
over zonder `E2E_STAFF_EMAIL` en `E2E_STAFF_PASSWORD`.

## Uitrollen

Nooit rechtstreeks op de server. Push naar `staging` voor een automatische uitrol naar staging; `main` enkel via
een PR vanaf `staging` (CI groen), met databankbackup, health check op `/health` en automatische rollback. Alle
details, servergegevens en regels staan in [`CLAUDE.md`](CLAUDE.md).

## API

Basis-URL lokaal: `http://localhost:8000`. JSON, bedragen in centen (`*_cents`) én als tekst (`"€ 4,20"`), alle
meldingen in het Nederlands. De volledige beschrijving met voorbeeldantwoorden staat op `/api/docs`.
Bestellen: maximum 60 per minuut per IP en 3 per minuut per gsm-nummer (429 met `Retry-After`).

| Methode | Pad | Wie | Doet | Voorbeeld |
|---|---|---|---|---|
| GET | `/api/menu` | iedereen | Menu, zaakgegevens, open of dicht | `curl -s localhost:8000/api/menu -H 'Accept: application/json'` |
| POST | `/api/orders` | iedereen | Bestelling plaatsen (201, of 422/429) | `curl -s -X POST localhost:8000/api/orders -H 'Content-Type: application/json' -H 'Accept: application/json' -d '{"customer_name":"Jef","customer_phone":"0470 12 34 56","lines":[{"product_id":3,"quantity":2}]}'` |
| GET | `/api/orders/{token}` | wie de token heeft | Bestelling volgen (de token staat in het antwoord van het plaatsen) | `curl -s localhost:8000/api/orders/VOORBEELD-TOKEN -H 'Accept: application/json'` |
| GET | `/api/docs` | iedereen | Deze documentatie als pagina | `curl -s localhost:8000/api/docs` |
| GET | `/keuken/bestellingen` | personeel | Open bestellingen van vandaag | `curl -s localhost:8000/keuken/bestellingen -b cookies.txt -H 'Accept: application/json'` |
| PATCH | `/keuken/bestellingen/{id}/status` | personeel | Volgende stap (nieuw, bezig, klaar, afgehaald) | `curl -s -X PATCH localhost:8000/keuken/bestellingen/1/status -b cookies.txt -H 'Content-Type: application/json' -H 'Accept: application/json' -H "X-XSRF-TOKEN: $XSRF" -d '{"status":"bezig"}'` |
| GET | `/keuken/producten` | personeel | Alle producten met uitverkocht-vlag | `curl -s localhost:8000/keuken/producten -b cookies.txt -H 'Accept: application/json'` |
| PATCH | `/keuken/producten/{id}/uitverkocht` | personeel | Product uitverkocht aan of uit | `curl -s -X PATCH localhost:8000/keuken/producten/3/uitverkocht -b cookies.txt -H 'Content-Type: application/json' -H 'Accept: application/json' -H "X-XSRF-TOKEN: $XSRF" -d '{"is_sold_out":true}'` |
| PATCH | `/keuken/bestellen` | personeel | Bestellen open of dicht (sluiten vraagt een boodschap) | `curl -s -X PATCH localhost:8000/keuken/bestellen -b cookies.txt -H 'Content-Type: application/json' -H 'Accept: application/json' -H "X-XSRF-TOKEN: $XSRF" -d '{"is_open":false,"closed_message":"Vandaag gesloten"}'` |

De personeelsroutes hebben een sessie nodig: eerst inloggen op `/login` in de browser, of met curl een cookiebestand
(`-c cookies.txt`) aanmaken en de waarde van de cookie `XSRF-TOKEN` (URL-gedecodeerd) als `$XSRF` meegeven. Zonder
login krijg je 401. Het adminpaneel (`/admin/...`) werkt met gewone formulieren in de browser en heeft geen API.
Pagina's voor klanten: `/`, `/bestellen`, `/bevestiging/{token}`, `/bestelling/{token}`. Gezondheid: `/health`.

## Meer

Het bouwverslag van het agent-team staat in `docs/run/` (begin bij `00-tijdlijn.md`). Een oefenblad voor leerlingen
staat in `docs/opdrachtblad.md`.
