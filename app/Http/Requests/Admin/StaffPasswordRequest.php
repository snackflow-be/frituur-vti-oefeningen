<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StaffPasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, Password|string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults(), 'max:72'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Geef een nieuw wachtwoord op.',
            'password.min' => 'Het wachtwoord moet minstens :min tekens zijn.',
            'password.max' => 'Het wachtwoord is te lang.',
        ];
    }
}
