import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { PageHeader } from '@/components/admin/page-header';
import { EmptyState, StatusBadge } from '@/components/frituur';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, show } from '@/routes/admin/orders';
import type { Order, OrderStatus } from '@/types/frituur';
import type { OrderFilters, Paginated, StatusOption } from '@/types/staff';

type Props = {
    orders: Paginated<Order>;
    filters: OrderFilters;
    statuses: StatusOption[];
};

const ALL = 'all';

function isoDate(daysAgo: number): string {
    const date = new Date();
    date.setDate(date.getDate() - daysAgo);

    return date.toISOString().slice(0, 10);
}

function timeOf(iso: string): string {
    return new Date(iso).toLocaleTimeString('nl-BE', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function dateOf(iso: string): string {
    return new Date(iso).toLocaleDateString('nl-BE', {
        day: '2-digit',
        month: '2-digit',
    });
}

export default function OrdersIndex({ orders, filters, statuses }: Props) {
    const [form, setForm] = useState<OrderFilters>(filters);
    const multiDay = filters.from !== filters.to;

    const apply = (next: OrderFilters) => {
        setForm(next);
        router.get(
            index.url(),
            {
                q: next.q || undefined,
                from: next.from || undefined,
                to: next.to || undefined,
                status: next.status || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        apply(form);
    };

    const preset = (days: number) =>
        apply({ ...form, from: isoDate(days - 1), to: isoDate(0) });

    return (
        <>
            <Head title="Bestellingen" />
            <PageHeader
                title="Bestellingen"
                description="Vandaag en vroeger. Klik op een bestelling voor de details."
            />

            <form
                onSubmit={submit}
                className="grid gap-4 rounded-lg border border-border bg-card p-4 md:grid-cols-[1fr_auto_auto_auto_auto] md:items-end"
            >
                <div className="grid gap-2">
                    <Label htmlFor="q" className="text-base">
                        Zoeken
                    </Label>
                    <div className="relative">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="q"
                            value={form.q}
                            onChange={(e) =>
                                setForm({ ...form, q: e.target.value })
                            }
                            placeholder="Nummer, naam of gsm"
                            className="h-11 pl-10 text-base md:text-base"
                        />
                    </div>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="from" className="text-base">
                        Van
                    </Label>
                    <Input
                        id="from"
                        type="date"
                        value={form.from}
                        onChange={(e) =>
                            setForm({ ...form, from: e.target.value })
                        }
                        className="h-11 text-base md:text-base"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="to" className="text-base">
                        Tot
                    </Label>
                    <Input
                        id="to"
                        type="date"
                        value={form.to}
                        onChange={(e) =>
                            setForm({ ...form, to: e.target.value })
                        }
                        className="h-11 text-base md:text-base"
                    />
                </div>
                <div className="grid gap-2">
                    <Label className="text-base">Status</Label>
                    <Select
                        value={form.status || ALL}
                        onValueChange={(value) =>
                            apply({
                                ...form,
                                status:
                                    value === ALL ? '' : (value as OrderStatus),
                            })
                        }
                    >
                        <SelectTrigger className="h-11! min-w-40 bg-background text-base">
                            <SelectValue placeholder="Alle statussen" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>Alle statussen</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <Button type="submit" className="h-11">
                    Zoeken
                </Button>

                <div className="flex flex-wrap gap-2 md:col-span-5">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => preset(1)}
                    >
                        Vandaag
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => preset(7)}
                    >
                        Laatste 7 dagen
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => preset(30)}
                    >
                        Laatste 30 dagen
                    </Button>
                </div>
            </form>

            {orders.data.length === 0 ? (
                <EmptyState
                    icon="🧾"
                    title="Geen bestellingen"
                    text="Niets gevonden voor deze periode of zoekterm."
                />
            ) : (
                <div className="rounded-lg border border-border bg-card">
                    <Table className="text-base">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nummer</TableHead>
                                <TableHead>Tijd</TableHead>
                                <TableHead>Naam</TableHead>
                                <TableHead>Gsm</TableHead>
                                <TableHead className="text-right">
                                    Stuks
                                </TableHead>
                                <TableHead className="text-right">
                                    Totaal
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.data.map((order) => (
                                <TableRow
                                    key={order.id}
                                    className="cursor-pointer"
                                    onClick={() =>
                                        router.visit(show.url(order.id))
                                    }
                                >
                                    <TableCell className="font-display text-lg font-extrabold tabular-nums">
                                        <Link
                                            href={show(order.id)}
                                            className="hover:text-primary"
                                        >
                                            {order.number}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {multiDay && (
                                            <span className="mr-2 text-muted-foreground">
                                                {dateOf(order.placed_at)}
                                            </span>
                                        )}
                                        {timeOf(order.placed_at)}
                                    </TableCell>
                                    <TableCell className="font-bold">
                                        {order.customer_name}
                                    </TableCell>
                                    <TableCell className="tabular-nums">
                                        {order.customer_phone}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {order.lines.reduce(
                                            (sum, line) => sum + line.quantity,
                                            0,
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right font-bold tabular-nums">
                                        {order.total}
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            status={order.status}
                                            variant="kitchen"
                                            size="sm"
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <div className="flex items-center justify-between gap-4 border-t border-border px-4 py-3 text-sm text-muted-foreground">
                        <span className="tabular-nums">
                            {orders.from ?? 0}–{orders.to ?? 0} van{' '}
                            {orders.total}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!orders.prev_page_url}
                                onClick={() =>
                                    orders.prev_page_url &&
                                    router.visit(orders.prev_page_url, {
                                        preserveState: true,
                                    })
                                }
                                aria-label="Vorige pagina"
                            >
                                <ChevronLeft />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!orders.next_page_url}
                                onClick={() =>
                                    orders.next_page_url &&
                                    router.visit(orders.next_page_url, {
                                        preserveState: true,
                                    })
                                }
                                aria-label="Volgende pagina"
                            >
                                <ChevronRight />
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
