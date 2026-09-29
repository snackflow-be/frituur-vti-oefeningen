<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderLine;
use App\Queries\SalesStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_zonder_bestellingen_zijn_alle_cijfers_nul(): void
    {
        $stats = (new SalesStats)->handle();

        $this->assertSame(['revenue_cents' => 0, 'orders' => 0], $stats['today']);
        $this->assertSame(['revenue_cents' => 0, 'orders' => 0], $stats['week']);
        $this->assertSame([], $stats['top_today']);
        $this->assertSame([], $stats['top_week']);
    }

    public function test_vandaag_en_deze_week_tellen_alle_statussen_en_de_top_5_groepeert_op_naam(): void
    {
        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));

        $today = Order::factory()->create(['total_cents' => 1000]);
        $todayDone = Order::factory()->afgehaald()->create(['total_cents' => 500]);
        $thisWeek = Order::factory()->create(['total_cents' => 2000, 'created_at' => now()->subDays(2)]);
        Order::factory()->create(['total_cents' => 9999, 'created_at' => now()->subDays(10)]);

        OrderLine::factory()->for($today)->create(['product_name' => 'Grote friet', 'quantity' => 2, 'line_total_cents' => 840]);
        OrderLine::factory()->for($todayDone)->create(['product_name' => 'Grote friet', 'quantity' => 1, 'line_total_cents' => 420]);
        OrderLine::factory()->for($today)->create(['product_name' => 'Frikandel', 'quantity' => 1, 'line_total_cents' => 220]);
        OrderLine::factory()->for($thisWeek)->create(['product_name' => 'Frikandel', 'quantity' => 5, 'line_total_cents' => 1100]);

        $stats = (new SalesStats)->handle();

        $this->assertSame(['revenue_cents' => 1500, 'orders' => 2], $stats['today']);
        $this->assertSame(['revenue_cents' => 3500, 'orders' => 3], $stats['week']);

        $this->assertSame([
            ['product_name' => 'Grote friet', 'quantity' => 3, 'revenue_cents' => 1260],
            ['product_name' => 'Frikandel', 'quantity' => 1, 'revenue_cents' => 220],
        ], $stats['top_today']);

        $this->assertSame('Frikandel', $stats['top_week'][0]['product_name']);
        $this->assertSame(6, $stats['top_week'][0]['quantity']);
        $this->assertSame('Grote friet', $stats['top_week'][1]['product_name']);
    }
}
