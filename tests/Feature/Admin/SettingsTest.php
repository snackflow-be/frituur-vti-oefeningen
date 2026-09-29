<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
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

    public function test_page_shows_settings_with_seven_labelled_days(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/settings')
                ->where('settings.business_name', 'Frituur VTI')
                ->has('settings.opening_hours', 7)
                ->where('settings.opening_hours.0.label', 'maandag')
                ->has('settings.opening_hours.0.slots', 2)
                ->has('settings.opening_hours.6.slots', 1));
    }

    public function test_settings_and_opening_hours_can_be_updated(): void
    {
        $hours = array_map(fn (int $day) => [
            'day' => $day,
            'slots' => $day === 3 ? [] : [['from' => '11:00', 'to' => '14:00'], ['from' => '17:30', 'to' => '22:00']],
        ], range(1, 7));

        $this->actingAs($this->staff)
            ->put(route('admin.settings.update'), [
                'business_name' => 'Frituur VTI Waregem',
                'address' => 'Toekomststraat 75, 8790 Waregem',
                'phone' => '056 11 22 33',
                'opening_hours' => $hours,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $setting = Setting::current();
        $this->assertSame('Frituur VTI Waregem', $setting->business_name);
        $this->assertSame('056 11 22 33', $setting->phone);
        $this->assertCount(7, $setting->opening_hours);
        $this->assertSame([], $setting->opening_hours[2]['slots']);
        $this->assertSame('17:30', $setting->opening_hours[0]['slots'][1]['from']);
    }

    public function test_invalid_hours_are_refused_in_dutch(): void
    {
        $hours = array_map(fn (int $day) => [
            'day' => $day,
            'slots' => $day === 1 ? [['from' => '14:00', 'to' => '11:00']] : [],
        ], range(1, 7));

        $this->actingAs($this->staff)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'business_name' => '',
                'address' => 'x',
                'phone' => '1',
                'opening_hours' => $hours,
            ])
            ->assertSessionHasErrors([
                'business_name' => 'Geef de naam van de zaak op.',
                'opening_hours.0.slots.0.to' => 'Het einduur moet na het beginuur liggen.',
            ]);
    }
}
