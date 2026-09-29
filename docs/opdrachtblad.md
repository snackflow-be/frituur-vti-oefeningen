# Opdrachtblad Frituur VTI

Werk per twee. Deel 1 doe je tegen de live site, deel 2 in je eigen Codespace.

**Tip:** plak elke regel uit een grijs codeblok apart in de terminal en druk op Enter. Twee regels tegelijk plakken
werkt niet.

# Deel 1: praat met de API

Je kent C#. Dan ken je HTTP-verzoeken misschien al van `HttpClient`. Hier doe je hetzelfde met **curl**, een
programma waarmee je een verzoek naar een website stuurt vanuit een terminal. De app is gebouwd met **Laravel**
(een PHP-framework, een beetje zoals ASP.NET Core voor PHP). Je hoeft geen PHP te schrijven.

Woordjes die je nodig hebt:

- **API**: een adres dat geen webpagina teruggeeft maar gegevens (hier in JSON, zoals een C#-object als tekst).
- **GET**: gegevens ophalen. **POST**: gegevens versturen om iets aan te maken.
- **Statuscode**: een nummer in het antwoord. 200 en 201 betekenen gelukt, 422 betekent "je gegevens kloppen niet".
- **Token**: een geheime code waarmee alleen jij je eigen bestelling kan bekijken.

Open een terminal (in Codespaces: menu ☰ → Terminal → New Terminal) en zet het adres van de app klaar:

```bash
URL="https://frituurvti.staging.snackflow.be"
```

## Opdracht 1: het menu ophalen

```bash
curl -s "$URL/api/menu" -H "Accept: application/json"
```

Je krijgt één lange regel. Wil je het leesbaar? Voeg `| python3 -m json.tool` toe achteraan.

Vragen:

1. Hoeveel categorieën zie je? Hoe heten ze?
2. Wat kost een grote friet? Let op: de prijs staat in **centen** (`price_cents`) én als tekst (`price`).
3. Zoek het `id` van een friet en van een saus. Schrijf ze op, je hebt ze in opdracht 2 nodig.
4. Wat staat bij `ordering` en `is_open`? Wat zou er veranderen als de baas het bestellen sluit?

## Opdracht 2: een bestelling plaatsen

Vervang `3` en `16` door de `id`'s die jij in opdracht 1 vond. `-X POST` zegt dat je iets verstuurt, `-d` is de
inhoud (JSON).

```bash
curl -s -X POST "$URL/api/orders" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"customer_name": "Jouw naam", "customer_phone": "0470 12 34 56",
       "lines": [{"product_id": 3, "quantity": 2}, {"product_id": 16, "quantity": 1}]}'
```

Je krijgt statuscode 201 en een `order` terug.

Vragen:

1. Wat is je bestelnummer (`number`, bv. `VTI-042`)?
2. Wat is het totaal? Reken zelf na met de prijzen uit opdracht 1. De server rekent altijd zelf; een prijs die jij
   meestuurt wordt genegeerd. Probeer maar eens `"price_cents": 1` bij een regel te zetten.
3. Kopieer de waarde van `token`. Volg je bestelling met (vervang `JOUW-TOKEN`):

```bash
curl -s "$URL/api/orders/JOUW-TOKEN" -H "Accept: application/json"
```

De `status` is `nieuw`. Vraag aan de "keuken" (een klasgenoot met de login) om de bestelling te starten en doe het
verzoek opnieuw: wat is de status nu?

## Opdracht 3 (als je tijd over hebt): een bestelling met fouten en de 422 lezen

Stuur bewust een slechte bestelling: naam van 1 letter, geen geldig gsm-nummer en een leeg mandje. Voeg `-i` toe
om ook de statuscode te zien.

```bash
curl -s -i -X POST "$URL/api/orders" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"customer_name": "J", "customer_phone": "abc", "lines": []}'
```

Bovenaan zie je `422 Unprocessable Content`. In de JSON staat:

- `message`: een korte samenvatting voor mensen.
- `errors`: per veld een lijstje met wat er misloopt, bv. `customer_phone` en `lines`.

Vragen:

1. Welke drie velden staan in `errors`? Wat zeggen de meldingen?
2. Verbeter één fout per keer en stuur opnieuw. Wanneer krijg je 201?
3. Probeer `"quantity": 0`, `"quantity": 21` en een `product_id` dat niet bestaat (bv. `99999`). Wat zegt de server?
4. Bonus: stuur dezelfde geldige bestelling vier keer snel na elkaar met hetzelfde gsm-nummer. Er mogen er maar drie
   per minuut. Wat krijg je bij de vierde? Kijk naar `retry_after` in het antwoord (dat is een 429).

## De tests draaien

Tests zijn kleine programma's die de app automatisch controleren. Wil je ze lokaal draaien, dan heb je de code op je
computer nodig (zie `README.md`, stap "Lokaal draaien"). Daarna:

```bash
php artisan test
```

Groen betekent: alles werkt. Rood betekent: er is iets stuk en de test zegt welk bestand en welke regel. Wil je
maar één testbestand draaien?

```bash
php artisan test tests/Feature/Api/PlaceOrderTest.php
```

Kijk daarna in `tests/Feature/Api/PlaceOrderFailuresTest.php`: dat zijn precies de fouten uit opdracht 3, maar dan
als test. Kun jij er zelf een bijschrijven?


# Deel 2: vind de bug (in je Codespace)

Een echte app heeft **tests**: kleine programma's die controleren of de code doet wat ze moet doen. Bij Frituur VTI
zijn er 154. Ze zijn allemaal groen, behalve op twee speciale branches waar wij een fout in verstopt hebben. Jouw
job: de test lezen, de fout vinden, ze herstellen, en de test groen krijgen. Precies wat een ontwikkelaar elke dag doet.

## Klaarzetten

In de terminal van je Codespace:

```bash
git switch bug/1-totaalprijs
php artisan test --filter=OrderTotalTest
```

Je ziet een paar rode tests. Ze gaan allemaal over dezelfde fout: begin met de eerste. Lees de boodschap goed: ze
zegt wat ze **verwachtte** en wat ze **kreeg**.

## Bug 1: het totaal klopt niet (makkelijk)

- De test heet `test_totaal_met_aantallen_groter_dan_een_en_meerdere_regels`. Wat verwacht ze, wat krijgt ze?
- Wanneer gaat het mis: bij 1 stuk of bij meer stuks van hetzelfde product?
- De bestelling wordt gemaakt in `app/Actions/PlaceOrder.php`. Zoek de regel waar het bedrag per lijn berekend wordt.
- Herstel de fout, draai de test opnieuw. Groen? Draai dan alles: `php artisan test`.
- Bonus: probeer het ook in de browser. Start de site met `php artisan serve --host 0.0.0.0`, open ze via het
  pop-upvenster, bestel 3 × Grote friet en kijk naar het totaal. Vóór en na je fix.

## Bug 2: een oude bestelling verandert van prijs (moeilijker)

```bash
git stash                     # je fix van bug 1 even opzij (of eerst committen)
git switch bug/2-prijssnapshot
php artisan test --filter=OrderTotalTest
```

- Twee rode tests. Begin met `test_een_prijswijziging_verandert_een_bestaande_bestelling_niet`. Lees het scenario in de test:
  iemand bestelt, daarna verandert de baas de prijs. Wat mag er dan **niet** gebeuren?
- Het bedrag wordt correct opgeslagen in de databank (controleer dat in de test: welke asserts slagen wel?).
  Het probleem zit dus ergens tussen de databank en het antwoord van de API. Waar wordt het JSON-antwoord van een
  bestelling gemaakt? Tip: map `app/Http/Resources/Api`.
- De tweede rode test geeft zelfs een 500-fout: wat gebeurt er als het product intussen verwijderd is? Dezelfde
  oorzaak.
- Herstel, test opnieuw, en leg in één zin uit aan je buur waarom een webshop de prijs kopieert op het moment van
  bestellen in plaats van ze telkens opnieuw op te zoeken.

## Klaar?

Vergelijk met de oplossing: `git diff main` toont wat er op de branch anders is dan de werkende versie. Zo zie je
exact welke regel wij gesaboteerd hadden.
