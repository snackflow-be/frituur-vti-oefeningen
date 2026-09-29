<?php

namespace App\Queries;

use App\Models\Order;
use App\Models\OrderLine;
use DateTimeInterface;

/**
 * Cijfers voor het adminpaneel: omzet en aantal bestellingen vandaag en deze week, plus de top 5
 * best verkochte producten (op aantal stuks). Telt alle bestellingen ongeacht status (B-10).
 *
 * @phpstan-type Period array{revenue_cents: int, orders: int}
 * @phpstan-type TopRow array{product_name: string, quantity: int, revenue_cents: int}
 */
class SalesStats
{
    public const int TOP_LIMIT = 5;

    /**
     * @return array{today: Period, week: Period, top_today: list<TopRow>, top_week: list<TopRow>}
     */
    public function handle(): array
    {
        $today = now()->startOfDay();
        $week = now()->startOfWeek();

        return [
            'today' => $this->period($today),
            'week' => $this->period($week),
            'top_today' => $this->top($today),
            'top_week' => $this->top($week),
        ];
    }

    /**
     * @return Period
     */
    private function period(DateTimeInterface $since): array
    {
        /** @var object{revenue_cents: int|string|null, orders: int|string} $row */
        $row = Order::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(total_cents), 0) as revenue_cents, COUNT(*) as orders')
            ->first();

        return [
            'revenue_cents' => (int) $row->revenue_cents,
            'orders' => (int) $row->orders,
        ];
    }

    /**
     * @return list<TopRow>
     */
    private function top(DateTimeInterface $since): array
    {
        $rows = OrderLine::query()
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.created_at', '>=', $since)
            ->groupBy('order_lines.product_name')
            ->selectRaw('order_lines.product_name, SUM(order_lines.quantity) as quantity, SUM(order_lines.line_total_cents) as revenue_cents')
            ->orderByRaw('SUM(order_lines.quantity) DESC')
            ->orderBy('order_lines.product_name')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->map(fn (OrderLine $line): array => [
                'product_name' => (string) $line->getAttribute('product_name'),
                'quantity' => (int) $line->getAttribute('quantity'),
                'revenue_cents' => (int) $line->getAttribute('revenue_cents'),
            ])
            ->all();

        return array_values($rows);
    }
}
