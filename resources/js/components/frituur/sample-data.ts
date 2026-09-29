/**
 * Voorbeelddata voor de componenten (zelfde vorm als de API, 02-architect §3.1).
 * Handig om pagina's te bouwen voor de backend klaar is, en voor visuele controle.
 * Nooit in productie tonen: de pagina's halen echte data via lib/api.ts.
 */
import type { Business, Menu, Order } from '@/types/frituur';

export const sampleBusiness: Business = {
    name: 'Frituur VTI',
    address: 'Toekomststraat 75, 8790 Waregem',
    phone: '056 00 00 00',
    opening_hours: [
        {
            day: 1,
            label: 'maandag',
            slots: [
                { from: '11:30', to: '14:00' },
                { from: '17:00', to: '22:00' },
            ],
        },
        {
            day: 2,
            label: 'dinsdag',
            slots: [
                { from: '11:30', to: '14:00' },
                { from: '17:00', to: '22:00' },
            ],
        },
        { day: 3, label: 'woensdag', slots: [] },
        {
            day: 4,
            label: 'donderdag',
            slots: [
                { from: '11:30', to: '14:00' },
                { from: '17:00', to: '22:00' },
            ],
        },
        {
            day: 5,
            label: 'vrijdag',
            slots: [
                { from: '11:30', to: '14:00' },
                { from: '17:00', to: '22:30' },
            ],
        },
        { day: 6, label: 'zaterdag', slots: [{ from: '17:00', to: '22:30' }] },
        { day: 7, label: 'zondag', slots: [{ from: '17:00', to: '22:00' }] },
    ],
};

export const sampleMenu: Menu = {
    business: sampleBusiness,
    ordering: { is_open: true, closed_message: null },
    categories: [
        {
            id: 1,
            name: 'Frieten',
            slug: 'frieten',
            products: [
                {
                    id: 1,
                    name: 'Kleine friet',
                    slug: 'kleine-friet',
                    description:
                        'Krokant gebakken in ossenvet, net genoeg voor één',
                    price_cents: 300,
                    price: '€ 3,00',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 2,
                    name: 'Medium friet',
                    slug: 'medium-friet',
                    description:
                        'Onze klassieker: goudbruin, warm en goed gevuld',
                    price_cents: 350,
                    price: '€ 3,50',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 3,
                    name: 'Grote friet',
                    slug: 'grote-friet',
                    description: 'Voor de echte honger of om te delen',
                    price_cents: 420,
                    price: '€ 4,20',
                    image_url: null,
                    is_sold_out: false,
                },
            ],
        },
        {
            id: 2,
            name: 'Snacks',
            slug: 'snacks',
            products: [
                {
                    id: 4,
                    name: 'Frikandel',
                    slug: 'frikandel',
                    description:
                        'De klassieker, krokant vanbuiten, sappig vanbinnen',
                    price_cents: 220,
                    price: '€ 2,20',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 7,
                    name: 'Bicky Burger',
                    slug: 'bicky-burger',
                    description: 'Krokant broodje, Bicky-saus, ajuin en augurk',
                    price_cents: 400,
                    price: '€ 4,00',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 8,
                    name: 'Kaaskroket',
                    slug: 'kaaskroket',
                    description: 'Romige kaas in een goudbruin krokant korstje',
                    price_cents: 250,
                    price: '€ 2,50',
                    image_url: null,
                    is_sold_out: true,
                },
            ],
        },
        {
            id: 3,
            name: 'Sauzen',
            slug: 'sauzen',
            products: [
                {
                    id: 16,
                    name: 'Mayonaise',
                    slug: 'mayonaise',
                    description: 'Echte Belgische frietmayonaise, vol en romig',
                    price_cents: 90,
                    price: '€ 0,90',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 18,
                    name: 'Andalouse',
                    slug: 'andalouse',
                    description: 'Licht pikant met paprika en fijne ajuin',
                    price_cents: 100,
                    price: '€ 1,00',
                    image_url: null,
                    is_sold_out: false,
                },
            ],
        },
        {
            id: 4,
            name: 'Dranken',
            slug: 'dranken',
            products: [
                {
                    id: 22,
                    name: 'Coca-Cola',
                    slug: 'coca-cola',
                    description: 'Ijskoud blikje 33 cl',
                    price_cents: 220,
                    price: '€ 2,20',
                    image_url: null,
                    is_sold_out: false,
                },
                {
                    id: 27,
                    name: 'Jupiler',
                    slug: 'jupiler',
                    description: 'Belgische pils, ijskoud, 33 cl',
                    price_cents: 250,
                    price: '€ 2,50',
                    image_url: null,
                    is_sold_out: false,
                },
            ],
        },
    ],
};

export const sampleOrder: Order = {
    id: 42,
    number: 'VTI-042',
    token: 'VOORBEELD-TOKEN',
    status: 'bezig',
    status_label: 'In de maak',
    customer_name: 'Jef',
    customer_phone: '0470 12 34 56',
    total_cents: 930,
    total: '€ 9,30',
    placed_at: new Date(Date.now() - 4 * 60 * 1000).toISOString(),
    started_at: new Date(Date.now() - 2 * 60 * 1000).toISOString(),
    ready_at: null,
    picked_up_at: null,
    lines: [
        {
            product_name: 'Grote friet',
            quantity: 2,
            unit_price_cents: 420,
            unit_price: '€ 4,20',
            line_total_cents: 840,
            line_total: '€ 8,40',
        },
        {
            product_name: 'Mayonaise',
            quantity: 1,
            unit_price_cents: 90,
            unit_price: '€ 0,90',
            line_total_cents: 90,
            line_total: '€ 0,90',
        },
    ],
};
