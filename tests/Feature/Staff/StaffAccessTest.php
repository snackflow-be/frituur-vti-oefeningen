<?php

namespace Tests\Feature\Staff;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keuken en admin zonder login: JSON → 401, browser → naar /login. Nooit een Engelse tekst,
 * nooit een wijziging in de databank.
 */
class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private Product $product;

    private Category $category;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();
        $this->order = Order::factory()->withLines(1)->create();
        $this->product = Product::factory()->create();
        $this->category = $this->product->category;
        $this->user = User::factory()->create();
    }

    /**
     * @return list<array{0: string, 1: string, 2?: array<string, mixed>}>
     */
    private function staffRoutes(): array
    {
        return [
            ['GET', '/keuken'],
            ['GET', '/keuken/bestellingen'],
            ['PATCH', "/keuken/bestellingen/{$this->order->id}/status", ['status' => 'bezig']],
            ['GET', '/keuken/producten'],
            ['PATCH', "/keuken/producten/{$this->product->id}/uitverkocht", ['is_sold_out' => true]],
            ['PATCH', '/keuken/bestellen', ['is_open' => false, 'closed_message' => 'dicht']],
            ['GET', '/admin'],
            ['GET', '/admin/bestellingen'],
            ['GET', "/admin/bestellingen/{$this->order->id}"],
            ['PATCH', "/admin/bestellingen/{$this->order->id}/status", ['status' => 'bezig']],
            ['GET', '/admin/cijfers'],
            ['GET', '/admin/producten'],
            ['GET', '/admin/producten/nieuw'],
            ['POST', '/admin/producten', ['name' => 'Hack', 'price' => '1,00', 'category_id' => $this->category->id]],
            ['GET', "/admin/producten/{$this->product->id}/bewerken"],
            ['PUT', "/admin/producten/{$this->product->id}", ['name' => 'Hack', 'price' => '1,00', 'category_id' => $this->category->id]],
            ['DELETE', "/admin/producten/{$this->product->id}"],
            ['PATCH', "/admin/producten/{$this->product->id}/volgorde", ['direction' => 'up']],
            ['PATCH', "/admin/producten/{$this->product->id}/schakelaars", ['is_sold_out' => true]],
            ['GET', '/admin/categorieen'],
            ['POST', '/admin/categorieen', ['name' => 'Hack']],
            ['PUT', "/admin/categorieen/{$this->category->id}", ['name' => 'Hack']],
            ['DELETE', "/admin/categorieen/{$this->category->id}"],
            ['PATCH', "/admin/categorieen/{$this->category->id}/volgorde", ['direction' => 'up']],
            ['GET', '/admin/bestellen'],
            ['PUT', '/admin/bestellen', ['is_open' => false, 'closed_message' => 'dicht']],
            ['GET', '/admin/instellingen'],
            ['PUT', '/admin/instellingen', ['business_name' => 'Hack']],
            ['GET', '/admin/personeel'],
            ['POST', '/admin/personeel', ['name' => 'Hack', 'email' => 'hack@example.com', 'password' => 'wachtwoord123']],
            ['PUT', "/admin/personeel/{$this->user->id}/wachtwoord", ['password' => 'wachtwoord123']],
            ['DELETE', "/admin/personeel/{$this->user->id}"],
        ];
    }

    public function test_alle_keuken_en_adminroutes_geven_json_401_zonder_login(): void
    {
        foreach ($this->staffRoutes() as $route) {
            [$method, $uri] = $route;
            $response = $this->json($method, $uri, $route[2] ?? []);

            $this->assertContains($response->getStatusCode(), [401, 403], "$method $uri gaf {$response->getStatusCode()}");
            $this->assertSame('Hier moet je voor inloggen.', $response->json('message'), "$method $uri");
        }

        // Niets gewijzigd.
        $this->assertSame('nieuw', $this->order->fresh()?->status->value);
        $this->assertFalse($this->product->fresh()?->is_sold_out);
        $this->assertTrue(Setting::current()->is_open);
        $this->assertDatabaseMissing('products', ['name' => 'Hack']);
        $this->assertDatabaseMissing('categories', ['name' => 'Hack']);
        $this->assertDatabaseMissing('users', ['email' => 'hack@example.com']);
        $this->assertModelExists($this->user);
        $this->assertModelExists($this->product);
    }

    public function test_de_browser_gaat_zonder_login_naar_de_loginpagina(): void
    {
        foreach (['/keuken', '/admin', '/admin/bestellingen', '/admin/producten', '/admin/cijfers', '/admin/personeel', '/admin/instellingen', '/admin/bestellen', '/admin/categorieen'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_verboden_statusovergang_geeft_422_met_de_nederlandse_boodschap(): void
    {
        $staff = User::factory()->create();

        // Overslaan
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$this->order->id}/status", ['status' => 'klaar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', "Deze bestelling staat op 'Nieuw'; enkel de stap naar 'Bezig' kan.");

        // Terug
        $klaar = Order::factory()->klaar()->create();
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$klaar->id}/status", ['status' => 'bezig'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', "Deze bestelling staat op 'Klaar'; enkel de stap naar 'Afgehaald' kan.");

        // Dezelfde status
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$klaar->id}/status", ['status' => 'klaar'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        // Na afgehaald
        $af = Order::factory()->afgehaald()->create();
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$af->id}/status", ['status' => 'afgehaald'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', "Deze bestelling staat op 'Afgehaald'; er is geen volgende stap meer.");

        // Onbekende status
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$this->order->id}/status", ['status' => 'klaargemaakt'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Onbekende status.');

        // Ook via admin (Inertia): fout op sleutel status.
        $this->actingAs($staff)
            ->from("/admin/bestellingen/{$klaar->id}")
            ->patch("/admin/bestellingen/{$klaar->id}/status", ['status' => 'nieuw'])
            ->assertSessionHasErrors('status');

        $this->assertSame('nieuw', $this->order->fresh()?->status->value);
        $this->assertSame('klaar', $klaar->fresh()?->status->value);
    }

    public function test_onbekende_bestelling_in_de_keuken_geeft_nederlandse_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->patchJson('/keuken/bestellingen/999999/status', ['status' => 'bezig'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Niet gevonden.']);
    }

    public function test_twee_toestellen_die_dezelfde_stap_zetten_geven_een_keer_422(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$this->order->id}/status", ['status' => 'bezig'])
            ->assertOk();

        // Het tweede toestel had het bord nog niet ververst en drukt ook op "Start".
        $this->actingAs($staff)
            ->patchJson("/keuken/bestellingen/{$this->order->id}/status", ['status' => 'bezig'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame('bezig', $this->order->fresh()?->status->value);
    }
}
