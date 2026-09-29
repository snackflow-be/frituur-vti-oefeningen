<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => 'Frituur VTI',
            'address' => 'Toekomststraat 75, 8790 Waregem',
            'phone' => '056 00 00 00',
            'opening_hours' => self::defaultOpeningHours(),
            'is_open' => true,
            'closed_message' => null,
        ];
    }

    public function closed(string $message = 'Vandaag gesloten'): static
    {
        return $this->state(fn () => [
            'is_open' => false,
            'closed_message' => $message,
        ]);
    }

    /**
     * Ma–za 11:30–14:00 en 17:00–22:00, zo 17:00–22:00.
     *
     * @return list<array{day: int, slots: list<array{from: string, to: string}>}>
     */
    public static function defaultOpeningHours(): array
    {
        $noon = ['from' => '11:30', 'to' => '14:00'];
        $evening = ['from' => '17:00', 'to' => '22:00'];

        return array_map(
            fn (int $day) => ['day' => $day, 'slots' => $day === 7 ? [$evening] : [$noon, $evening]],
            range(1, 7),
        );
    }
}
