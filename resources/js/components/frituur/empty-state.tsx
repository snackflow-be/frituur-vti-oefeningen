import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    /** Groot pictogram/emoji, bv. "🛒" of "🍟". */
    icon?: ReactNode;
    title: string;
    text?: string;
    /** Knop of link, bv. <BigButton asChild><Link href="/">Naar het menu</Link></BigButton>. */
    action?: ReactNode;
    size?: 'md' | 'lg';
    className?: string;
};

/** Lege staat: nooit een leeg zwart scherm. */
export function EmptyState({
    icon = '🍟',
    title,
    text,
    action,
    size = 'md',
    className,
}: Props) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border px-6 text-center motion-safe:animate-fade-in',
                size === 'lg' ? 'min-h-[24rem] py-16' : 'py-12',
                className,
            )}
        >
            <div
                className={cn(
                    'leading-none select-none',
                    size === 'lg' ? 'text-8xl' : 'text-6xl',
                )}
                aria-hidden
            >
                {icon}
            </div>
            <h2
                className={cn(
                    'mt-6',
                    size === 'lg' && 'text-[clamp(2rem,5vw,3.5rem)]',
                )}
            >
                {title}
            </h2>
            {text && (
                <p className="mt-3 max-w-md text-lg text-muted-foreground">
                    {text}
                </p>
            )}
            {action && <div className="mt-8">{action}</div>}
        </div>
    );
}
