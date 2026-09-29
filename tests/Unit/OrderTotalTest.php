<?php

namespace Tests\Unit;

use App\Actions\PlaceOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Totaalberekening in PlaceOrder: som van aantal × prijs per regel, in centen, uit de producten in de databank.
 */
class OrderTotalTest extends TestCase
{
    use RefreshDatabase;

    private PlaceOrder $action;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::factory()->create();
        $this->action = $this->app->make(PlaceOrder::class);
    }

    public function test_totaal_met_aantallen_groter_dan_een_en_meerdere_regels(): void
    {
        $friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);
        $frikandel = Product::factory()->create(['name' => 'Frikandel', 'price_cents' => 220]);
        $mayo = Product::factory()->create(['name' => 'Mayonaise', 'price_cents' => 90]);
        $cola = Product::factory()->create(['name' => 'Coca-Cola', 'price_cents' => 220]);

        $order = $this->action->handle('Jef', '0470 12 34 56', [
            ['product_id' => $friet->id, 'quantity' => 2],
            ['product_id' => $frikandel->id, 'quantity' => 3],
            ['product_id' => $mayo->id, 'quantity' => 2],
            ['product_id' => $cola->id, 'quantity' => 1],
        ], '127.0.0.1');

        $expected = 2 * 420 + 3 * 220 + 2 * 90 + 1 * 220;

        $this->assertSame(1900, $expected);
        $this->assertSame($expected, $order->total_cents);
        $this->assertSame($expected, $order->fresh()?->total_cents);
        $this->assertSame($expected, (int) $order->lines()->sum('line_total_cents'));

        $lines = $order->lines()->orderBy('id')->get();
        $this->assertCount(4, $lines);
        $this->assertSame([840, 660, 180, 220], $lines->pluck('line_total_cents')->all());
        $this->assertSame([2, 3, 2, 1], $lines->pluck('quantity')->all());
        $this->assertSame([420, 220, 90, 220], $lines->pluck('unit_price_cents')->all());
    }

    public function test_maximale_bestelling_blijft_een_integer_in_centen(): void
    {
        $duur = Product::factory()->create(['price_cents' => 999999]);

        $order = $this->action->handle('Jef', '0470 12 34 56', [
            ['product_id' => $duur->id, 'quantity' => 20],
        ], '127.0.0.1');

        $this->assertSame(19999980, $order->total_cents);
        $this->assertIsInt($order->fresh()?->total_cents);
    }

    public function test_een_prijswijziging_verandert_een_bestaande_bestelling_niet(): void
    {
        $friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);

        $order = $this->action->handle('Jef', '0470 12 34 56', [
            ['product_id' => $friet->id, 'quantity' => 2],
        ], '127.0.0.1');

        $friet->update(['price_cents' => 999, 'name' => 'Reuzenfriet']);

        $fresh = Order::query()->with('lines')->findOrFail($order->id);

        $this->assertSame(840, $fresh->total_cents);
        $this->assertSame(420, $fresh->lines[0]->unit_price_cents);
        $this->assertSame(840, $fresh->lines[0]->line_total_cents);
        $this->assertSame('Grote friet', $fresh->lines[0]->product_name);

        // Ook via de API: de klant ziet nog altijd het bedrag van bij het bestellen.
        $this->getJson('/api/orders/'.$order->public_token)
            ->assertOk()
            ->assertJsonPath('order.total_cents', 840)
            ->assertJsonPath('order.lines.0.unit_price', '€ 4,20')
            ->assertJsonPath('order.lines.0.product_name', 'Grote friet');

        // Een nieuwe bestelling gebruikt wél de nieuwe prijs.
        $nieuw = $this->action->handle('An', '0499 88 77 66', [
            ['product_id' => $friet->id, 'quantity' => 1],
        ], '127.0.0.1');

        $this->assertSame(999, $nieuw->total_cents);
        $this->assertSame('Reuzenfriet', $nieuw->lines[0]->product_name);
    }

    public function test_verwijderen_van_het_product_laat_de_bestelling_intact(): void
    {
        $friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);

        $order = $this->action->handle('Jef', '0470 12 34 56', [
            ['product_id' => $friet->id, 'quantity' => 2],
        ], '127.0.0.1');

        $friet->delete();

        $this->getJson('/api/orders/'.$order->public_token)
            ->assertOk()
            ->assertJsonPath('order.total_cents', 840)
            ->assertJsonPath('order.lines.0.product_id', null)
            ->assertJsonPath('order.lines.0.product_name', 'Grote friet');
    }
}
