<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bestellen open/dicht vanuit keuken of admin. Sluiten kan enkel met een boodschap voor de klant:
 * meegegeven in dit verzoek of al eerder ingesteld.
 */
class UpdateOrderingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_open' => ['required', 'boolean'],
            'closed_message' => ['nullable', 'string', 'max:140'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_open.required' => 'Geef mee of bestellen open of dicht is.',
            'is_open.boolean' => 'Bestellen moet open of dicht zijn.',
            'closed_message.string' => 'De boodschap moet tekst zijn.',
            'closed_message.max' => 'De boodschap mag maximaal 140 tekens zijn.',
        ];
    }
}
