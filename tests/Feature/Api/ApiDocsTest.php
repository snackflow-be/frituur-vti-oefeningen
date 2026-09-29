<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function test_de_documentatie_toont_elke_route_met_een_curl_voorbeeld(): void
    {
        $response = $this->get('/api/docs')->assertOk();

        foreach (['GET', 'POST'] as $method) {
            $response->assertSee($method);
        }

        foreach (['/api/menu', '/api/orders', '/api/orders/{token}', '/api/docs', '/keuken/bestellingen'] as $path) {
            $response->assertSee($path);
        }

        $response->assertSee('curl -s')
            ->assertSee('VOORBEELD-TOKEN')
            // Geen letterlijke tokenwaarde in een curl-header: gitleaks (CI) ziet dat als een lek.
            ->assertSee('X-XSRF-TOKEN: $XSRF')
            ->assertDontSee('X-XSRF-TOKEN: VOORBEELD-TOKEN')
            ->assertSee('Bestellen is momenteel gesloten.')
            ->assertSee('Retry-After')
            ->assertSee('lang="nl"', false);
    }
}
