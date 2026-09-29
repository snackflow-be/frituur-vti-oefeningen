<?php

namespace Tests\Feature\Staff;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KitchenTest extends TestCase
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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('keuken'))->assertRedirect(route('login'));
        $this->getJson(route('keuken.orders'))->assertUnauthorized();
    }

    public function test_kitchen_page_renders_with_open_orders_of_today(): void
    {
        $open = Order::factory()->withLines(2)->create();
        Order::factory()->afgehaald()->create();
        Order::factory()->create(['created_at' => now()->subDay()]);

        $this->actingAs($this->staff)
            ->get(route('keuken'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('keuken/index')
                ->has('initial.orders', 1)
                ->where('initial.orders.0.number', $open->number)
                ->where('initial.orders.0.status', 'nieuw')
                ->has('initial.orders.0.lines', 2)
                ->where('initial.ordering.is_open', true)
                ->has('initial.server_time'));
    }

    public function test_board_json_lists_open_orders_oldest_first(): void
    {
        $older = Order::factory()->create(['created_at' => now()->subMinutes(10)]);
        $newer = Order::factory()->bezig()->create(['created_at' => now()->subMinute()]);
        Order::factory()->afgehaald()->create();

        $this->actingAs($this->staff)
            ->getJson(route('keuken.orders'))
            ->assertOk()
            ->assertJsonCount(2, 'orders')
            ->assertJsonPath('orders.0.number', $older->number)
            ->assertJsonPath('orders.1.number', $newer->number)
            ->assertJsonPath('orders.1.status_label', 'In de maak')
            ->assertJsonStructure(['server_time', 'ordering' => ['is_open', 'closed_message']]);
    }

    public function test_status_button_advances_to_the_next_step(): void
    {
        $order = Order::factory()->withLines(1)->create();

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.orders.status', $order), ['status' => 'bezig'])
            ->assertOk()
            ->assertJsonPath('order.status', 'bezig')
            ->assertJsonPath('order.number', $order->number);

        $order->refresh();
        $this->assertSame(OrderStatus::Bezig, $order->status);
        $this->assertNotNull($order->started_at);

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.orders.status', $order), ['status' => 'klaar'])
            ->assertOk()
            ->assertJsonPath('order.status', 'klaar');

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.orders.status', $order), ['status' => 'afgehaald'])
            ->assertOk()
            ->assertJsonPath('order.status', 'afgehaald');

        $this->assertNotNull($order->fresh()?->picked_up_at);
    }

    public function test_status_can_not_go_backwards_or_skip(): void
    {
        $order = Order::factory()->bezig()->create();

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.orders.status', $order), ['status' => 'nieuw'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.orders.status', $order), ['status' => 'afgehaald'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(OrderStatus::Bezig, $order->fresh()?->status);
    }

    public function test_products_can_be_marked_sold_out_and_back(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)
            ->getJson(route('keuken.products'))
            ->assertOk()
            ->assertJsonPath('categories.0.products.0.id', $product->id)
            ->assertJsonPath('categories.0.products.0.is_sold_out', false);

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.products.soldout', $product), ['is_sold_out' => true])
            ->assertOk()
            ->assertJsonPath('product.is_sold_out', true);

        $this->assertTrue($product->fresh()?->is_sold_out);

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.products.soldout', $product), ['is_sold_out' => false])
            ->assertOk();

        $this->assertFalse($product->fresh()?->is_sold_out);
    }

    public function test_ordering_can_be_closed_with_a_message_and_reopened(): void
    {
        $this->actingAs($this->staff)
            ->patchJson(route('keuken.ordering'), ['is_open' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('closed_message');

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.ordering'), ['is_open' => false, 'closed_message' => 'Te druk, bel ons'])
            ->assertOk()
            ->assertJsonPath('ordering.is_open', false)
            ->assertJsonPath('ordering.closed_message', 'Te druk, bel ons');

        $this->actingAs($this->staff)
            ->patchJson(route('keuken.ordering'), ['is_open' => true])
            ->assertOk()
            ->assertJsonPath('ordering.is_open', true)
            ->assertJsonPath('ordering.closed_message', 'Te druk, bel ons');
    }
}
