<?php

namespace App\Http\Resources\Api;

use App\Models\OrderLine;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderLine
 */
class OrderLineResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->product->price_cents,
            'unit_price' => Money::format($this->product->price_cents),
            'line_total_cents' => $this->line_total_cents,
            'line_total' => Money::format($this->line_total_cents),
        ];
    }
}
