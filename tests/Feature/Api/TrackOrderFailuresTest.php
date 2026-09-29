<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderFailuresTest extends TestCase
{
    use RefreshDatabase;

    public function test_onbekend_bestelnummer_of_token_geeft_404_in_het_nederlands(): void
    {
        $order = Order::factory()->create();

        foreach (['VTI-999', (string) $order->number, 'onbekend', '0', str_repeat('a', 24)] as $token) {
            $response = $this->getJson('/api/orders/'.$token)->assertNotFound();

            $message = (string) $response->json('message');
            $this->assertNotSame('', $message);
            $this->assertStringNotContainsStringIgnoringCase('not found', $message);
            $this->assertStringNotContainsStringIgnoringCase('No query results', $message);
        }
    }

    public function test_een_token_met_streepjes_of_te_lang_geeft_ook_een_nederlandse_404(): void
    {
        // Sinds de fixronde (F3) heeft de API-route geen constraint meer: `-`, `_` en te lange waarden
        // krijgen dezelfde boodschap als een onbekende token, gelijk aan de webroutes.
        foreach (['VOORBEELD-TOKEN', 'a_b', str_repeat('a', 65)] as $token) {
            $this->getJson('/api/orders/'.$token)
                ->assertNotFound()
                ->assertExactJson(['message' => 'Bestelling niet gevonden.']);
        }
    }

    public function test_de_opvolgpagina_van_een_onbekend_nummer_blijft_een_gewone_pagina(): void
    {
        // De klant krijgt de lege staat in de pagina (frontend), niet een 404 van de server.
        $this->get('/bestelling/VOORBEELD-TOKEN')->assertOk();
        $this->get('/bevestiging/VOORBEELD-TOKEN')->assertOk();
    }
}
