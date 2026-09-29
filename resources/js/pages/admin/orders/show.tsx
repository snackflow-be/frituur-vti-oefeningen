import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Phone } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/admin/page-header';
import { STATUS_META, StatusBadge } from '@/components/frituur';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, status as statusRoute } from '@/routes/admin/orders';
import type { Order } from '@/types/frituur';

type Props = {
    order: Order;
};

function stamp(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('nl-BE', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function OrderShow({ order }: Props) {
    const [busy, setBusy] = useState(false);
    const meta = STATUS_META[order.status];
    const phoneHref = `tel:${order.customer_phone.replace(/\s/g, '')}`;

    const advance = () => {
        if (!meta.next) {
            return;
        }

        setBusy(true);
        router.patch(
            statusRoute.url(order.id),
            { status: meta.next },
            { onFinish: () => setBusy(false) },
        );
    };

    return (
        <>
            <Head title={`Bestelling ${order.number}`} />
            <PageHeader
                title={order.number}
                description={`Geplaatst op ${stamp(order.placed_at)}`}
                actions={
                    <>
                        <Button variant="outline" className="h-11" asChild>
                            <Link href={index()}>
                                <ArrowLeft />
                                Alle bestellingen
                            </Link>
                        </Button>
                        {meta.next && meta.action && (
                            <Button
                                className="h-11"
                                onClick={advance}
                                disabled={busy}
                            >
                                {meta.action}
                                <ArrowRight />
                            </Button>
                        )}
                    </>
                }
            />

            <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <section className="rounded-lg border border-border bg-card">
                    <Table className="text-base">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-20 text-right">
                                    Aantal
                                </TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead className="text-right">
                                    Stukprijs
                                </TableHead>
                                <TableHead className="text-right">
                                    Totaal
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {order.lines.map((line, i) => (
                                <TableRow key={`${line.product_name}-${i}`}>
                                    <TableCell className="text-right font-display text-lg font-extrabold text-primary tabular-nums">
                                        {line.quantity}×
                                    </TableCell>
                                    <TableCell className="font-bold">
                                        {line.product_name}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {line.unit_price}
                                    </TableCell>
                                    <TableCell className="text-right font-bold tabular-nums">
                                        {line.line_total}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <div className="flex items-center justify-between border-t border-border px-4 py-4">
                        <span className="text-base text-muted-foreground">
                            Betaald bij afhaling
                        </span>
                        <span className="font-display text-3xl font-extrabold tabular-nums">
                            {order.total}
                        </span>
                    </div>
                </section>

                <aside className="flex flex-col gap-6">
                    <section className="rounded-lg border border-border bg-card p-5">
                        <h2 className="text-lg uppercase">Klant</h2>
                        <p className="mt-3 text-2xl font-bold">
                            {order.customer_name}
                        </p>
                        <a
                            href={phoneHref}
                            className="mt-1 inline-flex min-h-11 items-center gap-2 text-lg text-primary tabular-nums hover:underline"
                        >
                            <Phone className="size-5" aria-hidden />
                            {order.customer_phone}
                        </a>
                    </section>

                    <section className="rounded-lg border border-border bg-card p-5">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg uppercase">Status</h2>
                            <StatusBadge
                                status={order.status}
                                variant="kitchen"
                            />
                        </div>
                        <dl className="mt-4 grid grid-cols-[1fr_auto] gap-y-2 text-base">
                            <dt className="text-muted-foreground">Ontvangen</dt>
                            <dd className="tabular-nums">
                                {stamp(order.placed_at)}
                            </dd>
                            <dt className="text-muted-foreground">Gestart</dt>
                            <dd className="tabular-nums">
                                {stamp(order.started_at)}
                            </dd>
                            <dt className="text-muted-foreground">Klaar</dt>
                            <dd className="tabular-nums">
                                {stamp(order.ready_at)}
                            </dd>
                            <dt className="text-muted-foreground">Afgehaald</dt>
                            <dd className="tabular-nums">
                                {stamp(order.picked_up_at)}
                            </dd>
                        </dl>
                    </section>
                </aside>
            </div>
        </>
    );
}
