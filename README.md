# Frituur VTI — oefenrepo voor de gastles

Dit is de code van een echte webapp: online bestellen voor een frituur, gebouwd tijdens de gastles op campus VTI
Waregem. Live versie: **https://frituurvti.staging.snackflow.be** (fictieve klant, bestel gerust).

**Ga naar [docs/opdrachtblad.md](docs/opdrachtblad.md) voor de opdrachten.** Snelste start: knop **Code → Codespaces →
Create codespace on main** bovenaan deze pagina. Na ± 3 minuten heb je een werkende omgeving in je browser.

---


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
