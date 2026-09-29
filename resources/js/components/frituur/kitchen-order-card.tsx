import { Phone } from 'lucide-react';
import { BigButton } from '@/components/frituur/big-button';
import { STATUS_META } from '@/components/frituur/status-badge';
import { cn } from '@/lib/utils';
import type { Order, OrderStatus } from '@/types/frituur';

/** Minuten in nieuw/bezig waarna de kaart waarschuwend kleurt. */
export const KITCHEN_WARN_AFTER_MS = 15 * 60 * 1000;

/** Verstreken tijd als "mm:ss" (na een uur "h:mm:ss"). */
export function formatElapsed(ms: number): string {
    const total = Math.max(0, Math.floor(ms / 1000));
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    const mm = minutes.toString().padStart(2, '0');
    const ss = seconds.toString().padStart(2, '0');

    return hours > 0 ? `${hours}:${mm}:${ss}` : `${mm}:${ss}`;
}

type Props = {
    order: Order;
    /** "Nu" in ms, gecorrigeerd met de servertijd; de pagina tikt elke seconde. */
    now: number;
    onAdvance?: (order: Order, next: OrderStatus) => void;
    /** Verzoek loopt: knop uit met spinner. */
    pending?: boolean;
    /** Net binnengekomen: korte flits in accent. */
    isNew?: boolean;
    className?: string;
};

/**
 * Bestelkaart voor het keukenscherm (tv aan de muur): bestelnummer reusachtig, timer sinds
 * binnenkomst, lijnen groot, één grote knop naar de volgende stap. Na 15 min in nieuw/bezig
 * krijgt de kaart een accentrand en een waarschuwing.
 */
export function KitchenOrderCard({
    order,
    now,
    onAdvance,
    pending = false,
    isNew = false,
    className,
}: Props) {
    const meta = STATUS_META[order.status];
    const elapsed = now - new Date(order.placed_at).getTime();
    const late =
        (order.status === 'nieuw' || order.status === 'bezig') &&
        elapsed > KITCHEN_WARN_AFTER_MS;

    return (
        <article
            className={cn(
                'flex flex-col overflow-hidden rounded-lg border-4 bg-card motion-safe:animate-fade-up',
                late ? 'border-primary' : meta.border,
                isNew && 'motion-safe:animate-flash',
                className,
            )}
            aria-label={`Bestelling ${order.number}, ${meta.kitchenLabel}`}
        >
            <header className="flex items-start justify-between gap-4 p-5 pb-3">
                <div>
                    <p className="font-display text-[3.25rem] leading-none font-extrabold tracking-tight tabular-nums xl:text-[4rem]">
                        {order.number}
                    </p>
                    <p className="mt-2 text-2xl font-bold">
                        {order.customer_name}
                    </p>
                    <p className="mt-1 flex items-center gap-2 text-lg text-muted-foreground tabular-nums">
                        <Phone className="size-5" aria-hidden />
                        {order.customer_phone}
                    </p>
                </div>
                <div className="text-right">
                    <time
                        dateTime={order.placed_at}
                        className={cn(
                            'block font-display text-4xl leading-none font-extrabold tabular-nums',
                            late ? 'text-primary' : 'text-foreground',
                        )}
                        aria-label="Tijd sinds bestellen"
                    >
                        {formatElapsed(elapsed)}
                    </time>
                    {late && (
                        <p className="mt-2 font-display text-base font-extrabold tracking-wide text-primary uppercase">
                            Al 15+ min
                        </p>
                    )}
                </div>
            </header>

            <ul className="flex flex-col divide-y divide-border border-y border-border">
                {order.lines.map((line, index) => (
                    <li
                        key={`${line.product_name}-${index}`}
                        className="flex items-baseline gap-4 px-5 py-3 text-2xl"
                    >
                        <span className="min-w-12 font-display font-extrabold text-primary tabular-nums">
                            {line.quantity}×
                        </span>
                        <span className="font-bold">{line.product_name}</span>
                    </li>
                ))}
            </ul>

            <div className="p-5">
                {meta.next && meta.action ? (
                    <BigButton
                        size="xl"
                        block
                        loading={pending}
                        onClick={() =>
                            onAdvance?.(order, meta.next as OrderStatus)
                        }
                    >
                        {meta.action}
                    </BigButton>
                ) : (
                    <p className="text-center text-xl font-bold text-muted-foreground">
                        Afgehaald
                    </p>
                )}
            </div>
        </article>
    );
}
