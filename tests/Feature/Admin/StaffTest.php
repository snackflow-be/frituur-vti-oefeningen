<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();
        $this->staff = User::factory()->create(['name' => 'Baas']);
    }

    public function test_index_lists_staff_and_marks_current_user(): void
    {
        User::factory()->create(['name' => 'An']);

        $this->actingAs($this->staff)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/staff/index')
                ->has('staff', 2)
                ->where('staff.0.name', 'An')
                ->where('currentUserId', $this->staff->id));
    }

    public function test_staff_can_be_invited_with_a_temporary_password_and_log_in(): void
    {
        $this->actingAs($this->staff)
            ->post(route('admin.staff.store'), [
                'name' => 'Jef',
                'email' => 'jef@frituurvti.be',
                'password' => 'tijdelijk123',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $jef = User::query()->where('email', 'jef@frituurvti.be')->firstOrFail();
        $this->assertNotNull($jef->email_verified_at);
        $this->assertTrue(Hash::check('tijdelijk123', $jef->password));

        auth()->logout();
        $this->flushSession();

        $this->post(route('login.store'), ['email' => 'jef@frituurvti.be', 'password' => 'tijdelijk123'])
            ->assertRedirect('/keuken');
    }

    public function test_duplicate_email_and_short_password_are_refused_in_dutch(): void
    {
        $this->actingAs($this->staff)
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                'name' => 'Jef',
                'email' => $this->staff->email,
                'password' => 'kort',
            ])
            ->assertSessionHasErrors([
                'email' => 'Er is al een personeelslid met dit e-mailadres.',
                'password' => 'Het wachtwoord moet minstens 8 tekens zijn.',
            ]);
    }

    public function test_the_password_policy_follows_password_defaults(): void
    {
        // In productie stelt AppServiceProvider 12 tekens, hoofd- en kleine letters, cijfers en symbolen in.
        // Hier zetten we een strengere policy en verwachten Nederlandse meldingen uit lang/nl/validation.php.
        Password::defaults(fn (): Password => Password::min(12)->mixedCase()->numbers());
        $colleague = User::factory()->create();

        $this->actingAs($this->staff)
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), ['name' => 'Jef', 'email' => 'jef@frituurvti.be', 'password' => 'kort123'])
            ->assertSessionHasErrors([
                'password' => 'Het wachtwoord moet minstens 12 tekens zijn.',
            ]);

        $errors = session('errors')?->get('password') ?? [];
        $this->assertContains('Het wachtwoord moet minstens één hoofdletter en één kleine letter bevatten.', $errors);
        $this->assertDatabaseMissing('users', ['email' => 'jef@frituurvti.be']);

        $this->actingAs($this->staff)
            ->from(route('admin.staff.index'))
            ->put(route('admin.staff.password', $colleague), ['password' => 'kort123'])
            ->assertSessionHasErrors([
                'password' => 'Het wachtwoord moet minstens 12 tekens zijn.',
            ]);
        $this->assertFalse(Hash::check('kort123', (string) $colleague->fresh()?->password));

        $this->actingAs($this->staff)
            ->put(route('admin.staff.password', $colleague), ['password' => 'Nieuw wachtwoord 2026'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nieuw wachtwoord 2026', (string) $colleague->fresh()?->password));
    }

    public function test_password_can_be_reset_for_a_colleague(): void
    {
        $colleague = User::factory()->create();

        $this->actingAs($this->staff)
            ->put(route('admin.staff.password', $colleague), ['password' => 'nieuwwachtwoord'])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nieuwwachtwoord', (string) $colleague->fresh()?->password));
    }

    public function test_staff_can_be_removed_but_not_yourself_or_the_last_one(): void
    {
        $colleague = User::factory()->create();

        $this->actingAs($this->staff)
            ->from(route('admin.staff.index'))
            ->delete(route('admin.staff.destroy', $this->staff))
            ->assertSessionHasErrors(['staff' => 'Je kunt jezelf niet verwijderen.']);

        $this->assertModelExists($this->staff);

        $this->actingAs($this->staff)
            ->delete(route('admin.staff.destroy', $colleague))
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($colleague);

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->staff->delete();

        $this->actingAs($other)
            ->from(route('admin.staff.index'))
            ->delete(route('admin.staff.destroy', $other))
            ->assertSessionHasErrors('staff');
    }
}
