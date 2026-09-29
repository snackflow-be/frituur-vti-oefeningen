<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Setting;
use App\Support\OrderRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private Product $friet;

    private Product $kroket;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::factory()->create();
        $this->friet = Product::factory()->create(['name' => 'Grote friet', 'price_cents' => 420]);
        $this->kroket = Product::factory()->soldOut()->create(['name' => 'Kaaskroket', 'price_cents' => 250]);
    }

    public function test_per_gsm_nummer_zijn_drie_bestellingen_per_minuut_toegelaten(): void
    {
        for ($i = 0; $i < OrderRateLimiter::PER_PHONE; $i++) {
            $this->placeOrder('0470 12 34 56')->assertCreated();
        }

        $this->placeOrder('+32 470 12 34 56')
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', 'Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.')
            ->assertJsonPath('retry_after', fn (mixed $value): bool => is_int($value) && $value >= 1 && $value <= 60);

        // Een ander gsm-nummer op hetzelfde IP (de klas) mag nog wel.
        $this->placeOrder('0470 99 99 99')->assertCreated();

        $this->assertDatabaseCount('orders', OrderRateLimiter::PER_PHONE + 1);
    }

    public function test_geweigerde_pogingen_tellen_niet_mee(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->placeOrder('0470 12 34 56', $this->kroket)->assertUnprocessable();
        }

        for ($i = 0; $i < OrderRateLimiter::PER_PHONE; $i++) {
            $this->placeOrder('0470 12 34 56')->assertCreated();
        }
    }

    public function test_per_ip_geldt_een_ruime_limiet_voor_een_hele_klas(): void
    {
        $this->assertGreaterThanOrEqual(30, OrderRateLimiter::PER_IP);

        for ($i = 0; $i < OrderRateLimiter::PER_IP; $i++) {
            RateLimiter::hit(OrderRateLimiter::ipKey('127.0.0.1'), 60);
        }

        $this->placeOrder('0470 12 34 56')->assertStatus(429);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_de_algemene_api_limiet_laat_polling_door(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->getJson('/api/menu')->assertOk();
        }
    }

    private function placeOrder(string $phone, ?Product $product = null): TestResponse
    {
        return $this->postJson('/api/orders', [
            'customer_name' => 'Jef',
            'customer_phone' => $phone,
            'lines' => [['product_id' => ($product ?? $this->friet)->id, 'quantity' => 1]],
        ]);
    }
}
