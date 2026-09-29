<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_een_bestelling_is_op_te_volgen_via_haar_token(): void
    {
        $order = Order::factory()->bezig()->create(['customer_name' => 'Jef', 'customer_phone' => '0470123456', 'total_cents' => 840]);
        OrderLine::factory()->for($order)->create(['product_name' => 'Grote friet', 'quantity' => 2, 'unit_price_cents' => 420, 'line_total_cents' => 840]);
        $order->refresh();

        $this->getJson('/api/orders/'.$order->public_token)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.number', $order->number)
            ->assertJsonPath('order.token', $order->public_token)
            ->assertJsonPath('order.status', 'bezig')
            ->assertJsonPath('order.status_label', 'In de maak')
            ->assertJsonPath('order.customer_phone', '0470 12 34 56')
            ->assertJsonPath('order.total_cents', 840)
            ->assertJsonPath('order.lines.0.product_name', 'Grote friet')
            ->assertJsonPath('order.lines.0.unit_price', '€ 4,20')
            ->assertJsonMissingPath('track_url');

        $this->assertNotNull($this->getJson('/api/orders/'.$order->public_token)->json('order.started_at'));
    }

    public function test_een_onbekend_token_geeft_een_nederlandse_404(): void
    {
        Order::factory()->create();

        $this->getJson('/api/orders/onbekendtoken')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Bestelling niet gevonden.']);
    }

    public function test_een_token_met_streepjes_of_te_lang_geeft_dezelfde_nederlandse_404(): void
    {
        // F3: geen route-constraint meer, dus ook `-`, `_` en > 64 tekens landen in de controller.
        foreach (['VOORBEELD-TOKEN', 'a_b', str_repeat('a', 65)] as $token) {
            $this->getJson('/api/orders/'.$token)
                ->assertNotFound()
                ->assertExactJson(['message' => 'Bestelling niet gevonden.']);
        }
    }

    public function test_het_bestelnummer_zelf_is_geen_geldige_opvolglink(): void
    {
        $order = Order::factory()->create();

        $this->getJson('/api/orders/'.$order->number)->assertNotFound();
    }
}
