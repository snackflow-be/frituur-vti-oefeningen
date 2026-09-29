<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Queries\SalesStats;
use App\Support\Money;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public const int DAYS = 7;

    /**
     * Omzet en aantal vandaag en deze week, top 5, en een eenvoudige grafiek per dag (laatste 7 dagen).
     */
    public function __invoke(SalesStats $stats): Response
    {
        $data = $stats->handle();

        return Inertia::render('admin/stats', [
            'stats' => [
                ...$data,
                'today' => [...$data['today'], 'revenue' => Money::format($data['today']['revenue_cents'])],
                'week' => [...$data['week'], 'revenue' => Money::format($data['week']['revenue_cents'])],
                'per_day' => $this->perDay(),
            ],
        ]);
    }

    /**
     * Omzet en aantal per dag voor de laatste 7 dagen (vandaag inbegrepen), oudste eerst.
     * Groeperen gebeurt in PHP zodat SQLite en MariaDB hetzelfde geven.
     *
     * @return list<array{date: string, label: string, revenue_cents: int, orders: int}>
     */
    private function perDay(): array
    {
        $start = now()->startOfDay()->subDays(self::DAYS - 1);

        $days = [];

        for ($i = 0; $i < self::DAYS; $i++) {
            $day = $start->copy()->addDays($i);
            $days[$day->toDateString()] = [
                'date' => $day->toDateString(),
                'label' => self::label($day),
                'revenue_cents' => 0,
                'orders' => 0,
            ];
        }

        Order::query()
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get(['created_at', 'total_cents'])
            ->each(function (Order $order) use (&$days): void {
                $key = $order->created_at?->toDateString();

                if ($key === null || ! isset($days[$key])) {
                    return;
                }

                $days[$key]['revenue_cents'] += $order->total_cents;
                $days[$key]['orders']++;
            });

        return array_values($days);
    }

    private static function label(CarbonInterface $day): string
    {
        $names = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

        return $names[$day->dayOfWeekIso - 1].' '.$day->format('d/m');
    }
}
