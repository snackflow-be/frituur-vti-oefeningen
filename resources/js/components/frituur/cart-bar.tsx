import { Link } from '@inertiajs/react';
import { ArrowRight, ShoppingBag } from 'lucide-react';
import { Price } from '@/components/frituur/price';
import { cn } from '@/lib/utils';

type Props = {
    count: number;
    totalCents: number;
    href?: string;
    label?: string;
    /** Bestellen gesloten: balk grijs en niet klikbaar. */
    disabled?: boolean;
    className?: string;
};

/**
 * Vaste mandje-balk onderaan. Verschijnt (schuift op) zodra `count > 0`.
 * De layout houdt onderaan al ruimte vrij, dus niets verspringt.
 */
export function CartBar({
    count,
    totalCents,
    href = '/bestellen',
    label = 'Bekijk mandje',
    disabled = false,
    className,
}: Props) {
    if (count <= 0) {
        return null;
    }

    const inner = (
        <>
            <span className="flex items-center gap-3">
                <span className="relative flex size-10 items-center justify-center rounded-lg bg-primary-foreground/15">
                    <ShoppingBag className="size-6" aria-hidden />
                    <span className="absolute -top-2 -right-2 flex h-6 min-w-6 items-center justify-center rounded-lg bg-background px-1.5 text-sm font-bold text-foreground tabular-nums">
                        {count}
                    </span>
                </span>
                <span className="text-lg font-bold">{label}</span>
            </span>
            <span className="flex items-center gap-2">
                <Price cents={totalCents} size="lg" />
                <ArrowRight className="size-6" aria-hidden />
            </span>
        </>
    );

    return (
        <div
            className={cn(
                'fixed inset-x-0 bottom-0 z-40 px-4 pb-[calc(1rem+env(safe-area-inset-bottom))] motion-safe:animate-slide-up',
                className,
            )}
        >
            {disabled ? (
                <div
                    aria-disabled="true"
                    className="mx-auto flex h-16 max-w-5xl items-center justify-between rounded-lg border-2 border-border bg-card px-4 text-muted-foreground"
                >
                    {inner}
                </div>
            ) : (
                <Link
                    href={href}
                    aria-label={`${label}: ${count} stuks`}
                    className="mx-auto flex h-16 max-w-5xl items-center justify-between rounded-lg bg-primary px-4 text-primary-foreground transition-colors hover:bg-primary-hover"
                >
                    {inner}
                </Link>
            )}
        </div>
    );
}
