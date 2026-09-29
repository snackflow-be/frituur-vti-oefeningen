<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AdvanceOrderStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Staff\UpdateOrderStatusRequest;
use App\Http\Resources\Staff\KitchenBoard;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public const int PER_PAGE = 25;

    /**
     * Vandaag (standaard) en geschiedenis, zoeken op nummer/naam/gsm, filter op status. Nieuwste eerst.
     */
    public function index(OrderIndexRequest $request): Response
    {
        $filters = $request->filters();

        $orders = Order::query()
            ->with('lines')
            ->placedBetween($request->fromDate(), $request->toDate())
            ->when($filters['q'] !== '', fn ($query) => $query->search($filters['q']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Order $order): array => KitchenBoard::order($order));

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => self::statuses(),
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('admin/orders/show', [
            'order' => KitchenBoard::order($order),
        ]);
    }

    public function status(UpdateOrderStatusRequest $request, Order $order, AdvanceOrderStatus $advance): RedirectResponse
    {
        $advance->handle($order, $request->status());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Bestelling {$order->number} staat nu op '{$order->status->kitchenLabel()}'.",
        ]);

        return to_route('admin.orders.show', $order);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statuses(): array
    {
        return array_map(
            fn (OrderStatus $status): array => ['value' => $status->value, 'label' => $status->kitchenLabel()],
            OrderStatus::cases(),
        );
    }
}
