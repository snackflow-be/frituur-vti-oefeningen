<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Menu, instellingen en keukengebruiker: in elke omgeving (staging doet migrate:fresh --seed).
        $this->call([
            MenuSeeder::class,
            SettingsSeeder::class,
            KitchenUserSeeder::class,
        ]);

        // Staging: vaste beheerder met wachtwoord uit de .env, geen testgebruikers.
        if (app()->environment('staging')) {
            $this->call(StagingSeeder::class);

            return;
        }

        // Productie: nooit testgebruikers aanmaken.
        if (app()->environment('production')) {
            return;
        }

        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
