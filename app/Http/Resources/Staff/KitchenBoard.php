<?php

namespace App\Http\Resources\Staff;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;

/**
 * Het keukenbord: alle open bestellingen van vandaag, oudste eerst, plus servertijd en
 * bestellen open/dicht. Zelfde vorm voor de eerste render (Inertia-prop) en de polling-JSON.
 */
final class KitchenBoard
{
    /**
     * @return array{server_time: string, ordering: array{is_open: bool, closed_message: string|null}, orders: array<int, mixed>}
     */
    public static function toArray(): array
    {
        $orders = Order::query()
            ->with('lines')
            ->today()
            ->open()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return [
            'server_time' => now()->toIso8601String(),
            'ordering' => HandleInertiaRequests::ordering(),
            'orders' => $orders->map(fn (Order $order): array => self::order($order))->values()->all(),
        ];
    }

    /**
     * Eén bestelling als plat array (regels als lijst, geen `data`-omhulsel), voor Inertia-props en JSON.
     *
     * @return array<string, mixed>
     */
    public static function order(Order $order): array
    {
        $order->loadMissing('lines');

        $data = (new OrderResource($order))->response()->getData(true);

        return is_array($data) && is_array($data['order'] ?? null) ? $data['order'] : [];
    }
}
