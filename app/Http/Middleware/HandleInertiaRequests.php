<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Bestellen open/dicht voor de rode balk op elke personeelspagina (02-architect §4).
            'ordering' => fn (): array => self::ordering(),
        ];
    }

    /**
     * @return array{is_open: bool, closed_message: string|null}
     */
    public static function ordering(): array
    {
        $setting = Setting::query()->orderBy('id')->first();

        if ($setting === null) {
            return ['is_open' => true, 'closed_message' => null];
        }

        return [
            'is_open' => $setting->is_open,
            'closed_message' => $setting->closed_message,
        ];
    }
}
