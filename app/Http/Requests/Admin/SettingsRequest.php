<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Gegevens van de zaak en openingsuren: 7 dagen, elk 0 tot 2 blokken "van–tot" (HH:MM).
 */
class SettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:2', 'max:60'],
            'address' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'opening_hours' => ['required', 'array', 'size:7'],
            'opening_hours.*.day' => ['required', 'integer', 'between:1,7'],
            'opening_hours.*.slots' => ['present', 'array', 'max:2'],
            'opening_hours.*.slots.*.from' => ['required', 'date_format:H:i'],
            'opening_hours.*.slots.*.to' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'Geef de naam van de zaak op.',
            'business_name.min' => 'De naam moet minstens 2 tekens zijn.',
            'business_name.max' => 'De naam mag maximaal 60 tekens zijn.',
            'address.required' => 'Geef het adres op.',
            'address.max' => 'Het adres mag maximaal 120 tekens zijn.',
            'phone.required' => 'Geef het telefoonnummer op.',
            'phone.max' => 'Het telefoonnummer mag maximaal 20 tekens zijn.',
            'opening_hours.required' => 'Geef de openingsuren voor de 7 dagen op.',
            'opening_hours.array' => 'Geef de openingsuren voor de 7 dagen op.',
            'opening_hours.size' => 'Geef de openingsuren voor de 7 dagen op.',
            'opening_hours.*.day.required' => 'Elke dag moet een dagnummer hebben.',
            'opening_hours.*.day.integer' => 'Elke dag moet een dagnummer hebben.',
            'opening_hours.*.day.between' => 'Het dagnummer moet tussen 1 en 7 liggen.',
            'opening_hours.*.slots.present' => 'Geef per dag de blokken op (leeg = gesloten).',
            'opening_hours.*.slots.array' => 'Geef per dag de blokken op (leeg = gesloten).',
            'opening_hours.*.slots.max' => 'Maximaal 2 blokken per dag.',
            'opening_hours.*.slots.*.from.required' => 'Vul een beginuur in (bv. 11:30).',
            'opening_hours.*.slots.*.from.date_format' => 'Het beginuur moet uu:mm zijn (bv. 11:30).',
            'opening_hours.*.slots.*.to.required' => 'Vul een einduur in (bv. 14:00).',
            'opening_hours.*.slots.*.to.date_format' => 'Het einduur moet uu:mm zijn (bv. 14:00).',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $days = $this->input('opening_hours');

                if (! is_array($days)) {
                    return;
                }

                foreach ($days as $i => $day) {
                    $slots = is_array($day) && isset($day['slots']) && is_array($day['slots']) ? $day['slots'] : [];

                    foreach ($slots as $j => $slot) {
                        $from = is_array($slot) && is_string($slot['from'] ?? null) ? $slot['from'] : '';
                        $to = is_array($slot) && is_string($slot['to'] ?? null) ? $slot['to'] : '';

                        if ($from !== '' && $to !== '' && $from >= $to) {
                            $validator->errors()->add("opening_hours.{$i}.slots.{$j}.to", 'Het einduur moet na het beginuur liggen.');
                        }
                    }
                }
            },
        ];
    }

    /**
     * Genormaliseerde openingsuren, gesorteerd op dag 1..7.
     *
     * @return list<array{day: int, slots: list<array{from: string, to: string}>}>
     */
    public function openingHours(): array
    {
        /** @var array<int, array{day: int|string, slots: array<int, array{from: string, to: string}>}> $days */
        $days = $this->validated('opening_hours');

        $result = [];

        foreach ($days as $day) {
            $slots = [];

            foreach ($day['slots'] as $slot) {
                $slots[] = ['from' => $slot['from'], 'to' => $slot['to']];
            }

            $result[(int) $day['day']] = ['day' => (int) $day['day'], 'slots' => $slots];
        }

        ksort($result);

        return array_values($result);
    }
}
