<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * De enige plek waar een bestelling van status verandert: enkel de directe volgende stap,
 * nooit terug of overslaan. Zet ook het tijdstempel van de nieuwe stap.
 */
class AdvanceOrderStatus
{
    /**
     * @throws ValidationException (422, sleutel `status`) bij een verboden overgang
     */
    public function handle(Order $order, OrderStatus $to): Order
    {
        return DB::transaction(function () use ($order, $to): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->status->canAdvanceTo($to)) {
                throw ValidationException::withMessages([
                    'status' => [self::forbiddenMessage($locked->status)],
                ]);
            }

            $locked->status = $to;

            $column = $to->timestampColumn();

            if ($column !== null) {
                $locked->setAttribute($column, now());
            }

            $locked->save();

            $order->setRawAttributes($locked->getAttributes(), true);

            return $order;
        });
    }

    public static function forbiddenMessage(OrderStatus $current): string
    {
        $next = $current->next();

        if ($next === null) {
            return "Deze bestelling staat op '{$current->kitchenLabel()}'; er is geen volgende stap meer.";
        }

        return "Deze bestelling staat op '{$current->kitchenLabel()}'; enkel de stap naar '{$next->kitchenLabel()}' kan.";
    }
}
