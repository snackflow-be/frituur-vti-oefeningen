<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSoldOutRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_sold_out' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_sold_out.required' => 'Geef mee of het product uitverkocht is.',
            'is_sold_out.boolean' => 'Uitverkocht moet aan of uit zijn.',
        ];
    }
}
