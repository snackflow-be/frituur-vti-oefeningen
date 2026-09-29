<?php

namespace App\Http\Controllers\Staff;

use App\Actions\AdvanceOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\UpdateOrderStatusRequest;
use App\Http\Resources\Staff\KitchenBoard;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class KitchenOrderController extends Controller
{
    /**
     * Het bord: enkel vandaag en nog niet afgehaald, oudste eerst. Gepold elke 5 s.
     */
    public function index(): JsonResponse
    {
        return response()
            ->json(KitchenBoard::toArray())
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Eén grote knop: naar de volgende status (enkel vooruit, via de domeinactie).
     */
    public function status(UpdateOrderStatusRequest $request, Order $order, AdvanceOrderStatus $advance): JsonResponse
    {
        $advance->handle($order, $request->status());

        return response()->json([
            'order' => KitchenBoard::order($order->fresh(['lines']) ?? $order),
        ]);
    }
}
