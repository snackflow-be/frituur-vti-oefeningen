<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();

        // Vaste woensdag: zo valt "eerder deze week" nooit op vandaag (zie SalesStatsTest).
        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));
    }

    public function test_stats_page_shows_revenue_counts_top_products_and_per_day(): void
    {
        $friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);
        $mayo = Product::factory()->create(['name' => 'Mayonaise', 'price_cents' => 90]);

        $today = Order::factory()->create();
        OrderLine::factory()->forProduct($friet, 2)->create(['order_id' => $today->id]);
        OrderLine::factory()->forProduct($mayo, 1)->create(['order_id' => $today->id]);
        $today->update(['total_cents' => 930]);

        $earlierThisWeek = Order::factory()->create(['total_cents' => 500, 'created_at' => now()->startOfWeek()->addMinute()]);
        OrderLine::factory()->forProduct($friet, 1)->create(['order_id' => $earlierThisWeek->id]);

        Order::factory()->create(['total_cents' => 9999, 'created_at' => now()->subDays(30)]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.stats'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/stats')
                ->where('stats.today.revenue_cents', 930)
                ->where('stats.today.revenue', '€ 9,30')
                ->where('stats.today.orders', 1)
                ->where('stats.week.revenue_cents', 1430)
                ->where('stats.top_today.0.product_name', 'Grote friet')
                ->where('stats.top_today.0.quantity', 2)
                ->has('stats.per_day', 7)
                ->where('stats.per_day.6.date', now()->toDateString())
                ->where('stats.per_day.6.revenue_cents', 930)
                ->where('stats.per_day.6.orders', 1));
    }
}
