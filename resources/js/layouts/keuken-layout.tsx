import { Head, Link, router } from '@inertiajs/react';
import { LogOut, SlidersHorizontal } from 'lucide-react';
import type { ReactNode } from 'react';
import { BigButton, ClosedBanner } from '@/components/frituur';
import { logout } from '@/routes';
import { index as ordersIndex } from '@/routes/admin/orders';
import type { Ordering } from '@/types/frituur';

type Props = {
    children: ReactNode;
    ordering: Ordering;
    /** Knoppen rechts in de kop (uitverkocht, open/dicht, geluid). */
    actions?: ReactNode;
};

/**
 * Keukenlayout voor de tv aan de muur: donkere kop met naam, knoppen en de rode balk als
 * bestellen dicht is. Geen sidebar, geen kleine tekst.
 */
export default function KeukenLayout({ children, ordering, actions }: Props) {
    return (
        <div className="flex min-h-dvh flex-col bg-background text-foreground">
            <Head title="Keuken" />

            <header className="border-b border-border bg-card">
                <div className="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div className="flex items-baseline gap-3">
                        <span className="font-display text-3xl leading-none font-extrabold tracking-tight uppercase">
                            <span className="text-primary">Frituur</span> VTI
                        </span>
                        <span className="font-display text-xl leading-none font-extrabold tracking-wide text-muted-foreground uppercase">
                            Keuken
                        </span>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        {actions}
                        <BigButton
                            variant="ghost"
                            size="md"
                            asChild
                            aria-label="Naar het adminpaneel"
                        >
                            <Link href={ordersIndex()}>
                                <SlidersHorizontal aria-hidden />
                                Admin
                            </Link>
                        </BigButton>
                        <BigButton
                            variant="ghost"
                            size="md"
                            onClick={() => router.post(logout.url())}
                            aria-label="Uitloggen"
                        >
                            <LogOut aria-hidden />
                            Uitloggen
                        </BigButton>
                    </div>
                </div>

                {!ordering.is_open && (
                    <ClosedBanner
                        message={ordering.closed_message}
                        className="mx-0 px-6"
                    />
                )}
            </header>

            <main className="flex-1 px-6 py-6">{children}</main>
        </div>
    );
}
