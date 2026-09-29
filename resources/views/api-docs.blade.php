<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>API — Frituur VTI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@800&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #161412; --fg: #f4efe8; --muted: #a59a8c; --card: #201c18; --line: #3a332b;
            --accent: #ff5126; --ink: #161210;
        }
        * { box-sizing: border-box; }
        html { background: var(--bg); color-scheme: dark; }
        body { margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 17px; line-height: 1.55; color: var(--fg); background: var(--bg); }
        main { max-width: 900px; margin: 0 auto; padding: 32px 16px 96px; }
        h1, h2, h3 { font-family: 'Archivo', 'DM Sans', sans-serif; font-weight: 800; letter-spacing: -0.01em; margin: 0 0 12px; }
        h1 { font-size: clamp(2.2rem, 6vw, 3.6rem); line-height: 1.05; }
        h2 { font-size: 1.7rem; margin-top: 56px; padding-top: 24px; border-top: 1px solid var(--line); }
        h3 { font-size: 1.15rem; margin-top: 28px; }
        p.lead { color: var(--muted); font-size: 1.1rem; margin: 0 0 24px; }
        a { color: var(--accent); }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.92em; }
        code { background: var(--card); border: 1px solid var(--line); border-radius: 4px; padding: 1px 6px; }
        pre { background: var(--card); border: 1px solid var(--line); border-radius: 4px; padding: 16px; overflow-x: auto; margin: 12px 0 20px; }
        pre code { background: none; border: 0; padding: 0; }
        .route { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin: 32px 0 8px; }
        .method { font-family: 'Archivo', sans-serif; font-weight: 800; font-size: 0.85rem; letter-spacing: 0.06em; background: var(--accent); color: var(--ink); border-radius: 4px; padding: 4px 10px; }
        .path { font-family: ui-monospace, Menlo, monospace; font-size: 1.15rem; font-weight: 600; }
        .name { color: var(--muted); font-size: 0.95rem; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 0.97rem; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .tag { display: inline-block; background: var(--card); border: 1px solid var(--line); border-radius: 4px; padding: 2px 8px; font-size: 0.85rem; color: var(--muted); }
        ul { padding-left: 22px; } li { margin: 4px 0; }
    </style>
</head>
<body>
<main>
    <h1>Frituur VTI — API</h1>
    <p class="lead">
        Bestellen op de gsm zonder online betaling. JSON, UTF-8, tijden als ISO 8601 met offset
        (<code>2026-09-29T18:42:10+02:00</code>), bedragen in centen (<code>*_cents</code>) én geformatteerd
        (<code>"€ 3,50"</code>). Alle boodschappen zijn Nederlands.
        Basis-URL: <code>{{ $baseUrl }}</code>.
    </p>

    <h2>Publiek</h2>
    <p>Geen login, geen sessie, geen CSRF. Limiet: 600 verzoeken per minuut per IP.</p>

    <div class="route"><span class="method">GET</span><span class="path">/api/menu</span><span class="name">api.menu</span></div>
    <p>Het menu: zaakgegevens, of bestellen open is, en de categorieën met producten in volgorde. Verborgen
        producten en lege categorieën ontbreken; uitverkochte producten staan er wél in (<code>is_sold_out</code>).</p>
<pre><code>curl -s {{ $baseUrl }}/api/menu -H 'Accept: application/json'</code></pre>
    <p>Antwoord <span class="tag">200</span></p>
<pre><code>{
  "business": {
    "name": "Frituur VTI", "address": "Toekomststraat 75, 8790 Waregem", "phone": "056 00 00 00",
    "opening_hours": [{"day": 1, "label": "maandag", "slots": [{"from": "11:30", "to": "14:00"}, {"from": "17:00", "to": "22:00"}]}, ...]
  },
  "ordering": {"is_open": true, "closed_message": null},
  "categories": [
    {"id": 1, "name": "Frieten", "slug": "frieten", "products": [
      {"id": 3, "name": "Grote friet", "slug": "grote-friet", "description": "Voor de echte honger of om te delen",
       "price_cents": 420, "price": "€ 4,20", "image_url": null, "is_sold_out": false}
    ]}
  ]
}</code></pre>

    <div class="route"><span class="method">POST</span><span class="path">/api/orders</span><span class="name">api.orders.store</span></div>
    <p>Plaatst een bestelling. Het totaal wordt server-side berekend uit de producten; prijzen uit het verzoek
        worden genegeerd. Betalen gebeurt bij afhaling.</p>
    <table>
        <tr><th>Veld</th><th>Regels</th></tr>
        <tr><td><code>customer_name</code></td><td>verplicht, 2–40 tekens</td></tr>
        <tr><td><code>customer_phone</code></td><td>verplicht, Belgisch gsm-nummer (<code>04xx xx xx xx</code> of <code>+32 4xx …</code>; spaties, punten en streepjes mogen)</td></tr>
        <tr><td><code>lines</code></td><td>verplicht, 1–30 regels, elk product maximaal één keer</td></tr>
        <tr><td><code>lines.*.product_id</code></td><td>verplicht, bestaand, zichtbaar en niet uitverkocht</td></tr>
        <tr><td><code>lines.*.quantity</code></td><td>verplicht, 1–20</td></tr>
    </table>
<pre><code>curl -s -X POST {{ $baseUrl }}/api/orders \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"customer_name": "Jef", "customer_phone": "0470 12 34 56",
       "lines": [{"product_id": 3, "quantity": 2}, {"product_id": 16, "quantity": 1}]}'</code></pre>
    <p>Antwoord <span class="tag">201</span></p>
