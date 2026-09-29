<?php

namespace Database\Seeders;

use App\Models\Setting;
use Database\Factories\SettingFactory;
use Illuminate\Database\Seeder;

/**
 * De ene rij met instellingen (fictieve gegevens uit docs/run/01-analist.md). Idempotent.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (Setting::query()->exists()) {
            return;
        }

        Setting::query()->create([
            'business_name' => 'Frituur VTI',
            'address' => 'Toekomststraat 75, 8790 Waregem',
            'phone' => '056 00 00 00',
            'opening_hours' => SettingFactory::defaultOpeningHours(),
            'is_open' => true,
            'closed_message' => null,
        ]);
    }
}
