<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

/**
 * Eigen Nederlandse meldingen voor 401/403/404/429, nooit de Engelse standaardtekst van Laravel.
 */
class ErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_test/verboden', fn () => abort(403))->name('_test.verboden');
        Route::middleware('web')->get('/_test/verboden-engels', fn () => abort(403, 'This action is unauthorized.'));
        Route::middleware(['web', 'auth'])->get('/_test/beveiligd', fn () => 'ok');
        Route::middleware('web')->get('/_test/kapot', fn () => throw new RuntimeException('Geheim detail uit de code'));
        Route::middleware('web')->get('/_test/onderhoud', fn () => abort(503, 'Service Unavailable', ['Retry-After' => '120']));
    }

    public function test_onbekende_api_route_geeft_nederlandse_404(): void
    {
        $this->getJson('/api/bestaat-niet')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Niet gevonden.']);
    }

    public function test_zonder_login_geeft_json_401(): void
    {
        $this->getJson('/_test/beveiligd')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Hier moet je voor inloggen.']);
    }

    public function test_zonder_login_gaat_de_browser_naar_de_loginpagina(): void
    {
        $this->get('/_test/beveiligd')->assertRedirect('/login');
    }

    public function test_verboden_geeft_json_403(): void
    {
        $this->getJson('/_test/verboden')->assertForbidden()->assertExactJson(['message' => 'Dit mag je niet.']);
        $this->getJson('/_test/verboden-engels')->assertForbidden()->assertExactJson(['message' => 'Dit mag je niet.']);
    }

    public function test_de_browser_krijgt_een_nederlandse_foutpagina(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->withoutVite()->get('/_test/verboden')
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('fout')
                ->where('status', 403)
                ->where('message', 'Dit mag je niet.'));

        $this->withoutVite()->get('/pagina-bestaat-niet')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('fout')
                ->where('status', 404)
                ->where('message', 'Niet gevonden.'));
    }

    public function test_een_serverfout_en_onderhoud_zijn_nederlands_zonder_details(): void
    {
        config(['app.debug' => false, 'inertia.testing.ensure_pages_exist' => false]);

        $this->getJson('/_test/kapot')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Er ging iets mis. Probeer opnieuw of bestel aan de toog.']);

        $this->getJson('/_test/onderhoud')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '120')
            ->assertExactJson(['message' => 'Even in onderhoud. Probeer over een paar minuten opnieuw.']);

        $this->withoutVite()->get('/_test/kapot')
            ->assertStatus(500)
            ->assertDontSee('Geheim detail')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('fout')
                ->where('status', 500)
                ->where('message', 'Er ging iets mis. Probeer opnieuw of bestel aan de toog.'));

        $this->withoutVite()->get('/_test/onderhoud')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '120')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('fout')
                ->where('status', 503)
                ->where('message', 'Even in onderhoud. Probeer over een paar minuten opnieuw.'));
    }

    public function test_met_debug_aan_blijft_de_stacktrace_van_laravel_zichtbaar(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/_test/kapot')
            ->assertStatus(500)
            ->assertJsonPath('message', 'Geheim detail uit de code');
    }

    public function test_de_app_spreekt_nederlands_in_brusselse_tijd(): void
    {
        $this->assertSame('nl', config('app.locale'));
        $this->assertSame('Europe/Brussels', config('app.timezone'));
        $this->assertSame('Deze gegevens kloppen niet. Controleer je e-mailadres en wachtwoord.', __('auth.failed'));
    }
}
