import { Head, Link } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    children: ReactNode;
    /** Titel van de pagina (zonder de naam van de zaak). */
    title?: string;
    /** Naam van de zaak in de header; uit `menu.business.name`. */
    businessName?: string;
    /** Aantal stuks in het mandje voor de indicator rechtsboven. */
    cartCount?: number;
    /** Link achter naam en mandje-indicator. */
    homeHref?: string;
    cartHref?: string;
    /** Ruimte onderaan vrijhouden voor de vaste mandje-balk (geen layout-shift). */
    reserveCartBar?: boolean;
    /** Footer-slot (bv. <FooterInfo />). */
    footer?: ReactNode;
    className?: string;
};

/**
 * Klantlayout: compacte header met naam en mandje-indicator, hoofdinhoud, footer.
 * Pagina's wikkelen zichzelf hierin (app.tsx geeft `null` als layout).
 */
export default function FrituurLayout({
    children,
    title,
    businessName = 'Frituur VTI',
    cartCount = 0,
    homeHref = '/',
    cartHref = '/bestellen',
    reserveCartBar = true,
    footer,
    className,
}: Props) {
    return (
        <div className="flex min-h-dvh flex-col bg-background text-foreground">
            <Head title={title} />

            <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur-sm">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4">
                    <Link
                        href={homeHref}
                        className="flex min-h-11 items-center font-display text-2xl leading-none font-extrabold tracking-tight uppercase"
                    >
                        <span className="text-primary">Frituur</span>
                        <span className="ml-1.5">
                            {businessName.replace(/^frituur\s+/i, '')}
                        </span>
                    </Link>

                    <Link
                        href={cartHref}
                        aria-label={
                            cartCount > 0
                                ? `Mandje, ${cartCount} stuks`
                                : 'Mandje, leeg'
                        }
                        className={cn(
                            'relative flex size-11 items-center justify-center rounded-lg border border-border bg-card transition-colors hover:border-primary',
                            cartCount > 0 && 'border-primary',
                        )}
                    >
                        <ShoppingBag className="size-6" aria-hidden />
                        {cartCount > 0 && (
                            <span className="absolute -top-2 -right-2 flex h-6 min-w-6 items-center justify-center rounded-lg bg-primary px-1.5 text-sm font-bold text-primary-foreground tabular-nums">
                                {cartCount}
                            </span>
                        )}
                    </Link>
                </div>
            </header>

            <main
                className={cn(
                    'mx-auto w-full max-w-5xl flex-1 px-4',
                    reserveCartBar &&
                        'pb-[calc(6rem+env(safe-area-inset-bottom))]',
                    className,
                )}
            >
                {children}
            </main>

            {footer && (
                <footer className="border-t border-border bg-card">
                    <div className="mx-auto max-w-5xl px-4 py-10">{footer}</div>
                </footer>
            )}
        </div>
    );
}
