import { Check } from 'lucide-react';
import { STATUS_META, STATUS_ORDER } from '@/components/frituur/status-badge';
import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types/frituur';

type Props = {
    current: OrderStatus;
    className?: string;
};

/**
 * Vier statusstappen voor de opvolgpagina: Ontvangen → In de maak → Klaar → Afgehaald.
 * Gedaan = accent met vinkje, huidige = accent met pulserende ring, later = omlijnd.
 */
export function StatusSteps({ current, className }: Props) {
    const currentIndex = STATUS_ORDER.indexOf(current);

    return (
        <ol
            className={cn('grid grid-cols-4 gap-2', className)}
            aria-label="Status van je bestelling"
        >
            {STATUS_ORDER.map((status, index) => {
                const done = index < currentIndex;
                const active = index === currentIndex;

                return (
                    <li
                        key={status}
                        className="flex flex-col items-center gap-2 text-center"
                        aria-current={active ? 'step' : undefined}
                    >
                        <span
                            className={cn(
                                'flex size-12 items-center justify-center rounded-lg border-2 font-display text-xl font-extrabold tabular-nums transition-colors sm:size-14',
                                done &&
                                    'border-primary bg-primary text-primary-foreground',
                                active &&
                                    'border-primary bg-primary text-primary-foreground motion-safe:animate-pulse-ring',
                                !done &&
                                    !active &&
                                    'border-border bg-card text-muted-foreground',
                            )}
                        >
                            {done ? (
                                <Check
                                    className="size-7"
                                    strokeWidth={3}
                                    aria-hidden
                                />
                            ) : (
                                index + 1
                            )}
                        </span>
                        <span
                            className={cn(
                                'text-sm leading-tight font-bold sm:text-base',
                                active
                                    ? 'text-foreground'
                                    : done
                                      ? 'text-foreground/80'
                                      : 'text-muted-foreground',
                            )}
                        >
                            {STATUS_META[status].label}
                        </span>
                        {/* Verbindingslijn (onder de blokjes, via een dunne balk) */}
                        <span
                            aria-hidden
                            className={cn(
                                'h-1 w-full rounded-full',
                                done || active ? 'bg-primary' : 'bg-border',
                            )}
                        />
                    </li>
                );
            })}
        </ol>
    );
}
