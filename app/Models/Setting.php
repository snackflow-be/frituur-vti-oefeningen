<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exact één rij: gegevens van de zaak, openingsuren en bestellen open/dicht.
 *
 * `opening_hours` = lijst van 7 dagen: [{"day": 1, "slots": [{"from": "11:30", "to": "14:00"}]}, …]
 * (day 1 = maandag … 7 = zondag; lege slots = gesloten; max. 2 slots; HH:MM).
 *
 * @property int $id
 * @property string $business_name
 * @property string $address
 * @property string $phone
 * @property list<array{day: int, slots: list<array{from: string, to: string}>}> $opening_hours
 * @property bool $is_open
 * @property string|null $closed_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'business_name',
    'address',
    'phone',
    'opening_hours',
    'is_open',
    'closed_message',
])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * Nederlandse dagnamen, sleutel = ISO-weekdag (1 = maandag).
     *
     * @var array<int, string>
     */
    public const array DAY_LABELS = [
        1 => 'maandag',
        2 => 'dinsdag',
        3 => 'woensdag',
        4 => 'donderdag',
        5 => 'vrijdag',
        6 => 'zaterdag',
        7 => 'zondag',
    ];

    /**
     * De ene rij met instellingen (de seeder maakt ze aan).
     */
    public static function current(): self
    {
        return self::query()->orderBy('id')->firstOrFail();
    }

    /**
     * Zeven dagen zonder openingsuren (allemaal gesloten).
     *
     * @return list<array{day: int, slots: list<array{from: string, to: string}>}>
     */
    public static function closedAllWeek(): array
    {
        return array_map(fn (int $day) => ['day' => $day, 'slots' => []], range(1, 7));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'is_open' => 'boolean',
        ];
    }
}
