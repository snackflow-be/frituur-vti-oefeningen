<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Eén keukengebruiker voor de demo. Wachtwoord volgens de staging-conventie van het template:
 * STAGING_SEED_PASSWORD uit de .env (nooit in Git). Enkel lokaal en in tests valt hij terug op het
 * factory-wachtwoord van het template.
 */
class KitchenUserSeeder extends Seeder
{
    public const string EMAIL = 'keuken@frituurvti.be';

    public function run(): void
    {
        $password = (string) config('app.staging_seed_password', '');

        if (strlen($password) < 12) {
            if (! app()->environment(['local', 'testing'])) {
                throw new RuntimeException('STAGING_SEED_PASSWORD ontbreekt of is korter dan 12 tekens.');
            }

            $password = 'password';
        }

        User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Keuken',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
