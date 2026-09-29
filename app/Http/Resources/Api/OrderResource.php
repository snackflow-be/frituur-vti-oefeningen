<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use App\Support\Money;
use App\Support\Phone;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Eén bestelling zoals de API ze toont (klant, keuken en admin gebruiken dezelfde vorm).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public static $wrap = 'order';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'token' => $this->public_token,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'kitchen_label' => $this->status->kitchenLabel(),
            'next_status' => $this->status->next()?->value,
            'action_label' => $this->status->actionLabel(),
            'customer_name' => $this->customer_name,
            'customer_phone' => Phone::format($this->customer_phone),
            'total_cents' => $this->total_cents,
            'total' => Money::format($this->total_cents),
            'placed_at' => self::iso($this->created_at),
            'started_at' => self::iso($this->started_at),
            'ready_at' => self::iso($this->ready_at),
            'picked_up_at' => self::iso($this->picked_up_at),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
        ];
    }

    private static function iso(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }
}
