<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Requests\Staff\UpdateOrderingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class KitchenOrderingController extends Controller
{
    /**
     * Bestellen open/dicht vanuit de keuken. Sluiten zonder (bestaande of meegegeven) boodschap → 422.
     */
    public function __invoke(UpdateOrderingRequest $request): JsonResponse
    {
        self::apply(Setting::current(), $request->boolean('is_open'), $request->input('closed_message'));

        return response()->json(['ordering' => HandleInertiaRequests::ordering()]);
    }

    /**
     * Gedeeld met de admin-schakelaar: de boodschap blijft staan na heropenen (US-14).
     *
     * @throws ValidationException
     */
    public static function apply(Setting $setting, bool $isOpen, mixed $closedMessage): void
    {
        $message = is_string($closedMessage) && trim($closedMessage) !== '' ? trim($closedMessage) : null;

        if ($message !== null) {
            $setting->closed_message = $message;
        }

        if (! $isOpen && ($setting->closed_message === null || $setting->closed_message === '')) {
            throw ValidationException::withMessages([
                'closed_message' => ['Geef een boodschap mee die de klant ziet, bv. "Vandaag gesloten".'],
            ]);
        }

        $setting->is_open = $isOpen;
        $setting->save();
    }
}
