<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleProductRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_visible' => ['sometimes', 'required', 'boolean'],
            'is_sold_out' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_visible.required' => 'Zichtbaar moet aan of uit zijn.',
            'is_visible.boolean' => 'Zichtbaar moet aan of uit zijn.',
            'is_sold_out.required' => 'Uitverkocht moet aan of uit zijn.',
            'is_sold_out.boolean' => 'Uitverkocht moet aan of uit zijn.',
        ];
    }
}
