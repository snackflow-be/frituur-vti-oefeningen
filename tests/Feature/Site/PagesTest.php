<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_page_renders(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('menu/index'));
    }

    public function test_cart_page_renders(): void
    {
        $this->get(route('bestellen'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('bestellen/index'));
    }

    public function test_confirmation_page_passes_the_token(): void
    {
        $this->get(route('bevestiging', ['token' => 'VOORBEELD-TOKEN']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bevestiging/index')
                ->where('token', 'VOORBEELD-TOKEN'));
    }

    public function test_tracking_page_passes_the_token(): void
    {
        $this->get(route('opvolgen', ['token' => 'VOORBEELD-TOKEN']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('opvolgen/index')
                ->where('token', 'VOORBEELD-TOKEN'));
    }

    public function test_pages_are_public(): void
    {
        $this->get('/')->assertOk();
        $this->get('/bestellen')->assertOk();
        $this->get('/bestelling/VOORBEELD-TOKEN')->assertOk();
    }

    public function test_public_pages_are_not_indexed(): void
    {
        foreach (['/', '/bestellen', '/bestelling/VOORBEELD-TOKEN', '/bevestiging/VOORBEELD-TOKEN'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex">', false);
        }
    }
}
