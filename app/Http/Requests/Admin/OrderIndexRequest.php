<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Filters van de bestellingenlijst. Standaard: enkel vandaag.
 */
class OrderIndexRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'string', Rule::enum(OrderStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.max' => 'De zoekterm is te lang.',
            'from.date_format' => 'Geef een geldige datum op.',
            'to.date_format' => 'Geef een geldige datum op.',
            'status.enum' => 'Onbekende status.',
        ];
    }

    /**
     * @return array{q: string, from: string, to: string, status: string}
     */
    public function filters(): array
    {
        $today = now()->toDateString();

        return [
            'q' => trim($this->string('q')->toString()),
            'from' => $this->filled('from') ? $this->string('from')->toString() : $today,
            'to' => $this->filled('to') ? $this->string('to')->toString() : $today,
            'status' => $this->string('status')->toString(),
        ];
    }

    public function fromDate(): CarbonInterface
    {
        return Carbon::createFromFormat('Y-m-d', $this->filters()['from'])?->startOfDay() ?? now()->startOfDay();
    }

    public function toDate(): CarbonInterface
    {
        return Carbon::createFromFormat('Y-m-d', $this->filters()['to'])?->endOfDay() ?? now()->endOfDay();
    }
}
