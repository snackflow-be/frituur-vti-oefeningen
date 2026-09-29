/**
 * Centen → "€ 3,50" (Belgische notatie, spatie na het euroteken, komma als
 * decimaalteken, punt als duizendtal). Negatief wordt "-€ 1,00".
 */
export function formatCents(cents: number): string {
    const sign = cents < 0 ? '-' : '';
    const abs = Math.abs(Math.round(cents));
    const euros = Math.floor(abs / 100);
    const rest = abs % 100;

    const eurosText = euros.toLocaleString('nl-BE');

    return `${sign}€ ${eurosText},${rest.toString().padStart(2, '0')}`;
}

/**
 * "3,50" of "3.50" → 350; ongeldig → null. Spiegel van Money::parseEuro (PHP).
 */
export function parseEuro(input: string): number | null {
    const cleaned = input
        .trim()
        .replace(/\s|€/g, '')
        .replace(',', '.');

    if (!/^\d+(\.\d{1,2})?$/.test(cleaned)) {
        return null;
    }

    return Math.round(Number.parseFloat(cleaned) * 100);
}
