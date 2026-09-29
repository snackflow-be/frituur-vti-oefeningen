import { expect, type Browser, type Page } from '@playwright/test';

/**
 * Gedeeld door de e2e-tests (draaien op staging na elke uitrol, zie CLAUDE.md).
 * Personeel-login: E2E_STAFF_EMAIL en E2E_STAFF_PASSWORD (op staging: staging-admin@snackflow.be +
 * STAGING_SEED_PASSWORD, nooit in Git). Ontbreken ze, dan worden de personeelsstappen overgeslagen.
 */

/** Gsm-formaat (390 × 844, aanraking) voor alle klant- en personeelspagina's. */
export const gsm = {
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
};

export const staff = {
    email: process.env.E2E_STAFF_EMAIL ?? '',
    password: process.env.E2E_STAFF_PASSWORD ?? '',
};

export const staffAvailable = staff.email !== '' && staff.password !== '';

export const skipReason =
    'E2E_STAFF_EMAIL en E2E_STAFF_PASSWORD ontbreken: personeelsstappen overgeslagen.';

/** Verzamelt JS-fouten en 5xx-antwoorden van de eigen site tijdens de test. */
export function volgFouten(page: Page, baseURL: string): string[] {
    const fouten: string[] = [];
    const origin = new URL(baseURL).origin;

    page.on('pageerror', (e) => fouten.push(`JS-fout: ${e.message}`));
    page.on('response', (r) => {
        const u = new URL(r.url());

        if (r.status() >= 500 && u.origin === origin) {
            fouten.push(`HTTP ${r.status()} ${u.pathname}`);
        }
    });

    return fouten;
}

/** Nieuwe browsercontext voor personeel, ingelogd via de Fortify-loginpagina; landt op /keuken. */
export async function loginAlsPersoneel(browser: Browser, baseURL: string) {
    const context = await browser.newContext({ ...gsm, baseURL });
    const page = await context.newPage();

    await page.goto('/login', { waitUntil: 'networkidle' });
    await page.getByLabel('E-mailadres').fill(staff.email);
    await page.getByLabel('Wachtwoord').fill(staff.password);
    await page.getByRole('button', { name: 'Inloggen' }).click();
    await expect(page).toHaveURL(/\/keuken$/, { timeout: 20_000 });

    return { context, page };
}

/** Uniek Belgisch gsm-nummer per run: de rem is 3 bestellingen per minuut per nummer. */
export function gsmNummer(): string {
    const digits = String(Date.now() % 10_000_000).padStart(7, '0');

    return `047${digits}`;
}
