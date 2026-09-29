<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $ignore = $category instanceof Category ? $category->id : null;

        return [
            'name' => ['required', 'string', 'min:2', 'max:40', Rule::unique('categories', 'name')->ignore($ignore)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Geef de categorie een naam.',
            'name.min' => 'De naam moet minstens 2 tekens zijn.',
            'name.max' => 'De naam mag maximaal 40 tekens zijn.',
            'name.unique' => 'Er bestaat al een categorie met die naam.',
        ];
    }
}
