<?php

namespace App\Http\Controllers\Api;

use App\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Bestelling plaatsen (201). Totaal en nummer worden server-side bepaald.
     */
    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder): JsonResponse
    {
        $order = $placeOrder->handle(
            customerName: $request->string('customer_name')->toString(),
            customerPhone: $request->string('customer_phone')->toString(),
            lines: $request->lines(),
            ip: $request->ip() ?? '0.0.0.0',
        );

        return OrderResource::make($order)
            ->additional(['track_url' => self::trackUrl($order)])
            ->response()
            ->setStatusCode(201)
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Bestelling opvolgen via de geheime token uit de bevestiging.
     */
    public function show(string $token): JsonResponse
    {
        $order = Order::query()->with('lines')->where('public_token', $token)->first();

        if ($order === null) {
            abort(404, 'Bestelling niet gevonden.');
        }

        return OrderResource::make($order)
            ->response()
            ->header('Cache-Control', 'no-store');
    }

    public static function trackUrl(Order $order): string
    {
        return url('/bestelling/'.$order->public_token);
    }
}
