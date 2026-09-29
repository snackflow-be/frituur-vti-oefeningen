import { useCallback, useEffect, useRef, useState } from 'react';
import { ApiError, apiGet } from '@/lib/api';
import type { Menu, Product } from '@/types/frituur';

const MENU_URL = '/api/menu';
const REFRESH_INTERVAL_MS = 30_000;

type MenuState = {
    menu: Menu | null;
    loading: boolean;
    error: string | null;
};

/**
 * Haalt `GET /api/menu` op, herhaalt elke 30 s en telkens als het tabblad weer zichtbaar wordt
 * (02-architect A-8). Bij een herhaling blijft het vorige menu staan; alleen de eerste keer
 * toont de pagina een skeleton.
 */
export function useMenu(options: { poll?: boolean } = {}) {
    const poll = options.poll ?? true;
    const [state, setState] = useState<MenuState>({
        menu: null,
        loading: true,
        error: null,
    });
    const abortRef = useRef<AbortController | null>(null);

    const load = useCallback(async () => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        try {
            const menu = await apiGet<Menu>(MENU_URL, controller.signal);

            setState({ menu, loading: false, error: null });
        } catch (error) {
            if (controller.signal.aborted) {
                return;
            }

            setState((current) => ({
                // Vorig menu behouden; een mislukte herhaling mag niets wegnemen.
                menu: current.menu,
                loading: false,
                error:
                    error instanceof ApiError
                        ? error.message
                        : 'Het menu kon niet geladen worden.',
            }));
        }
    }, []);

    useEffect(() => {
        void load();

        if (!poll) {
            return () => abortRef.current?.abort();
        }

        const timer = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                void load();
            }
        }, REFRESH_INTERVAL_MS);

        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                void load();
            }
        };

        document.addEventListener('visibilitychange', onVisible);

        return () => {
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', onVisible);
            abortRef.current?.abort();
        };
    }, [load, poll]);

    return { ...state, reload: load };
}

/** Product + categorie-slug, opgezocht op id in het menu. */
export type MenuEntry = { product: Product; categorySlug: string };

export function indexMenu(menu: Menu | null): Map<number, MenuEntry> {
    const map = new Map<number, MenuEntry>();

    if (!menu) {
        return map;
    }

    for (const category of menu.categories) {
        for (const product of category.products) {
            map.set(product.id, { product, categorySlug: category.slug });
        }
    }

    return map;
}
