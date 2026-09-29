import { useCallback, useEffect, useState } from 'react';
import type { CartItem } from '@/types/frituur';

/** Sleutel in localStorage (02-architect §4): enkel product_id + aantal, nooit prijzen of namen. */
export const CART_STORAGE_KEY = 'frituur.cart.v1';
export const CART_MAX_QUANTITY = 20;
export const CART_MAX_LINES = 30;

function readStorage(): CartItem[] {
    try {
        const raw = window.localStorage.getItem(CART_STORAGE_KEY);

        if (!raw) {
            return [];
        }

        const parsed: unknown = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .filter(
                (item): item is CartItem =>
                    typeof item === 'object' &&
                    item !== null &&
                    Number.isInteger((item as CartItem).product_id) &&
                    Number.isInteger((item as CartItem).quantity),
            )
            .map((item) => ({
                product_id: item.product_id,
                quantity: Math.min(
                    CART_MAX_QUANTITY,
                    Math.max(1, item.quantity),
                ),
            }))
            .slice(0, CART_MAX_LINES);
    } catch {
        // Private modus, opslag uit of onleesbare inhoud: start met een leeg mandje.
        return [];
    }
}

function writeStorage(items: CartItem[]) {
    try {
        if (items.length === 0) {
            window.localStorage.removeItem(CART_STORAGE_KEY);
        } else {
            window.localStorage.setItem(
                CART_STORAGE_KEY,
                JSON.stringify(items),
            );
        }
    } catch {
        // Opslag niet beschikbaar: het mandje leeft dan enkel in het geheugen.
    }
}

/**
 * Mandje in localStorage: overleeft herladen en volgt wijzigingen uit andere tabbladen.
 * Prijzen en namen komen altijd uit het laatst opgehaalde menu, nooit uit opslag.
 */
export function useCart() {
    // Eerste render altijd leeg (zelfde als de server bij SSR), daarna uit opslag lezen.
    const [items, setItems] = useState<CartItem[]>([]);
    const [hydrated, setHydrated] = useState(false);

    useEffect(() => {
        setItems(readStorage());
        setHydrated(true);
    }, []);

    useEffect(() => {
        if (hydrated) {
            writeStorage(items);
        }
    }, [items, hydrated]);

    useEffect(() => {
        const onStorage = (event: StorageEvent) => {
            if (event.key === CART_STORAGE_KEY || event.key === null) {
                setItems(readStorage());
            }
        };

        window.addEventListener('storage', onStorage);

        return () => window.removeEventListener('storage', onStorage);
    }, []);

    const setQuantity = useCallback((productId: number, quantity: number) => {
        setItems((current) => {
            const next = Math.min(
                CART_MAX_QUANTITY,
                Math.max(0, Math.round(quantity)),
            );
            const exists = current.some(
                (item) => item.product_id === productId,
            );

            if (next <= 0) {
                return current.filter((item) => item.product_id !== productId);
            }

            if (!exists) {
                if (current.length >= CART_MAX_LINES) {
                    return current;
                }

                return [...current, { product_id: productId, quantity: next }];
            }

            return current.map((item) =>
                item.product_id === productId
                    ? { ...item, quantity: next }
                    : item,
            );
        });
    }, []);

    const add = useCallback((productId: number) => {
        setItems((current) => {
            const line = current.find((item) => item.product_id === productId);

            if (!line) {
                if (current.length >= CART_MAX_LINES) {
                    return current;
                }

                return [...current, { product_id: productId, quantity: 1 }];
            }

            if (line.quantity >= CART_MAX_QUANTITY) {
                return current;
            }

            return current.map((item) =>
                item.product_id === productId
                    ? { ...item, quantity: item.quantity + 1 }
                    : item,
            );
        });
    }, []);

    const remove = useCallback((productId: number) => {
        setItems((current) =>
            current.filter((item) => item.product_id !== productId),
        );
    }, []);

    /** Houd enkel de producten die nog in het menu staan (verborgen/verwijderd valt weg). */
    const keepOnly = useCallback((productIds: Set<number>) => {
        setItems((current) => {
            const kept = current.filter((item) =>
                productIds.has(item.product_id),
            );

            return kept.length === current.length ? current : kept;
        });
    }, []);

    const clear = useCallback(() => setItems([]), []);

    const quantityOf = useCallback(
        (productId: number) =>
            items.find((item) => item.product_id === productId)?.quantity ?? 0,
        [items],
    );

    const count = items.reduce((sum, item) => sum + item.quantity, 0);

    return {
        items,
        hydrated,
        count,
        add,
        remove,
        setQuantity,
        keepOnly,
        clear,
        quantityOf,
    };
}
