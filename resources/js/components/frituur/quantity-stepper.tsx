import { Minus, Plus, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    value: number;
    onChange: (next: number) => void;
    min?: number;
    max?: number;
    /** Naam van het product voor de aria-labels. */
    label?: string;
    size?: 'md' | 'lg';
    disabled?: boolean;
    className?: string;
};

/**
 * Hoeveelheidknop: [−] aantal [+]. Knoppen 48 px. Onder `min` wordt "−" een prullenbak
 * (verwijderen); bij `max` staat "+" uit.
 */
export function QuantityStepper({
    value,
    onChange,
    min = 0,
    max = 20,
    label = 'aantal',
    size = 'md',
    disabled = false,
    className,
}: Props) {
    const atMin = value <= min;
    const atMax = value >= max;
    const btn = cn(
        'flex items-center justify-center bg-card text-foreground transition-colors hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-40',
        size === 'lg' ? 'size-14 [&_svg]:size-7' : 'size-12 [&_svg]:size-6',
    );

    return (
        <div
            className={cn(
                'inline-flex items-stretch overflow-hidden rounded-lg border-2 border-border',
                className,
            )}
            role="group"
            aria-label={`Aantal ${label}`}
        >
            <button
                type="button"
                className={btn}
                onClick={() => onChange(Math.max(min - 1, value - 1))}
                disabled={disabled || (min > 0 && atMin)}
                aria-label={
                    value <= 1 && min === 0
                        ? `${label} verwijderen`
                        : `Eén ${label} minder`
                }
            >
                {value <= 1 && min === 0 ? (
                    <Trash2 aria-hidden />
                ) : (
                    <Minus aria-hidden />
                )}
            </button>
            <output
                className={cn(
                    'flex items-center justify-center border-x-2 border-border bg-background font-display font-extrabold tabular-nums',
                    size === 'lg' ? 'min-w-16 text-2xl' : 'min-w-12 text-xl',
                )}
                aria-live="polite"
            >
                {value}
            </output>
            <button
                type="button"
                className={btn}
                onClick={() => onChange(Math.min(max, value + 1))}
                disabled={disabled || atMax}
                aria-label={`Eén ${label} meer`}
            >
                <Plus aria-hidden />
            </button>
        </div>
    );
}
