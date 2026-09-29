<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Volgorde: één plaats omhoog of omlaag (product binnen zijn categorie, of categorie).
 */
class MoveRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', 'string', Rule::in(['up', 'down'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'Geef een richting mee (omhoog of omlaag).',
            'direction.string' => 'Geef een richting mee (omhoog of omlaag).',
            'direction.in' => 'De richting moet omhoog of omlaag zijn.',
        ];
    }

    public function isUp(): bool
    {
        return $this->string('direction')->toString() === 'up';
    }
}