<pre><code>{
  "order": {
    "id": 42, "number": "VTI-042", "token": "VOORBEELD-TOKEN",
    "status": "nieuw", "status_label": "Ontvangen", "kitchen_label": "Nieuw", "next_status": "bezig", "action_label": "Start",
    "customer_name": "Jef", "customer_phone": "0470 12 34 56",
    "total_cents": 930, "total": "€ 9,30",
    "placed_at": "2026-09-29T18:42:10+02:00", "started_at": null, "ready_at": null, "picked_up_at": null,
    "lines": [
      {"product_id": 3, "product_name": "Grote friet", "quantity": 2, "unit_price_cents": 420, "unit_price": "€ 4,20", "line_total_cents": 840, "line_total": "€ 8,40"},
      {"product_id": 16, "product_name": "Mayonaise", "quantity": 1, "unit_price_cents": 90, "unit_price": "€ 0,90", "line_total_cents": 90, "line_total": "€ 0,90"}
    ]
  },
  "track_url": "{{ $baseUrl }}/bestelling/VOORBEELD-TOKEN"
}</code></pre>
    <p>Fouten, in deze volgorde gecontroleerd:</p>
    <ul>
        <li><span class="tag">422</span> validatie: <code>{"message": "…", "errors": {"customer_phone": ["Vul een geldig Belgisch gsm-nummer in (bv. 0470 12 34 56)."]}}</code></li>
        <li><span class="tag">422</span> bestellen gesloten: <code>{"message": "Bestellen is momenteel gesloten.", "errors": {"ordering": ["Vandaag gesloten"]}}</code></li>
        <li><span class="tag">422</span> product weg of verborgen: <code>{"errors": {"lines.0.product_id": ["Dit product bestaat niet meer."]}}</code>; uitverkocht: <code>["Grote friet is uitverkocht."]</code></li>
        <li><span class="tag">429</span> te veel bestellingen: maximaal {{ $perIp }} per minuut per IP en {{ $perPhone }} per minuut per gsm-nummer; geweigerde pogingen tellen niet mee.
            <code>{"message": "Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.", "retry_after": 42}</code> + header <code>Retry-After</code>.</li>
    </ul>

    <div class="route"><span class="method">GET</span><span class="path">/api/orders/{token}</span><span class="name">api.orders.show</span></div>
    <p>Volgt een bestelling via de geheime <code>token</code> uit het antwoord hierboven (niet het bestelnummer).
        Zelfde <code>order</code>-vorm; de opvolgpagina vraagt dit elke 4 seconden op. Header <code>Cache-Control: no-store</code>.</p>
<pre><code>curl -s {{ $baseUrl }}/api/orders/VOORBEELD-TOKEN -H 'Accept: application/json'</code></pre>
    <p>Antwoord <span class="tag">200</span> <code>{"order": {…}}</code> · <span class="tag">404</span> <code>{"message": "Bestelling niet gevonden."}</code></p>

    <div class="route"><span class="method">GET</span><span class="path">/api/docs</span><span class="name">api.docs</span></div>
    <p>Deze pagina.</p>

    <h2>Personeel (na inloggen)</h2>
    <p>Het keukenscherm gebruikt JSON-routes achter de gewone sessie-login (<code>/login</code>), met CSRF via de
        header <code>X-XSRF-TOKEN</code>. Zonder login: <span class="tag">401</span>
        <code>{"message": "Hier moet je voor inloggen."}</code>. Bestellingen hebben dezelfde <code>order</code>-vorm als hierboven.</p>
    <table>
        <tr><th>Methode</th><th>Pad</th><th>Body</th><th>Doet</th></tr>
        <tr><td>GET</td><td><code>/keuken/bestellingen</code></td><td>—</td><td>Open bestellingen van vandaag, oudste eerst, met <code>server_time</code> en <code>ordering</code>.</td></tr>
        <tr><td>PATCH</td><td><code>/keuken/bestellingen/{id}/status</code></td><td><code>{"status": "bezig"}</code></td><td>Volgende stap: nieuw → bezig → klaar → afgehaald, enkel vooruit. Anders <span class="tag">422</span> met sleutel <code>status</code>.</td></tr>
        <tr><td>GET</td><td><code>/keuken/producten</code></td><td>—</td><td>Alle producten per categorie (ook verborgen) met <code>is_sold_out</code>.</td></tr>
        <tr><td>PATCH</td><td><code>/keuken/producten/{id}/uitverkocht</code></td><td><code>{"is_sold_out": true}</code></td><td>Product uitverkocht aan of uit.</td></tr>
        <tr><td>PATCH</td><td><code>/keuken/bestellen</code></td><td><code>{"is_open": false}</code></td><td>Bestellen open of dicht (sluiten vereist een ingestelde boodschap).</td></tr>
    </table>
<pre><code>curl -s -X PATCH {{ $baseUrl }}/keuken/bestellingen/42/status \
  -b cookies.txt -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -H "X-XSRF-TOKEN: $XSRF" -d '{"status": "bezig"}'</code></pre>

    <h2>Foutcodes</h2>
    <table>
        <tr><th>Status</th><th>Antwoord</th></tr>
        <tr><td>401</td><td><code>{"message": "Hier moet je voor inloggen."}</code></td></tr>
        <tr><td>403</td><td><code>{"message": "Dit mag je niet."}</code></td></tr>
        <tr><td>404</td><td><code>{"message": "Niet gevonden."}</code> (of specifieker, zoals "Bestelling niet gevonden.")</td></tr>
        <tr><td>422</td><td><code>{"message": "…", "errors": {"veld": ["boodschap"]}}</code></td></tr>
        <tr><td>429</td><td><code>{"message": "Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.", "retry_after": 42}</code> + header <code>Retry-After</code></td></tr>
    </table>
</main>
</body>
</html>
