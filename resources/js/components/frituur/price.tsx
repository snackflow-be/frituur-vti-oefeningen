import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';

type Props = {
    cents: number;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
};

const sizes = {
    sm: 'text-base',
    md: 'text-xl',
    lg: 'text-2xl',
    xl: 'text-4xl',
};

/** Prijs in de huisstijl: vet, groot, tabulaire cijfers. `<Price cents={350} />` → "€ 3,50". */
export function Price({ cents, size = 'md', className }: Props) {
    return (
        <span
            className={cn(
                'font-display font-extrabold tracking-tight tabular-nums',
                sizes[size],
                className,
            )}
        >
            {formatCents(cents)}
        </span>
    );
}
