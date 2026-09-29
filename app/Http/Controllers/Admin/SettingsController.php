<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $setting = Setting::current();

        $byDay = [];

        foreach ($setting->opening_hours as $day) {
            $byDay[$day['day']] = $day['slots'];
        }

        return Inertia::render('admin/settings', [
            'settings' => [
                'business_name' => $setting->business_name,
                'address' => $setting->address,
                'phone' => $setting->phone,
                'opening_hours' => array_map(fn (int $day): array => [
                    'day' => $day,
                    'label' => Setting::DAY_LABELS[$day],
                    'slots' => $byDay[$day] ?? [],
                ], range(1, 7)),
            ],
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $setting = Setting::current();

        $setting->business_name = $request->string('business_name')->toString();
        $setting->address = $request->string('address')->toString();
        $setting->phone = $request->string('phone')->toString();
        $setting->opening_hours = $request->openingHours();
        $setting->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Instellingen bewaard.']);

        return to_route('admin.settings.edit');
    }
}
