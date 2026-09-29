<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_het_menu_toont_zaak_bestelstatus_en_producten_in_volgorde(): void
    {
        Setting::factory()->create(['business_name' => 'Frituur VTI', 'phone' => '056 00 00 00']);
        $snacks = Category::factory()->create(['name' => 'Snacks', 'slug' => 'snacks', 'sort_order' => 2]);
        $frieten = Category::factory()->create(['name' => 'Frieten', 'slug' => 'frieten', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Leeg', 'slug' => 'leeg', 'sort_order' => 3]);

        Product::factory()->for($frieten)->create(['name' => 'Grote friet', 'price_cents' => 420, 'sort_order' => 2]);
        Product::factory()->for($frieten)->create(['name' => 'Kleine friet', 'price_cents' => 300, 'sort_order' => 1]);
        Product::factory()->for($snacks)->soldOut()->create(['name' => 'Kaaskroket', 'price_cents' => 250]);
        Product::factory()->for($snacks)->hidden()->create(['name' => 'Verborgen']);

        $response = $this->getJson('/api/menu')->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $response
            ->assertJsonPath('business.name', 'Frituur VTI')
            ->assertJsonPath('business.phone', '056 00 00 00')
            ->assertJsonPath('business.opening_hours.0.label', 'maandag')
            ->assertJsonPath('business.opening_hours.0.slots.0.from', '11:30')
            ->assertJsonPath('ordering.is_open', true)
            ->assertJsonPath('ordering.closed_message', null)
            ->assertJsonCount(2, 'categories')
            ->assertJsonPath('categories.0.slug', 'frieten')
            ->assertJsonPath('categories.0.products.0.name', 'Kleine friet')
            ->assertJsonPath('categories.0.products.1.name', 'Grote friet')
            ->assertJsonPath('categories.0.products.1.price_cents', 420)
            ->assertJsonPath('categories.0.products.1.price', '€ 4,20')
            ->assertJsonPath('categories.0.products.1.is_sold_out', false)
            ->assertJsonPath('categories.1.slug', 'snacks')
            ->assertJsonCount(1, 'categories.1.products')
            ->assertJsonPath('categories.1.products.0.is_sold_out', true)
            ->assertJsonMissing(['name' => 'Verborgen']);
    }

    public function test_gesloten_bestellen_komt_mee_met_de_boodschap(): void
    {
        Setting::factory()->closed('Te druk, bel ons')->create();

        $this->getJson('/api/menu')
            ->assertOk()
            ->assertJsonPath('ordering.is_open', false)
            ->assertJsonPath('ordering.closed_message', 'Te druk, bel ons')
            ->assertJsonPath('categories', []);
    }
}
