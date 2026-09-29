import { cn } from '@/lib/utils';
import type { OrderStatus } from '@/types/frituur';

export const STATUS_ORDER: OrderStatus[] = [
    'nieuw',
    'bezig',
    'klaar',
    'afgehaald',
];

export const STATUS_META: Record<
    OrderStatus,
    {
        label: string; // klant
        kitchenLabel: string; // keuken/admin
        action: string | null; // knop naar volgende stap
        next: OrderStatus | null;
        bg: string;
        text: string;
        border: string;
    }
> = {
    nieuw: {
        label: 'Ontvangen',
        kitchenLabel: 'Nieuw',
        action: 'Start',
        next: 'bezig',
        bg: 'bg-status-new',
        text: 'text-status-new',
        border: 'border-status-new',
    },
    bezig: {
        label: 'In de maak',
        kitchenLabel: 'Bezig',
        action: 'Klaar',
        next: 'klaar',
        bg: 'bg-status-busy',
        text: 'text-status-busy',
        border: 'border-status-busy',
    },
    klaar: {
        label: 'Klaar',
        kitchenLabel: 'Klaar',
        action: 'Afgehaald',
        next: 'afgehaald',
        bg: 'bg-status-ready',
        text: 'text-status-ready',
        border: 'border-status-ready',
    },
    afgehaald: {
        label: 'Afgehaald',
        kitchenLabel: 'Afgehaald',
        action: null,
        next: null,
        bg: 'bg-status-done',
        text: 'text-status-done',
        border: 'border-status-done',
    },
};

type Props = {
    status: OrderStatus;
    /** Klantlabel ("Ontvangen") of keukenlabel ("Nieuw"). */
    variant?: 'customer' | 'kitchen';
    size?: 'sm' | 'md' | 'lg';
    className?: string;
};

const sizes = {
    sm: 'h-7 px-2.5 text-xs',
    md: 'h-9 px-3 text-sm',
    lg: 'h-11 px-4 text-base',
};

/** Statusbadge: gevuld vlak in de statuskleur, donkere inkt, hoofdletters. */
export function StatusBadge({
    status,
    variant = 'customer',
    size = 'md',
    className,
}: Props) {
    const meta = STATUS_META[status];

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-lg font-display font-extrabold tracking-wide whitespace-nowrap uppercase',
                meta.bg,
                'text-status-ink',
                sizes[size],
                className,
            )}
        >
            {variant === 'kitchen' ? meta.kitchenLabel : meta.label}
        </span>
    );
}
