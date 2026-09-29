import { useCallback, useEffect, useRef, useState } from 'react';
import { ApiError, apiGet } from '@/lib/api';
import type { Order } from '@/types/frituur';

/**
 * Elke 10 s: een klas op één wifi-IP deelt 600 verzoeken per minuut (02-architect A-3);
 * bij 4 s zou een klas van 30 alleen al met opvolgen aan 450/min zitten.
 */
export const TRACKING_INTERVAL_MS = 10_000;

type TrackingState = {
    order: Order | null;
    loading: boolean;
    /** HTTP-status van de laatste fout (404 = onbekende bestelling), 0 = geen verbinding. */
    errorStatus: number | null;
    errorMessage: string | null;
};

/**
 * Haalt `GET /api/orders/{token}` op. Met `poll` herhaalt ze elke 10 s, stopt bij
 * "afgehaald" en pauzeert zolang het tabblad verborgen is (bij terugkeer meteen opnieuw).
 */
export function useOrderTracking(
    token: string,
    options: { poll?: boolean } = {},
) {
    const poll = options.poll ?? false;
    const [state, setState] = useState<TrackingState>({
        order: null,
        loading: true,
        errorStatus: null,
        errorMessage: null,
    });
    const abortRef = useRef<AbortController | null>(null);
    const finishedRef = useRef(false);

    const load = useCallback(async () => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        try {
            const { order } = await apiGet<{ order: Order }>(
                `/api/orders/${encodeURIComponent(token)}`,
                controller.signal,
            );

            finishedRef.current = order.status === 'afgehaald';
            setState({
                order,
                loading: false,
                errorStatus: null,
                errorMessage: null,
            });
        } catch (error) {
            if (controller.signal.aborted) {
                return;
            }

            const status = error instanceof ApiError ? error.status : 0;
            const message =
                error instanceof ApiError
                    ? error.message
                    : 'De bestelling kon niet geladen worden.';

            // Bij een 404 is de bestelling echt weg; anders blijft de laatste stand staan.
            setState((current) => ({
                order: status === 404 ? null : current.order,
                loading: false,
                errorStatus: status,
                errorMessage: message,
            }));

            if (status === 404) {
                finishedRef.current = true;
            }
        }
    }, [token]);

    useEffect(() => {
        finishedRef.current = false;
        void load();

        if (!poll) {
            return () => abortRef.current?.abort();
        }

        const tick = () => {
            if (
                !finishedRef.current &&
                document.visibilityState === 'visible'
            ) {
                void load();
            }
        };

        const timer = window.setInterval(tick, TRACKING_INTERVAL_MS);
        document.addEventListener('visibilitychange', tick);

        return () => {
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', tick);
            abortRef.current?.abort();
        };
    }, [load, poll]);

    return { ...state, reload: load };
}
