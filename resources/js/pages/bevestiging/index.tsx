import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';
import {
    BigButton,
    EmptyState,
    OrderLines,
    OrderSkeleton,
} from '@/components/frituur';
import { useOrderTracking } from '@/hooks/use-order-tracking';
import FrituurLayout from '@/layouts/frituur-layout';
import { home, opvolgen } from '@/routes';

type Props = { token: string };

export default function BevestigingPage({ token }: Props) {
    const { order, loading, errorStatus, errorMessage, reload } =
        useOrderTracking(token);

    return (
        <FrituurLayout title="Bestelling ontvangen" reserveCartBar={false}>
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
                <div className="flex flex-col gap-8 py-8 motion-safe:animate-fade-up sm:py-12">
                    <div className="text-center">
                        <p className="inline-flex items-center gap-2 font-display text-base font-extrabold tracking-[0.2em] text-primary uppercase">
                            <CheckCircle2 className="size-5" aria-hidden />
                            Bestelling ontvangen
                        </p>
                        <p className="mt-6 text-lg text-muted-foreground">
                            Je bestelnummer
                        </p>
                        <p
                            className="mt-2 font-display text-[clamp(4rem,18vw,8rem)] leading-none font-extrabold tracking-tight tabular-nums"
                            aria-label={`Bestelnummer ${order.number}`}
                        >
                            {order.number}
                        </p>
                        <p className="mt-6 text-xl font-bold">
                            Toon dit nummer aan de toog.
                        </p>
                        <p className="mt-2 text-lg text-muted-foreground">
                            Je betaalt bij afhaling (cash of kaart).
                        </p>
                    </div>

                    <BigButton asChild size="xl" block>
                        <Link href={opvolgen.url({ token })}>
                            Volg je bestelling
                            <ArrowRight aria-hidden />
                        </Link>
                    </BigButton>

                    <section aria-labelledby="overzicht">
                        <h2 id="overzicht" className="mb-4 uppercase">
                            Wat je bestelde
                        </h2>
                        <OrderLines order={order} />
                        <p className="mt-3 text-base text-muted-foreground">
                            Voor {order.customer_name} · {order.customer_phone}
                        </p>
                    </section>

                    <BigButton asChild variant="ghost" size="lg" block>
                        <Link href={home.url()}>Terug naar het menu</Link>
                    </BigButton>
                </div>
            )}
        </FrituurLayout>
    );
}
