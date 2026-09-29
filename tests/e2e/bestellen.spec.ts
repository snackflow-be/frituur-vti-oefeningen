import { expect, test } from '@playwright/test';
import {
    gsm,
    gsmNummer,
    loginAlsPersoneel,
    skipReason,
    staffAvailable,
    volgFouten,
} from './helpers';

// Kernflow op de gsm: menu → + → mandje → naam en gsm → bevestiging met bestelnummer →
// keuken toont de bestelling → keuken zet ze op klaar (klant ziet het) en afgehaald (bord leeg).
// Staging: geseed menu (MenuSeeder), bestellen open; de bestelling wordt op het einde afgehaald.

test.use(gsm);

const PRODUCT = 'Kleine friet';
const SAUS = 'Mayonaise';

test('klant bestelt op de gsm en de keuken ziet de bestelling', async ({
    page,
    browser,
    baseURL,
}) => {
    const fouten = volgFouten(page, baseURL!);

    // Menu
    await page.goto('/', { waitUntil: 'networkidle' });
    await expect(page.getByRole('button', { name: 'Bestel nu' })).toBeVisible();

    const friet = page.getByRole('article', { name: PRODUCT });
    await expect(friet).toBeVisible();
    await friet.getByRole('button', { name: `${PRODUCT} toevoegen` }).click();
    await friet.getByRole('button', { name: `Eén ${PRODUCT} meer` }).click();

    const saus = page.getByRole('article', { name: SAUS });
    await saus.getByRole('button', { name: `${SAUS} toevoegen` }).click();

    // Vaste mandje-balk onderaan: 3 stuks
    const balk = page.getByRole('link', { name: 'Bekijk mandje: 3 stuks' });
    await expect(balk).toBeVisible();
    await balk.click();

    // Mandje
    await expect(page).toHaveURL(/\/bestellen$/);
    await expect(
        page.getByRole('heading', { name: 'Je mandje' }),
    ).toBeVisible();
    await expect(
        page.getByRole('group', { name: `Aantal ${PRODUCT}` }),
    ).toContainText('2');

    // Lege verzending: fouten onder de velden, geen bestelling
    await page.getByRole('button', { name: 'Bestelling doorsturen' }).click();
    await expect(page.getByRole('alert').first()).toBeVisible();

    const nummer = gsmNummer();
    await page.getByLabel('Je naam').fill('E2E Test');
    await page.getByLabel('Je gsm-nummer').fill(nummer);
    await page.getByRole('button', { name: 'Bestelling doorsturen' }).click();

    // Bevestiging met groot bestelnummer
    await expect(page).toHaveURL(/\/bevestiging\/[A-Za-z0-9]+$/, {
        timeout: 20_000,
    });
    const token = page.url().split('/').pop()!;
    const nummerElement = page.locator('p[aria-label^="Bestelnummer "]');
    await expect(nummerElement).toBeVisible();
    const bestelnummer = (await nummerElement.textContent())!.trim();
    expect(bestelnummer).toMatch(/^VTI-\d{3,}$/);
    await expect(page.getByText('Toon dit nummer aan de toog.')).toBeVisible();
    await expect(page.getByText(PRODUCT)).toBeVisible();

    // Opvolgen
    await page.getByRole('link', { name: 'Volg je bestelling' }).click();
    await expect(page).toHaveURL(new RegExp(`/bestelling/${token}$`));
    await expect(page.getByText('We hebben je bestelling')).toBeVisible();

    expect(fouten, 'fouten aan klantkant').toEqual([]);

    // Keuken (personeel)
    test.skip(!staffAvailable, skipReason);

    const keuken = await loginAlsPersoneel(browser, baseURL!);
    const keukenFouten = volgFouten(keuken.page, baseURL!);

    try {
        const kaart = keuken.page.getByRole('article', {
            name: `Bestelling ${bestelnummer}, Nieuw`,
        });
        await expect(kaart).toBeVisible({ timeout: 15_000 });
        await expect(kaart).toContainText('E2E Test');
        await expect(kaart).toContainText(new RegExp(`2×\\s*${PRODUCT}`));
        await expect(kaart).toContainText(new RegExp(`1×\\s*${SAUS}`));

        // Start → Bezig
        await kaart.getByRole('button', { name: 'Start' }).click();
        const bezig = keuken.page.getByRole('article', {
            name: `Bestelling ${bestelnummer}, Bezig`,
        });
        await expect(bezig).toBeVisible();

        // Klaar → de klant ziet het binnen de polling van 10 s
        await bezig.getByRole('button', { name: 'Klaar' }).click();
        const klaar = keuken.page.getByRole('article', {
            name: `Bestelling ${bestelnummer}, Klaar`,
        });
        await expect(klaar).toBeVisible();
        await expect(
            page.getByText('Je bestelling ligt klaar aan de toog'),
        ).toBeVisible({
            timeout: 20_000,
        });

        // Afgehaald → weg van het bord, klant ziet "Afgehaald"
        await klaar.getByRole('button', { name: 'Afgehaald' }).click();
        await expect(
            keuken.page.getByRole('article', {
                name: new RegExp(`Bestelling ${bestelnummer},`),
            }),
        ).toHaveCount(0);
        await expect(page.getByText('Afgehaald. Smakelijk!')).toBeVisible({
            timeout: 20_000,
        });

        expect(keukenFouten, 'fouten aan keukenkant').toEqual([]);
    } finally {
        await keuken.context.close();
    }
});
