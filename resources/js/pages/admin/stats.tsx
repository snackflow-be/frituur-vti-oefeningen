import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import { EmptyState } from '@/components/frituur';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import type { Stats, TopProduct } from '@/types/staff';

type Props = {
    stats: Stats;
};

function Tile({
    label,
    value,
    accent = false,
}: {
    label: string;
    value: string;
    accent?: boolean;
}) {
    return (
        <div
            className={cn(
                'rounded-lg border border-border p-5',
                accent ? 'bg-primary text-primary-foreground' : 'bg-card',
            )}
        >
            <p
                className={cn(
                    'font-display text-sm tracking-wide uppercase',
                    accent ? 'opacity-80' : 'text-muted-foreground',
                )}
            >
                {label}
            </p>
            <p className="mt-2 font-display text-4xl leading-none font-extrabold tabular-nums">
                {value}
            </p>
        </div>
    );
}

function TopTable({ title, rows }: { title: string; rows: TopProduct[] }) {
    return (
        <section className="rounded-lg border border-border bg-card">
            <h2 className="border-b border-border px-4 py-3 text-lg uppercase">
                {title}
            </h2>
            {rows.length === 0 ? (
                <p className="px-4 py-6 text-base text-muted-foreground">
                    Nog niets verkocht.
                </p>
            ) : (
                <Table className="text-base">
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-10">#</TableHead>
                            <TableHead>Product</TableHead>
                            <TableHead className="text-right">Stuks</TableHead>
                            <TableHead className="text-right">Omzet</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row, i) => (
                            <TableRow key={row.product_name}>
                                <TableCell className="font-display font-extrabold text-primary tabular-nums">
                                    {i + 1}
                                </TableCell>
                                <TableCell className="font-bold">
                                    {row.product_name}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {row.quantity}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatCents(row.revenue_cents)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </section>
    );
}

export default function StatsPage({ stats }: Props) {
    const max = Math.max(1, ...stats.per_day.map((d) => d.revenue_cents));
    const nothingYet =
        stats.week.orders === 0 && stats.per_day.every((d) => d.orders === 0);

    return (
        <>
            <Head title="Cijfers" />
            <PageHeader
                title="Cijfers"
                description="Alle bestellingen tellen mee, ook wat nog niet afgehaald is. Week = sinds maandag."
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Tile
                    label="Omzet vandaag"
                    value={stats.today.revenue}
                    accent
                />
                <Tile
                    label="Bestellingen vandaag"
                    value={String(stats.today.orders)}
                />
                <Tile label="Omzet deze week" value={stats.week.revenue} />
                <Tile
                    label="Bestellingen deze week"
                    value={String(stats.week.orders)}
                />
            </div>

            {nothingYet ? (
                <EmptyState
                    icon="📈"
                    title="Nog geen cijfers"
                    text="Zodra de eerste bestelling binnenkomt, zie je hier omzet en topproducten."
                />
            ) : (
                <>
                    <section className="rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg uppercase">
                            Omzet per dag (laatste 7 dagen)
                        </h2>
                        <ol
                            className="mt-4 grid h-56 grid-cols-7 items-end gap-2 sm:gap-4"
                            aria-label="Omzet per dag"
                        >
                            {stats.per_day.map((day) => {
                                const height = Math.round(
                                    (day.revenue_cents / max) * 100,
                                );

                                return (
                                    <li
                                        key={day.date}
                                        className="flex h-full flex-col items-center justify-end gap-2 text-center"
                                    >
                                        <span className="text-xs font-bold tabular-nums sm:text-sm">
                                            {formatCents(day.revenue_cents)}
                                        </span>
                                        <div
                                            className={cn(
                                                'w-full rounded-t-lg transition-[height]',
                                                day.revenue_cents > 0
                                                    ? 'bg-primary'
                                                    : 'bg-secondary',
                                            )}
                                            style={{
                                                height: `${Math.max(height, 3)}%`,
                                            }}
                                            role="img"
                                            aria-label={`${day.label}: ${formatCents(day.revenue_cents)}, ${day.orders} bestellingen`}
                                        />
                                        <span className="text-xs text-muted-foreground tabular-nums sm:text-sm">
                                            {day.label}
                                        </span>
                                    </li>
                                );
                            })}
                        </ol>
                    </section>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <TopTable
                            title="Top 5 vandaag"
                            rows={stats.top_today}
                        />
                        <TopTable
                            title="Top 5 deze week"
                            rows={stats.top_week}
                        />
                    </div>
                </>
            )}
        </>
    );
}
