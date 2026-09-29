import { Volume2, VolumeX } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    BigButton,
    EmptyState,
    KitchenOrderCard,
    STATUS_META,
} from '@/components/frituur';
import { OrderingToggle } from '@/components/keuken/ordering-toggle';
import { SoldOutSheet } from '@/components/keuken/sold-out-sheet';
import { useKitchenSound } from '@/components/keuken/use-kitchen-sound';
import KeukenLayout from '@/layouts/keuken-layout';
import { ApiError, apiGet, apiPatch } from '@/lib/api';
import { cn } from '@/lib/utils';
import { orders as ordersRoute } from '@/routes/keuken';
import { status as statusRoute } from '@/routes/keuken/orders';
import type { Order, OrderStatus } from '@/types/frituur';
import type { KitchenBoard } from '@/types/staff';

/** Hoe vaak het bord ververst. */
const POLL_MS = 5000;

/** Hoe lang een nieuwe kaart flitst. */
const FLASH_MS = 6000;

const COLUMNS: OrderStatus[] = ['nieuw', 'bezig', 'klaar'];

type Props = {
    initial: KitchenBoard;
};

export default function KeukenIndex({ initial }: Props) {
    const [board, setBoard] = useState<KitchenBoard>(initial);
    const [now, setNow] = useState(() => Date.now());
    const [newIds, setNewIds] = useState<number[]>([]);
    const [pendingId, setPendingId] = useState<number | null>(null);
    const [offline, setOffline] = useState(false);

    const offsetRef = useRef(serverOffset(initial.server_time));
    const knownIdsRef = useRef<Set<number>>(
        new Set(initial.orders.map((order) => order.id)),
    );
    const sound = useKitchenSound();
    const playRef = useRef(sound.play);
    playRef.current = sound.play;

    // Timer: elke seconde, gecorrigeerd met de servertijd van de laatste poll.
    useEffect(() => {
        const timer = window.setInterval(
            () => setNow(Date.now() + offsetRef.current),
            1000,
        );

        return () => window.clearInterval(timer);
    }, []);

    // Polling: elke 5 s en zodra het tabblad weer zichtbaar wordt.
    useEffect(() => {
        let controller: AbortController | null = null;

        const refresh = async () => {
            controller?.abort();
            controller = new AbortController();

            try {
                const data = await apiGet<KitchenBoard>(
                    ordersRoute.url(),
                    controller.signal,
                );

                offsetRef.current = serverOffset(data.server_time);
                setNow(Date.now() + offsetRef.current);
                setOffline(false);

                const fresh = data.orders
                    .map((order) => order.id)
                    .filter((id) => !knownIdsRef.current.has(id));

                data.orders.forEach((order) =>
                    knownIdsRef.current.add(order.id),
                );

                setBoard(data);

                if (fresh.length > 0) {
                    setNewIds((current) => [...current, ...fresh]);
                    playRef.current();
                    window.setTimeout(
                        () =>
                            setNewIds((current) =>
                                current.filter((id) => !fresh.includes(id)),
                            ),
                        FLASH_MS,
                    );
                }
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                if (error instanceof ApiError && error.status === 401) {
                    window.location.reload();

                    return;
                }

                setOffline(true);
            }
        };

        const interval = window.setInterval(() => void refresh(), POLL_MS);
        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                void refresh();
            }
        };

        document.addEventListener('visibilitychange', onVisible);

        return () => {
            window.clearInterval(interval);
            document.removeEventListener('visibilitychange', onVisible);
            controller?.abort();
        };
    }, []);

    const advance = async (order: Order, next: OrderStatus) => {
        setPendingId(order.id);

        try {
            const { order: updated } = await apiPatch<{ order: Order }>(
                statusRoute.url(order.id),
                { status: next },
            );

            setBoard((current) => ({
                ...current,
                orders:
                    updated.status === 'afgehaald'
                        ? current.orders.filter((o) => o.id !== updated.id)
                        : current.orders.map((o) =>
                              o.id === updated.id ? updated : o,
                          ),
            }));
        } catch (error) {
            toast.error(
                error instanceof ApiError
                    ? (error.fieldError('status') ?? error.message)
                    : 'Er ging iets mis. Probeer opnieuw.',
            );
        } finally {
            setPendingId(null);
        }
    };

    const byStatus = (status: OrderStatus) =>
        board.orders.filter((order) => order.status === status);

    return (
        <KeukenLayout
            ordering={board.ordering}
            actions={
                <>
                    <BigButton
                        variant="ghost"
                        size="md"
                        aria-pressed={sound.enabled}
                        onClick={sound.toggle}
                        aria-label={
                            sound.enabled
                                ? 'Geluid uitzetten'
                                : 'Geluid aanzetten'
                        }
                    >
                        {sound.enabled ? (
                            <Volume2 aria-hidden />
                        ) : (
                            <VolumeX aria-hidden />
                        )}
                        {sound.enabled ? 'Geluid aan' : 'Geluid uit'}
                    </BigButton>
                    <SoldOutSheet />
                    <OrderingToggle
                        ordering={board.ordering}
                        onChange={(ordering) =>
                            setBoard((current) => ({ ...current, ordering }))
                        }
                    />
                </>
            }
        >
            {offline && (
                <p
                    role="status"
                    className="mb-4 rounded-lg border-2 border-primary px-4 py-3 text-lg font-bold text-primary"
                >
                    Geen verbinding met de server. We proberen het elke 5
                    seconden opnieuw.
                </p>
            )}

            {board.orders.length === 0 ? (
                <EmptyState
                    size="lg"
                    icon="🍟"
                    title="Geen open bestellingen"
                    text="Nieuwe bestellingen verschijnen hier vanzelf."
                />
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    {COLUMNS.map((status) => {
                        const orders = byStatus(status);
                        const meta = STATUS_META[status];

                        return (
                            <section
                                key={status}
                                aria-label={`${meta.kitchenLabel}, ${orders.length} bestellingen`}
                                className="flex min-w-0 flex-col gap-4"
                            >
                                <header
                                    className={cn(
                                        'flex items-center justify-between rounded-lg px-5 py-3 text-status-ink',
                                        meta.bg,
                                    )}
                                >
                                    <h2 className="font-display text-3xl font-extrabold tracking-wide uppercase">
                                        {meta.kitchenLabel}
                                    </h2>
                                    <span className="font-display text-3xl font-extrabold tabular-nums">
                                        {orders.length}
                                    </span>
                                </header>

                                {orders.length === 0 ? (
                                    <p className="rounded-lg border-2 border-dashed border-border px-5 py-8 text-center text-xl text-muted-foreground">
                                        Niets in{' '}
                                        {meta.kitchenLabel.toLowerCase()}
                                    </p>
                                ) : (
                                    orders.map((order) => (
                                        <KitchenOrderCard
                                            key={order.id}
                                            order={order}
                                            now={now}
                                            isNew={newIds.includes(order.id)}
                                            pending={pendingId === order.id}
                                            onAdvance={(o, next) =>
                                                void advance(o, next)
                                            }
                                        />
                                    ))
                                )}
                            </section>
                        );
                    })}
                </div>
            )}
        </KeukenLayout>
    );
}

function serverOffset(serverTime: string): number {
    const parsed = new Date(serverTime).getTime();

    return Number.isNaN(parsed) ? 0 : parsed - Date.now();
}
