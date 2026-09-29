<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();
        $this->staff = User::factory()->create();
    }

    public function test_guests_can_not_open_admin(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    public function test_admin_root_redirects_to_orders(): void
    {
        $this->actingAs($this->staff)->get('/admin')->assertRedirect('/admin/bestellingen');
    }

    public function test_index_shows_only_today_by_default_newest_first(): void
    {
        $first = Order::factory()->withLines(1)->create(['created_at' => now()->subHours(2)]);
        $second = Order::factory()->withLines(1)->create(['created_at' => now()->subHour()]);
        Order::factory()->create(['created_at' => now()->subDays(2)]);

        $this->actingAs($this->staff)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/orders/index')
                ->has('orders.data', 2)
                ->where('orders.data.0.number', $second->number)
                ->where('orders.data.1.number', $first->number)
                ->where('filters.from', now()->toDateString())
                ->has('statuses', 4));
    }

    public function test_index_searches_on_number_name_and_phone_in_history(): void
    {
        $jef = Order::factory()->create(['customer_name' => 'Jef', 'customer_phone' => '0470123456', 'created_at' => now()->subDays(3)]);
        Order::factory()->create(['customer_name' => 'Mia', 'customer_phone' => '0499887766']);

        $from = now()->subDays(7)->toDateString();
        $to = now()->toDateString();

        foreach ([$jef->number, 'jef', '0470 12 34 56'] as $term) {
            $this->actingAs($this->staff)
                ->get(route('admin.orders.index', ['q' => $term, 'from' => $from, 'to' => $to]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->has('orders.data', 1)
                    ->where('orders.data.0.number', $jef->number)
                    ->where('filters.q', $term));
        }
    }

    public function test_index_filters_on_status(): void
    {
        Order::factory()->create();
        $ready = Order::factory()->klaar()->create();

        $this->actingAs($this->staff)
            ->get(route('admin.orders.index', ['status' => 'klaar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $ready->number));
    }

    public function test_show_renders_the_order_with_lines(): void
    {
        $order = Order::factory()->withLines(2)->create();

        $this->actingAs($this->staff)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/orders/show')
                ->where('order.number', $order->number)
                ->has('order.lines', 2)
                ->where('order.total_cents', $order->fresh()?->total_cents));
    }

    public function test_status_can_be_advanced_from_admin(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->staff)
            ->patch(route('admin.orders.status', $order), ['status' => 'bezig'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Bezig, $order->fresh()?->status);

        $this->actingAs($this->staff)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => 'nieuw'])
            ->assertSessionHasErrors('status');
    }
}
