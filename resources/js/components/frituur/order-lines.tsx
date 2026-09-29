import { Price } from '@/components/frituur/price';
import { cn } from '@/lib/utils';
import type { Order } from '@/types/frituur';

type Props = {
    order: Order;
    className?: string;
};

/**
 * Overzicht van een geplaatste bestelling (bevestiging en opvolgen): lijnen met aantal,
 * naam en lijntotaal, daaronder het totaal van de API. Zelfde kaartstijl als de rest.
 */
export function OrderLines({ order, className }: Props) {
    return (
        <div
            className={cn(
                'overflow-hidden rounded-lg border border-border bg-card',
                className,
            )}
        >
            <ul className="divide-y divide-border">
                {order.lines.map((line, index) => (
                    <li
                        key={`${line.product_name}-${index}`}
                        className="flex items-center gap-4 px-4 py-3 text-lg"
                    >
                        <span className="w-10 shrink-0 font-display text-xl font-extrabold text-primary tabular-nums">
                            {line.quantity}×
                        </span>
                        <span className="flex-1 font-medium">
                            {line.product_name}
                        </span>
                        <Price cents={line.line_total_cents} size="md" />
                    </li>
                ))}
            </ul>
            <div className="flex items-center justify-between border-t-2 border-border bg-background px-4 py-4">
                <span className="text-lg font-bold">Totaal</span>
                <Price
                    cents={order.total_cents}
                    size="xl"
                    className="shrink-0 whitespace-nowrap"
                />
            </div>
        </div>
    );
}
