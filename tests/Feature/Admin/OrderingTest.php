<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderingTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();
        $this->staff = User::factory()->create();
    }

    public function test_page_shows_current_state(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.ordering.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ordering')
                ->where('ordering.is_open', true)
                ->where('ordering.closed_message', null));
    }

    public function test_closing_requires_a_message_and_shares_it_with_every_admin_page(): void
    {
        $this->actingAs($this->staff)
            ->from(route('admin.ordering.edit'))
            ->put(route('admin.ordering.update'), ['is_open' => false, 'closed_message' => ''])
            ->assertSessionHasErrors('closed_message');

        $this->assertTrue(Setting::current()->is_open);

        $this->actingAs($this->staff)
            ->put(route('admin.ordering.update'), ['is_open' => false, 'closed_message' => 'Vandaag gesloten'])
            ->assertRedirect(route('admin.ordering.edit'))
            ->assertSessionHasNoErrors();

        $setting = Setting::current();
        $this->assertFalse($setting->is_open);
        $this->assertSame('Vandaag gesloten', $setting->closed_message);

        $this->actingAs($this->staff)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ordering.is_open', false)
                ->where('ordering.closed_message', 'Vandaag gesloten'));

        $this->actingAs($this->staff)
            ->put(route('admin.ordering.update'), ['is_open' => true, 'closed_message' => 'Vandaag gesloten'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Setting::current()->is_open);
        $this->assertSame('Vandaag gesloten', Setting::current()->closed_message);
    }
}
