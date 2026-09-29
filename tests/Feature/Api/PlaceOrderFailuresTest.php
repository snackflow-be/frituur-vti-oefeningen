<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\OrderRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Faalgevallen van POST /api/orders (tester, nota 07). Elk geval: 422 of 429, Nederlandse boodschap,
 * en nooit een bestelling in de databank.
 */
class PlaceOrderFailuresTest extends TestCase
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

    public function test_lege_bestelling_wordt_geweigerd(): void
    {
        $this->send(['lines' => []])
            ->assertUnprocessable()
            ->assertJsonPath('errors.lines.0', 'Je mandje is leeg.');

        $this->send(['lines' => null])->assertUnprocessable()->assertJsonValidationErrors('lines');

        $body = $this->valid();
        unset($body['lines']);
        $this->postJson('/api/orders', $body)->assertUnprocessable()->assertJsonPath('errors.lines.0', 'Je mandje is leeg.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_ontbrekende_of_foute_naam_wordt_geweigerd(): void
    {
        $this->send(['customer_name' => ''])->assertUnprocessable()->assertJsonPath('errors.customer_name.0', 'Je naam is verplicht.');
        $this->send(['customer_name' => '   '])->assertUnprocessable()->assertJsonValidationErrors('customer_name');
        $this->send(['customer_name' => 'J'])->assertUnprocessable()->assertJsonValidationErrors('customer_name');
        $this->send(['customer_name' => str_repeat('a', 41)])->assertUnprocessable()->assertJsonValidationErrors('customer_name');
        $this->send(['customer_name' => ['Jef']])->assertUnprocessable()->assertJsonValidationErrors('customer_name');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_ontbrekend_of_fout_gsm_nummer_wordt_geweigerd(): void
    {
        $fout = [
            '',                 // leeg
            '056 00 00 00',     // vast nummer
            '0470 12 34',       // te kort
            '0470 12 34 56 78', // te lang
            '+31 6 12345678',   // Nederlands
            '0570123456',       // geen 04
            'abcdefghij',
        ];

        foreach ($fout as $phone) {
            $this->send(['customer_phone' => $phone])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('customer_phone');
        }

        $this->send(['customer_phone' => 470123456])->assertUnprocessable()->assertJsonValidationErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_geldige_schrijfwijzen_van_een_gsm_nummer_worden_genormaliseerd(): void
    {
        $vormen = ['+32470123456', '0032470123456', '0470/12.34.56', '(0470) 12-34-56', '+32 470 12 34 56'];

        foreach ($vormen as $i => $phone) {
            // Ander nummer per iteratie is niet nodig: de limiet is 3 per nummer, dus we resetten de teller.
            $this->app->make(RateLimiter::class)->clear(OrderRateLimiter::phoneKey('0470123456'));

            $this->send(['customer_phone' => $phone])
                ->assertCreated()
                ->assertJsonPath('order.customer_phone', '0470 12 34 56');
        }

        $this->assertSame(count($vormen), Order::query()->where('customer_phone', '0470123456')->count());
    }

    public function test_onbekend_product_wordt_geweigerd(): void
    {
        $response = $this->send(['lines' => [
            ['product_id' => $this->friet->id, 'quantity' => 1],
            ['product_id' => 999999, 'quantity' => 1],
        ]])->assertUnprocessable();

        $this->assertError($response, 'lines.1.product_id', 'Dit product bestaat niet meer.');

        $this->send(['lines' => [['product_id' => 'abc', 'quantity' => 1]]])->assertUnprocessable();
        $this->send(['lines' => [['quantity' => 1]]])->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_uitverkocht_product_wordt_geweigerd_met_de_naam_in_de_boodschap(): void
    {
        $this->friet->update(['is_sold_out' => true]);

        $response = $this->send()->assertUnprocessable();

        $this->assertError($response, 'lines.0.product_id', 'Grote friet is uitverkocht.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_aantal_nul_negatief_of_geen_geheel_getal_wordt_geweigerd(): void
    {
        foreach ([0, -1, -20, 1.5, '1,5', 'twee', null, ''] as $quantity) {
            $response = $this->send(['lines' => [['product_id' => $this->friet->id, 'quantity' => $quantity]]])
                ->assertUnprocessable();

            $errors = $response->json('errors');
            $this->assertArrayHasKey('lines.0.quantity', $errors, 'Geen fout op aantal '.var_export($quantity, true));
        }

        $this->send(['lines' => [['product_id' => $this->friet->id, 'quantity' => 21]]])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_totaal_en_prijzen_van_de_klant_worden_genegeerd(): void
    {
        $response = $this->send([
            'total_cents' => 1,
            'total' => '€ 0,01',
            'lines' => [
                ['product_id' => $this->friet->id, 'quantity' => 3, 'unit_price_cents' => 1, 'line_total_cents' => 1, 'price_cents' => 1],
                ['product_id' => $this->mayo->id, 'quantity' => 2, 'unit_price_cents' => 0],
            ],
        ])->assertCreated();

        $response
            ->assertJsonPath('order.total_cents', 3 * 420 + 2 * 90)
            ->assertJsonPath('order.total', '€ 14,40')
            ->assertJsonPath('order.lines.0.unit_price_cents', 420)
            ->assertJsonPath('order.lines.0.line_total_cents', 1260)
            ->assertJsonPath('order.lines.1.unit_price_cents', 90)
            ->assertJsonPath('order.lines.1.line_total_cents', 180);

        $this->assertDatabaseHas('orders', ['id' => $response->json('order.id'), 'total_cents' => 1440]);
    }

    public function test_te_veel_regels_of_dubbele_producten_worden_geweigerd(): void
    {
        $products = Product::factory()->count(31)->create();

        $this->send(['lines' => $products->map(fn (Product $p) => ['product_id' => $p->id, 'quantity' => 1])->all()])
            ->assertUnprocessable()
            ->assertJsonPath('errors.lines.0', 'Maximum 30 verschillende producten per bestelling.');

        $response = $this->send(['lines' => [
            ['product_id' => $this->friet->id, 'quantity' => 1],
            ['product_id' => $this->friet->id, 'quantity' => 2],
        ]])->assertUnprocessable();

        $this->assertError($response, 'lines.0.product_id', 'Dit product staat twee keer in je mandje.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_lines_als_object_in_plaats_van_lijst(): void
    {
        // Een object met sleutels is ook "array" voor Laravel; het moet ofwel netjes werken ofwel netjes falen.
        $response = $this->send(['lines' => ['a' => ['product_id' => $this->friet->id, 'quantity' => 2]]]);

        $this->assertContains($response->getStatusCode(), [201, 422]);

        if ($response->getStatusCode() === 201) {
            $response->assertJsonPath('order.total_cents', 840)->assertJsonCount(1, 'order.lines');
        }
    }

    public function test_gesloten_bestellen_geeft_422_met_de_boodschap_ook_bij_geldige_invoer(): void
    {
        Setting::query()->update(['is_open' => false, 'closed_message' => 'Te druk, bel ons op 056 00 00 00']);

        $this->send()
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Bestellen is momenteel gesloten.')
            ->assertJsonPath('errors.ordering.0', 'Te druk, bel ons op 056 00 00 00');

        // Zonder boodschap valt hij terug op de vaste tekst, nooit op null.
        Setting::query()->update(['closed_message' => null]);
        $this->send()->assertUnprocessable()->assertJsonPath('errors.ordering.0', 'Bestellen is momenteel gesloten.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_rate_limit_geeft_429_met_retry_after_en_nederlandse_boodschap(): void
    {
        for ($i = 0; $i < OrderRateLimiter::PER_PHONE; $i++) {
            $this->send()->assertCreated();
        }

        $response = $this->send()->assertStatus(429);

        $response
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', 'Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.')
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertIsInt($response->json('retry_after'));
        $this->assertStringNotContainsStringIgnoringCase('too many', (string) $response->getContent());
        $this->assertSame(OrderRateLimiter::PER_PHONE, Order::query()->count());
    }

    public function test_een_hele_klas_op_een_ip_kan_bestellen(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->send(['customer_phone' => sprintf('047%07d', $i)])->assertCreated();
        }

        $this->assertSame(30, Order::query()->count());
    }

    public function test_bij_meerdere_fouten_is_ook_de_samenvatting_nederlands(): void
    {
        $response = $this->send(['customer_name' => 'J', 'customer_phone' => 'x', 'lines' => []])
            ->assertUnprocessable();

        $message = (string) $response->json('message');

        $this->assertStringNotContainsString('more error', $message, 'Engelse samenvatting in 422-message: '.$message);
        $this->assertStringNotContainsString('given data', $message);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jef',
            'customer_phone' => '0470 12 34 56',
            'lines' => [['product_id' => $this->friet->id, 'quantity' => 1]],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function send(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/orders', $this->valid($overrides));
    }

    private function assertError(TestResponse $response, string $key, string $message): void
    {
        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey($key, $errors, 'Geen fout op '.$key.': '.json_encode(array_keys($errors)));
        $this->assertSame($message, $errors[$key][0]);
    }
}
