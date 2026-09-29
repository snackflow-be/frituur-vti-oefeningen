<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Testdata voor staging (<klant>.staging.snackflow.be).
 *
 * Draait na elke staging-uitrol (migrate:fresh --seed via DatabaseSeeder).
 * Maakt een beheerder aan met het wachtwoord uit STAGING_SEED_PASSWORD
 * (staat in de .env van staging, niet in Git). Geen echte klantgegevens.
 */
class StagingSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('app.staging_seed_password', '');

        if (strlen($password) < 12) {
            throw new RuntimeException('STAGING_SEED_PASSWORD ontbreekt of is korter dan 12 tekens.');
        }

        User::query()->updateOrCreate(
            ['email' => 'staging-admin@snackflow.be'],
            [
                'name' => 'Beheerder staging',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
