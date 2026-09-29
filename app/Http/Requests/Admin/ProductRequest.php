<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Aanmaken en wijzigen van een product. Prijs komt binnen als euro-tekst ("3,50") en wordt centen.
 */
class ProductRequest extends FormRequest
{
    public const int MAX_IMAGE_KB = 2048;

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'description' => ['nullable', 'string', 'max:60'],
            'price' => ['required', 'string', 'max:12'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_IMAGE_KB],
            'remove_image' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'is_sold_out' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Geef het product een naam.',
            'name.min' => 'De naam moet minstens 2 tekens zijn.',
            'name.max' => 'De naam mag maximaal 60 tekens zijn.',
            'description.max' => 'De omschrijving mag maximaal 60 tekens zijn.',
            'price.required' => 'Geef een prijs op, bv. 3,50.',
            'price.max' => 'Geef een geldige prijs op, bv. 3,50.',
            'category_id.required' => 'Kies een categorie.',
            'category_id.integer' => 'Kies een categorie.',
            'category_id.exists' => 'Die categorie bestaat niet (meer).',
            'image.file' => 'De foto kon niet gelezen worden.',
            'image.image' => 'De foto moet een afbeelding zijn (jpg, png of webp).',
            'image.mimes' => 'De foto moet jpg, png of webp zijn.',
            'image.max' => 'De foto mag maximaal 2 MB zijn.',
            'is_visible.boolean' => 'Zichtbaar moet aan of uit zijn.',
            'is_sold_out.boolean' => 'Uitverkocht moet aan of uit zijn.',
            'remove_image.boolean' => 'Foto verwijderen moet aan of uit zijn.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'description' => 'omschrijving',
            'price' => 'prijs',
            'category_id' => 'categorie',
            'image' => 'foto',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('price')) {
                    return;
                }

                $cents = Money::parseEuro($this->string('price')->toString());

                if ($cents === null) {
                    $validator->errors()->add('price', 'Geef een geldige prijs op, bv. 3,50.');
                } elseif ($cents === 0) {
                    $validator->errors()->add('price', 'De prijs moet groter zijn dan € 0,00.');
                }
            },
        ];
    }

    public function priceCents(): int
    {
        return (int) Money::parseEuro($this->string('price')->toString());
    }

    public function uploadedImage(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}
