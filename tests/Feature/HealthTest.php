<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * De uitrol leunt op /health: klopt de release en bereikt de app haar
 * database? Zo niet, dan wordt de vorige release teruggezet.
 */
class HealthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(base_path('RELEASE'));

        parent::tearDown();
    }

    public function test_zonder_releasebestand_meldt_hij_dev(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'release' => 'dev', 'checks' => ['database' => 'ok']])
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_hij_toont_de_release_die_de_uitrol_wegschreef(): void
    {
        File::put(base_path('RELEASE'), "20260923-abc1234\n");

        $this->getJson('/health')->assertOk()->assertJsonPath('release', '20260923-abc1234');
    }

    public function test_zonder_database_geeft_hij_503(): void
    {
        $standaard = config('database.default');
        config([
            'database.connections.onbereikbaar' => ['driver' => 'sqlite', 'database' => '/pad/bestaat/niet.sqlite', 'prefix' => ''],
            'database.default' => 'onbereikbaar',
        ]);

        $antwoord = $this->getJson('/health');

        // Terugzetten vóór tearDown: RefreshDatabase rolt de transactie terug
        // op de standaardverbinding.
        config(['database.default' => $standaard]);

        $antwoord->assertStatus(503)->assertJsonPath('checks.database', 'fout');
    }

    public function test_hij_zet_geen_cookies(): void
    {
        $this->get('/health')->assertCookieMissing(config('session.cookie'));
    }
}
