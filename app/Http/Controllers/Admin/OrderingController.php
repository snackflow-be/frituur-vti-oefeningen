<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\KitchenOrderingController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Requests\Staff\UpdateOrderingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/ordering', [
            'ordering' => HandleInertiaRequests::ordering(),
        ]);
    }

    public function update(UpdateOrderingRequest $request): RedirectResponse
    {
        $isOpen = $request->boolean('is_open');

        KitchenOrderingController::apply(Setting::current(), $isOpen, $request->input('closed_message'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isOpen ? 'Bestellen staat open.' : 'Bestellen is gesloten; klanten zien je boodschap.',
        ]);

        return to_route('admin.ordering.edit');
    }
}
