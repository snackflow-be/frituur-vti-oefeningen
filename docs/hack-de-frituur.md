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
3. Plak het blok hieronder. Zegt de browser dat plakken geblokkeerd is? Typ dan `allow pasting`, druk op Enter
   en plak opnieuw. De browser doet dat om je te beschermen tegen oplichters, hier mag het. (Rode fout
   `SyntaxError` na `allow pasting`? Dan was het niet nodig: gewoon verder.)
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
