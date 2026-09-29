import { Link } from '@inertiajs/react';
import { WifiOff } from 'lucide-react';
import {
    BigButton,
    EmptyState,
    OrderLines,
    OrderSkeleton,
    StatusSteps,
} from '@/components/frituur';
import {
    TRACKING_INTERVAL_MS,
    useOrderTracking,
} from '@/hooks/use-order-tracking';
import FrituurLayout from '@/layouts/frituur-layout';
import { home } from '@/routes';
import type { OrderStatus } from '@/types/frituur';

type Props = { token: string };

const STATUS_TEXT: Record<OrderStatus, { title: string; text: string }> = {
    nieuw: {
        title: 'We hebben je bestelling',
        text: 'Ze staat in de rij in de keuken. Deze pagina vernieuwt vanzelf.',
    },
    bezig: {
        title: 'We zijn ermee bezig',
        text: 'De frieten liggen in het vet. Nog even.',
    },
    klaar: {
        title: 'Je bestelling ligt klaar aan de toog',
        text: 'Kom ze halen en toon je bestelnummer.',
    },
    afgehaald: {
        title: 'Afgehaald. Smakelijk!',
        text: 'Bedankt voor je bestelling. Tot de volgende keer.',
    },
};

export default function OpvolgenPage({ token }: Props) {
    const { order, loading, errorStatus, errorMessage, reload } =
        useOrderTracking(token, { poll: true });

    const seconds = Math.round(TRACKING_INTERVAL_MS / 1000);

    return (
        <FrituurLayout title="Je bestelling" reserveCartBar={false}>
            {loading && !order && <OrderSkeleton />}

            {!loading && !order && (
                <EmptyState
                    icon={errorStatus === 404 ? '🔍' : '📡'}
                    title={
                        errorStatus === 404
                            ? 'Bestelling niet gevonden'
                            : 'Even geen verbinding'
                    }
                    text={
                        errorStatus === 404
                            ? 'Deze link klopt niet meer. Kijk je bestelnummer na aan de toog.'
                            : (errorMessage ??
                              'We konden je bestelling niet ophalen.')
                    }
                    action={
                        errorStatus === 404 ? (
                            <BigButton asChild size="xl">
                                <Link href={home.url()}>Naar het menu</Link>
                            </BigButton>
                        ) : (
                            <BigButton size="xl" onClick={() => void reload()}>
                                Opnieuw proberen
                            </BigButton>
                        )
                    }
                    className="my-10"
                />
            )}

            {order && (
                <div className="flex flex-col gap-8 py-8 sm:py-12">
                    <div className="text-center">
                        <p className="text-lg text-muted-foreground">
                            Bestelnummer
                        </p>
                        <p
                            className="mt-1 font-display text-[clamp(3rem,14vw,6rem)] leading-none font-extrabold tracking-tight tabular-nums"
                            aria-label={`Bestelnummer ${order.number}`}
                        >
                            {order.number}
                        </p>
                    </div>

                    <StatusSteps current={order.status} />

                    <div
                        role="status"
                        aria-live="polite"
                        className={
                            order.status === 'klaar'
                                ? 'rounded-lg bg-primary px-5 py-8 text-center text-primary-foreground motion-safe:animate-fade-in'
                                : 'rounded-lg border-2 border-border px-5 py-6 text-center motion-safe:animate-fade-in'
                        }
                    >
                        <h2
                            className={
                                order.status === 'klaar'
                                    ? 'text-[clamp(1.75rem,6vw,3rem)] uppercase'
                                    : 'uppercase'
                            }
                        >
                            {STATUS_TEXT[order.status].title}
                        </h2>
                        <p
                            className={
                                order.status === 'klaar'
                                    ? 'mt-3 text-lg font-medium'
                                    : 'mt-3 text-lg text-muted-foreground'
                            }
                        >
                            {STATUS_TEXT[order.status].text}
                        </p>
                    </div>

                    {errorStatus !== null && errorStatus !== 404 && (
                        <p
                            role="status"
                            className="flex items-center justify-center gap-2 text-base text-muted-foreground"
                        >
                            <WifiOff className="size-5" aria-hidden />
                            Even geen verbinding; we proberen vanzelf opnieuw.
                        </p>
                    )}

                    <section aria-labelledby="overzicht">
                        <h2 id="overzicht" className="mb-4 uppercase">
                            Je bestelling
                        </h2>
                        <OrderLines order={order} />
                        <p className="mt-3 text-base text-muted-foreground">
                            Voor {order.customer_name} · je betaalt bij
                            afhaling.
                        </p>
                    </section>

                    <p className="text-center text-sm text-muted-foreground">
                        {order.status === 'afgehaald'
                            ? 'Deze bestelling is afgerond.'
                            : `Vernieuwt vanzelf elke ${seconds} seconden.`}
                    </p>

                    <BigButton asChild variant="secondary" size="lg" block>
                        <Link href={home.url()}>Nog iets bestellen</Link>
                    </BigButton>
                </div>
            )}
        </FrituurLayout>
    );
}
