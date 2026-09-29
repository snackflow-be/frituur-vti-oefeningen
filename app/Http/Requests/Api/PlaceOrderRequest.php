<?php

namespace App\Http\Requests\Api;

use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:40'],
            'customer_phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || Phone::normalize($value) === null) {
                    $fail('Vul een geldig Belgisch gsm-nummer in (bv. 0470 12 34 56).');
                }
            }],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Je mandje is leeg.',
            'lines.min' => 'Je mandje is leeg.',
            'lines.max' => 'Maximum 30 verschillende producten per bestelling.',
            'lines.*.product_id.exists' => 'Dit product bestaat niet meer.',
            'lines.*.product_id.distinct' => 'Dit product staat twee keer in je mandje.',
            'lines.*.quantity.max' => 'Maximum 20 stuks per product.',
            'lines.*.quantity.min' => 'Minstens 1 stuk per product.',
        ];
    }

    /**
     * @return list<array{product_id: int, quantity: int}>
     */
    public function lines(): array
    {
        /** @var list<array{product_id: int|string, quantity: int|string}> $lines */
        $lines = $this->validated('lines');

        return array_map(
            fn (array $line): array => ['product_id' => (int) $line['product_id'], 'quantity' => (int) $line['quantity']],
            $lines,
        );
    }
}
