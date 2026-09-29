<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Wat de baas in admin of keuken doet, moet de klant meteen in het menu en bij het bestellen merken.
 */
class AdminMenuFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Setting::factory()->create();
        $this->staff = User::factory()->create();
    }

    public function test_product_aanmaken_wijzigen_en_uitverkocht_zetten_verschijnt_in_het_menu(): void
    {
        $category = Category::factory()->create(['name' => 'Snacks', 'slug' => 'snacks']);

        // Aanmaken
        $this->actingAs($this->staff)
            ->post('/admin/producten', [
                'name' => 'Bicky Burger',
                'description' => 'Krokant broodje',
                'price' => '4,00',
                'category_id' => $category->id,
                'is_visible' => true,
                'is_sold_out' => false,
            ])
            ->assertRedirect('/admin/producten')
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Bicky Burger')->firstOrFail();

        $menu = $this->getJson('/api/menu')->assertOk();
        $this->assertSame('Bicky Burger', $this->menuProduct($menu, $product->id)['name']);
        $this->assertSame(400, $this->menuProduct($menu, $product->id)['price_cents']);
        $this->assertSame('€ 4,00', $this->menuProduct($menu, $product->id)['price']);
        $this->assertFalse($this->menuProduct($menu, $product->id)['is_sold_out']);

        // Wijzigen (prijs en naam)
        $this->actingAs($this->staff)
            ->put("/admin/producten/{$product->id}", [
                'name' => 'Bicky Burger XL',
                'price' => '4.50',
                'category_id' => $category->id,
                'is_visible' => true,
                'is_sold_out' => false,
            ])
            ->assertRedirect('/admin/producten')
            ->assertSessionHasNoErrors();

        $menu = $this->getJson('/api/menu')->assertOk();
        $this->assertSame('Bicky Burger XL', $this->menuProduct($menu, $product->id)['name']);
        $this->assertSame(450, $this->menuProduct($menu, $product->id)['price_cents']);

        // Uitverkocht via de admin-schakelaar
        $this->actingAs($this->staff)
            ->patch("/admin/producten/{$product->id}/schakelaars", ['is_sold_out' => true])
            ->assertRedirect();

        $menu = $this->getJson('/api/menu')->assertOk();
        $this->assertTrue($this->menuProduct($menu, $product->id)['is_sold_out']);

        $this->placeOrder($product)->assertUnprocessable()
            ->assertJsonPath('errors', fn (array $errors): bool => ($errors['lines.0.product_id'][0] ?? null) === 'Bicky Burger XL is uitverkocht.');

        // Weer beschikbaar via de keuken
        $this->actingAs($this->staff)
            ->patchJson("/keuken/producten/{$product->id}/uitverkocht", ['is_sold_out' => false])
            ->assertOk()
            ->assertJsonPath('product.is_sold_out', false);

        $menu = $this->getJson('/api/menu')->assertOk();
        $this->assertFalse($this->menuProduct($menu, $product->id)['is_sold_out']);
        $this->placeOrder($product, '0470 22 22 22')->assertCreated()->assertJsonPath('order.total_cents', 450);

        // Verbergen: weg uit het menu en niet meer te bestellen
        $this->actingAs($this->staff)
            ->patch("/admin/producten/{$product->id}/schakelaars", ['is_visible' => false])
            ->assertRedirect();

        $this->getJson('/api/menu')->assertOk()->assertJsonMissing(['id' => $product->id, 'name' => 'Bicky Burger XL']);
        $this->placeOrder($product, '0470 33 33 33')->assertUnprocessable();
    }

    public function test_bestellen_sluiten_vanuit_admin_geeft_de_klant_422_met_de_boodschap(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)
            ->put('/admin/bestellen', ['is_open' => false, 'closed_message' => 'Vandaag gesloten, tot morgen!'])
            ->assertRedirect('/admin/bestellen')
            ->assertSessionHasNoErrors();

        $this->getJson('/api/menu')
            ->assertOk()
            ->assertJsonPath('ordering.is_open', false)
            ->assertJsonPath('ordering.closed_message', 'Vandaag gesloten, tot morgen!');

        $this->placeOrder($product)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Bestellen is momenteel gesloten.')
            ->assertJsonPath('errors.ordering.0', 'Vandaag gesloten, tot morgen!');

        $this->assertDatabaseCount('orders', 0);

        // Heropenen vanuit de keuken: bestellen kan weer, de boodschap blijft bewaard.
        $this->actingAs($this->staff)
            ->patchJson('/keuken/bestellen', ['is_open' => true])
            ->assertOk()
            ->assertJsonPath('ordering.is_open', true);

        $this->placeOrder($product)->assertCreated();
        $this->assertSame('Vandaag gesloten, tot morgen!', Setting::current()->closed_message);
    }

    public function test_sluiten_zonder_boodschap_wordt_geweigerd_en_klanten_kunnen_blijven_bestellen(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)
            ->from('/admin/bestellen')
            ->put('/admin/bestellen', ['is_open' => false])
            ->assertSessionHasErrors('closed_message');

        $this->actingAs($this->staff)
            ->patchJson('/keuken/bestellen', ['is_open' => false, 'closed_message' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('closed_message');

        $this->actingAs($this->staff)
            ->patchJson('/keuken/bestellen', ['is_open' => false, 'closed_message' => str_repeat('a', 141)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.closed_message.0', 'De boodschap mag maximaal 140 tekens zijn.');

        $this->assertTrue(Setting::current()->is_open);
        $this->placeOrder($product)->assertCreated();
    }

    public function test_cijfers_kloppen_met_geseede_producten_en_geplaatste_bestellingen(): void
    {
        $this->seed(MenuSeeder::class);

        $friet = Product::query()->where('slug', 'grote-friet')->firstOrFail();   // 420
        $frikandel = Product::query()->where('slug', 'frikandel')->firstOrFail(); // 220
        $mayo = Product::query()->where('slug', 'mayonaise')->firstOrFail();      // 90

        // Drie bestellingen vandaag via de echte API (drie verschillende nummers).
        $this->postJson('/api/orders', [
            'customer_name' => 'Jef', 'customer_phone' => '0470 11 11 11',
            'lines' => [['product_id' => $friet->id, 'quantity' => 2], ['product_id' => $mayo->id, 'quantity' => 2]],
        ])->assertCreated()->assertJsonPath('order.total_cents', 1020);

        $this->postJson('/api/orders', [
            'customer_name' => 'An', 'customer_phone' => '0470 22 22 22',
            'lines' => [['product_id' => $frikandel->id, 'quantity' => 3]],
        ])->assertCreated()->assertJsonPath('order.total_cents', 660);

        $third = $this->postJson('/api/orders', [
            'customer_name' => 'Mia', 'customer_phone' => '0470 33 33 33',
            'lines' => [['product_id' => $friet->id, 'quantity' => 1], ['product_id' => $frikandel->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('order.total_cents', 640);

        // Een afgehaalde telt ook mee (B-10).
        $done = Order::query()->findOrFail($third->json('order.id'));
        $done->update(['status' => 'afgehaald', 'picked_up_at' => now()]);

        // Een oude bestelling van vorige maand telt niet mee.
        Order::factory()->create(['total_cents' => 99999, 'created_at' => now()->subDays(40)]);

        $this->actingAs($this->staff)
            ->get('/admin/cijfers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/stats')
                ->where('stats.today.orders', 3)
                ->where('stats.today.revenue_cents', 1020 + 660 + 640)
                ->where('stats.today.revenue', '€ 23,20')
                ->where('stats.week.orders', 3)
                ->where('stats.week.revenue_cents', 2320)
                ->where('stats.top_today.0.product_name', 'Frikandel')
                ->where('stats.top_today.0.quantity', 4)
                ->where('stats.top_today.0.revenue_cents', 880)
                ->where('stats.top_today.1.product_name', 'Grote friet')
                ->where('stats.top_today.1.quantity', 3)
                ->where('stats.top_today.2.product_name', 'Mayonaise')
                ->where('stats.top_today.2.quantity', 2)
                ->has('stats.per_day', 7)
                ->where('stats.per_day.6.orders', 3)
                ->where('stats.per_day.6.revenue_cents', 2320));

        // De bestellingenlijst van vandaag toont dezelfde drie, nieuwste eerst.
        $this->actingAs($this->staff)
            ->get('/admin/bestellingen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 3)
                ->where('orders.data.0.number', $done->number)
                ->where('orders.total', 3));
    }

    public function test_geseed_menu_is_volledig_bestelbaar_en_komt_op_het_keukenbord(): void
    {
        $this->seed(MenuSeeder::class);

        $menu = $this->getJson('/api/menu')->assertOk()->assertJsonCount(4, 'categories');
        $ids = collect($menu->json('categories'))->flatMap(fn (array $c) => collect($c['products'])->pluck('id'))->all();
        $this->assertCount(27, $ids);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'De klas',
            'customer_phone' => '0470 12 34 56',
            'lines' => array_map(fn (int $id) => ['product_id' => $id, 'quantity' => 1], $ids),
        ])->assertCreated()->assertJsonCount(27, 'order.lines');

        $expected = Product::query()->sum('price_cents');
        $response->assertJsonPath('order.total_cents', (int) $expected);

        $this->actingAs($this->staff)
            ->getJson('/keuken/bestellingen')
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.number', $response->json('order.number'))
            ->assertJsonCount(27, 'orders.0.lines');
    }

    /**
     * @return array<string, mixed>
     */
    private function menuProduct(TestResponse $menu, int $id): array
    {
        foreach ($menu->json('categories') as $category) {
            foreach ($category['products'] as $product) {
                if ($product['id'] === $id) {
                    return $product;
                }
            }
        }

        $this->fail("Product $id staat niet in het menu.");
    }

    private function placeOrder(Product $product, string $phone = '0470 12 34 56'): TestResponse
    {
        return $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => $phone,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
    }
}
