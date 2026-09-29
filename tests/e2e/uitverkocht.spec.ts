import { expect, test } from '@playwright/test';
import {
    gsm,
    loginAlsPersoneel,
    skipReason,
    staffAvailable,
    volgFouten,
} from './helpers';

// Admin zet een product uitverkocht → het menu toont het als uitverkocht (geen +knop).
// Daarna wordt de oorspronkelijke stand hersteld, zodat staging bruikbaar blijft voor de demo.

test.use(gsm);

const PRODUCT = 'Lucifer';

test('admin zet een product uitverkocht en het menu toont dat', async ({
    page,
    browser,
    baseURL,
}) => {
    test.skip(!staffAvailable, skipReason);

    const fouten = volgFouten(page, baseURL!);
    const admin = await loginAlsPersoneel(browser, baseURL!);
    const adminFouten = volgFouten(admin.page, baseURL!);

    await admin.page.goto('/admin/producten', { waitUntil: 'networkidle' });
    const schakelaar = admin.page.getByRole('checkbox', {
        name: `${PRODUCT} uitverkocht`,
    });
    await expect(schakelaar).toBeVisible();
    const wasUitverkocht = await schakelaar.isChecked();

    try {
        if (!wasUitverkocht) {
            await schakelaar.click();
        }

        await expect(schakelaar).toBeChecked({ timeout: 15_000 });

        // Klant: het menu toont het product als uitverkocht en zonder +knop.
        await page.goto('/', { waitUntil: 'networkidle' });
        const kaart = page.getByRole('article', { name: PRODUCT });
        await expect(kaart).toBeVisible();
        await expect(kaart).toContainText('Uitverkocht');
        await expect(
            kaart.getByRole('button', { name: `${PRODUCT} toevoegen` }),
        ).toHaveCount(0);
        await expect(kaart.getByText('Op', { exact: true })).toBeVisible();

        // Ook de API zegt het.
        const menu = await page.request.get('/api/menu');
        expect(menu.status()).toBe(200);
        const data = (await menu.json()) as {
            categories: {
                products: { name: string; is_sold_out: boolean }[];
            }[];
        };
        const product = data.categories
            .flatMap((c) => c.products)
            .find((p) => p.name === PRODUCT);
        expect(product?.is_sold_out, `${PRODUCT} in /api/menu`).toBe(true);
    } finally {
        // Herstellen naar de oorspronkelijke stand.
        if (!wasUitverkocht) {
            await admin.page.goto('/admin/producten', {
                waitUntil: 'networkidle',
            });
            const terug = admin.page.getByRole('checkbox', {
                name: `${PRODUCT} uitverkocht`,
            });
            await terug.click();
            await expect(terug).not.toBeChecked({ timeout: 15_000 });
        }

        await admin.context.close();
    }

    if (!wasUitverkocht) {
        await page.goto('/', { waitUntil: 'networkidle' });
        const kaart = page.getByRole('article', { name: PRODUCT });
        await expect(
            kaart.getByRole('button', { name: `${PRODUCT} toevoegen` }),
        ).toBeVisible();
    }

    expect(fouten, 'fouten aan klantkant').toEqual([]);
    expect(adminFouten, 'fouten aan adminkant').toEqual([]);
});
