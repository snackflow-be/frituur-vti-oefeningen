<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    /**
     * @return array<string, array<int, Password|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults(), 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Geef een naam op.',
            'name.min' => 'De naam moet minstens 2 tekens zijn.',
            'name.max' => 'De naam mag maximaal 60 tekens zijn.',
            'email.required' => 'Geef een e-mailadres op.',
            'email.email' => 'Geef een geldig e-mailadres op.',
            'email.max' => 'Het e-mailadres is te lang.',
            'email.unique' => 'Er is al een personeelslid met dit e-mailadres.',
            'password.required' => 'Geef een tijdelijk wachtwoord op.',
            'password.min' => 'Het wachtwoord moet minstens :min tekens zijn.',
            'password.max' => 'Het wachtwoord is te lang.',
        ];
    }
}
