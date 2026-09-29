<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $friet;

    private Product $mayo;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::factory()->create();
        $this->friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);
        $this->mayo = Product::factory()->create(['name' => 'Mayonaise', 'price_cents' => 90]);
    }

    public function test_een_bestelling_krijgt_een_nummer_en_een_server_side_totaal(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => '0470 12 34 56',
            'total_cents' => 1,
            'lines' => [
                ['product_id' => $this->friet->id, 'quantity' => 2, 'unit_price_cents' => 1],
                ['product_id' => $this->mayo->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.status', 'nieuw')
            ->assertJsonPath('order.status_label', 'Ontvangen')
            ->assertJsonPath('order.customer_name', 'Jef')
            ->assertJsonPath('order.customer_phone', '0470 12 34 56')
            ->assertJsonPath('order.total_cents', 930)
            ->assertJsonPath('order.total', '€ 9,30')
            ->assertJsonPath('order.started_at', null)
            ->assertJsonCount(2, 'order.lines')
            ->assertJsonPath('order.lines.0.product_name', 'Grote friet')
            ->assertJsonPath('order.lines.0.unit_price_cents', 420)
            ->assertJsonPath('order.lines.0.line_total_cents', 840)
            ->assertJsonPath('order.lines.0.line_total', '€ 8,40');

        $order = Order::query()->findOrFail($response->json('order.id'));

        $this->assertSame('VTI-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT), $order->number);
        $this->assertSame($order->number, $response->json('order.number'));
        $this->assertMatchesRegularExpression('/^VTI-\d{3,}$/', (string) $order->number);
        $this->assertSame($order->public_token, $response->json('order.token'));
        $this->assertSame(24, strlen($order->public_token));
        $this->assertSame(url('/bestelling/'.$order->public_token), $response->json('track_url'));
        $this->assertSame('0470123456', $order->customer_phone);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $response->json('order.placed_at'));
        $this->assertDatabaseHas('order_lines', ['order_id' => $order->id, 'product_id' => $this->mayo->id, 'line_total_cents' => 90]);
    }

    public function test_nummers_lopen_op_en_zijn_uniek(): void
    {
        $first = $this->placeOrder('0470 11 11 11')->json('order.number');
        $second = $this->placeOrder('0470 22 22 22')->json('order.number');

        $this->assertNotSame($first, $second);
        $this->assertGreaterThan((int) substr($first, 4), (int) substr($second, 4));
    }

    public function test_een_uitverkocht_product_kan_niet_besteld_worden(): void
    {
        $this->friet->update(['is_sold_out' => true]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => '0470 12 34 56',
            'lines' => [
                ['product_id' => $this->mayo->id, 'quantity' => 1],
                ['product_id' => $this->friet->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable();

        $this->assertError($response, 'lines.1.product_id', 'Grote friet is uitverkocht.');
        $this->assertArrayNotHasKey('lines.0.product_id', $response->json('errors'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_een_verborgen_of_onbestaand_product_bestaat_niet_meer(): void
    {
        $this->friet->update(['is_visible' => false]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => '0470 12 34 56',
            'lines' => [
                ['product_id' => $this->friet->id, 'quantity' => 1],
                ['product_id' => 999999, 'quantity' => 1],
            ],
        ])->assertUnprocessable();

        // Onbestaand product valt al op de validatie (exists), verborgen op de action: zelfde boodschap.
        $this->assertError($response, 'lines.1.product_id', 'Dit product bestaat niet meer.');

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => '0470 12 34 56',
            'lines' => [['product_id' => $this->friet->id, 'quantity' => 1]],
        ])->assertUnprocessable();

        $this->assertError($response, 'lines.0.product_id', 'Dit product bestaat niet meer.');
    }

    public function test_gesloten_bestellen_weigert_met_de_boodschap_van_de_baas(): void
    {
        Setting::query()->update(['is_open' => false, 'closed_message' => 'Vandaag gesloten']);

        $this->placeOrder()
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Bestellen is momenteel gesloten.')
            ->assertJsonPath('errors.ordering.0', 'Vandaag gesloten');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_validatiefouten_zijn_nederlands(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'J',
            'customer_phone' => '056 00 00 00',
            'lines' => [
                ['product_id' => $this->friet->id, 'quantity' => 21],
                ['product_id' => $this->friet->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable();

        $this->assertError($response, 'customer_name', 'Je naam moet minstens 2 tekens lang zijn.');
        $this->assertError($response, 'customer_phone', 'Vul een geldig Belgisch gsm-nummer in (bv. 0470 12 34 56).');
        $this->assertError($response, 'lines.0.quantity', 'Maximum 20 stuks per product.');
        $this->assertError($response, 'lines.0.product_id', 'Dit product staat twee keer in je mandje.');

        $this->postJson('/api/orders', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.customer_name.0', 'Je naam is verplicht.')
            ->assertJsonPath('errors.customer_phone.0', 'Je gsm-nummer is verplicht.')
            ->assertJsonPath('errors.lines.0', 'Je mandje is leeg.');
    }

    public function test_de_samenvatting_bij_meerdere_fouten_is_nederlands(): void
    {
        // F1: Laravel plakt "(and :count more errors)" achter de eerste fout; lang/nl.json vertaalt dat.
        $this->postJson('/api/orders', ['customer_name' => 'J', 'customer_phone' => 'x', 'lines' => []])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Je naam moet minstens 2 tekens lang zijn. (en nog 2 fouten)');

        $this->postJson('/api/orders', ['customer_name' => 'Jef', 'customer_phone' => 'x', 'lines' => []])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Vul een geldig Belgisch gsm-nummer in (bv. 0470 12 34 56). (en nog 1 fout)');
    }

    public function test_de_schrijfwijze_met_nul_tussen_haakjes_wordt_aanvaard(): void
    {
        // F4: "+32 (0)470 12 34 56" is gangbaar op visitekaartjes en in gsm-adresboeken.
        $this->placeOrder('+32 (0)470 12 34 56')
            ->assertCreated()
            ->assertJsonPath('order.customer_phone', '0470 12 34 56');

        $this->assertSame('0470123456', Order::query()->latest('id')->firstOrFail()->customer_phone);
    }

    /**
     * `assertJsonPath` kan niet met punten in sleutels zoals `lines.0.product_id`.
     */
    private function assertError(TestResponse $response, string $key, string $message): void
    {
        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey($key, $errors, 'Geen fout op '.$key.': '.json_encode(array_keys($errors)));
        $this->assertSame($message, $errors[$key][0]);
    }

    private function placeOrder(string $phone = '0470 12 34 56'): TestResponse
    {
        return $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => $phone,
            'lines' => [['product_id' => $this->friet->id, 'quantity' => 1]],
        ]);
    }
}
