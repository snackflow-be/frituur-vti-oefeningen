<?php

namespace Tests\Feature\Database;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KitchenUserSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_seeder_maakt_het_volledige_menu_en_is_idempotent(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuSeeder::class);

        $this->assertSame(4, Category::query()->count());
        $this->assertSame(27, Product::query()->count());

        $names = Category::query()->ordered()->pluck('name')->all();
        $this->assertSame(['Frieten', 'Snacks', 'Sauzen', 'Dranken'], $names);

        $friet = Product::query()->where('slug', 'grote-friet')->firstOrFail();
        $this->assertSame('Grote friet', $friet->name);
        $this->assertSame(420, $friet->price_cents);
        $this->assertSame('Voor de echte honger of om te delen', $friet->description);
        $this->assertSame('frieten', $friet->category->slug);
        $this->assertTrue($friet->isOrderable());

        $snacks = Category::query()->where('slug', 'snacks')->firstOrFail();
        $this->assertSame(12, $snacks->products()->count());
        $this->assertSame('Frikandel', $snacks->products()->first()?->name);
    }

    public function test_menu_seeder_behoudt_wijzigingen_van_de_baas(): void
    {
        $this->seed(MenuSeeder::class);

        Product::query()->where('slug', 'kaaskroket')->update(['is_sold_out' => true, 'is_visible' => false]);

        $this->seed(MenuSeeder::class);

        $kroket = Product::query()->where('slug', 'kaaskroket')->firstOrFail();
        $this->assertTrue($kroket->is_sold_out);
        $this->assertFalse($kroket->is_visible);
        $this->assertFalse($kroket->isOrderable());
    }

    public function test_settings_seeder_maakt_exact_een_rij(): void
    {
        $this->seed(SettingsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->assertSame(1, Setting::query()->count());

        $setting = Setting::current();
        $this->assertSame('Frituur VTI', $setting->business_name);
        $this->assertTrue($setting->is_open);
        $this->assertNull($setting->closed_message);
        $this->assertCount(7, $setting->opening_hours);
        $this->assertSame(1, $setting->opening_hours[0]['day']);
        $this->assertCount(2, $setting->opening_hours[0]['slots']);
        $this->assertCount(1, $setting->opening_hours[6]['slots']);
        $this->assertSame('17:00', $setting->opening_hours[6]['slots'][0]['from']);
        $this->assertSame('22:00', $setting->opening_hours[6]['slots'][0]['to']);
    }

    public function test_database_seeder_draait_menu_instellingen_en_keukengebruiker(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(27, Product::query()->count());
        $this->assertSame(1, Setting::query()->count());
        $this->assertTrue(User::query()->where('email', KitchenUserSeeder::EMAIL)->exists());
    }

    public function test_order_factory_geeft_nummer_token_en_totaal_uit_de_regels(): void
    {
        $order = Order::factory()->withLines(3)->create();

        $this->assertMatchesRegularExpression('/^VTI-\d{3,}$/', (string) $order->number);
        $this->assertSame(Order::numberFor($order->id), $order->number);
        $this->assertSame(Order::TOKEN_LENGTH, strlen($order->public_token));
        $this->assertSame(OrderStatus::Nieuw, $order->status);
        $this->assertCount(3, $order->lines);
        $this->assertSame((int) $order->lines->sum('line_total_cents'), $order->total_cents);

        $klaar = Order::factory()->klaar()->create();
        $this->assertSame(OrderStatus::Klaar, $klaar->status);
        $this->assertNotNull($klaar->started_at);
        $this->assertNotNull($klaar->ready_at);
        $this->assertNull($klaar->picked_up_at);
    }

    public function test_bestelregel_houdt_naam_en_prijs_na_verwijderen_van_het_product(): void
    {
        $product = Product::factory()->create(['name' => 'Frikandel', 'price_cents' => 220]);
        $line = OrderLine::factory()->forProduct($product, 2)->create();

        $product->delete();

        $line->refresh();
        $this->assertNull($line->product_id);
        $this->assertSame('Frikandel', $line->product_name);
        $this->assertSame(220, $line->unit_price_cents);
        $this->assertSame(440, $line->line_total_cents);
    }

    public function test_categorie_met_producten_kan_niet_verwijderd_worden(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);

        $product->category->delete();
    }

    public function test_order_scopes_today_open_en_search(): void
    {
        $vandaag = Order::factory()->create(['customer_name' => 'Jef', 'customer_phone' => '0470123456']);
        $gisteren = Order::factory()->create(['customer_name' => 'An', 'created_at' => now()->subDay()]);
        $afgehaald = Order::factory()->afgehaald()->create(['customer_name' => 'Mia']);

        $this->assertEqualsCanonicalizing([$vandaag->id, $afgehaald->id], Order::query()->today()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$vandaag->id, $gisteren->id], Order::query()->open()->pluck('id')->all());
        $this->assertSame([$vandaag->id], Order::query()->search('0470 12 34')->pluck('id')->all());
        $this->assertSame([$vandaag->id], Order::query()->search((string) $vandaag->number)->pluck('id')->all());
        $this->assertSame([$gisteren->id], Order::query()->search('an')->where('id', $gisteren->id)->pluck('id')->all());
        $this->assertSame(
            [$gisteren->id],
            Order::query()->placedBetween(now()->subDays(2), now()->subHours(12))->pluck('id')->all(),
        );
    }
}
