<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\OrderRateLimiter;
use Illuminate\Contracts\View\View;

class ApiDocsController extends Controller
{
    /**
     * Leesbare documentatie van de API met een curl-voorbeeld per route.
     */
    public function __invoke(): View
    {
        return view('api-docs', [
            'baseUrl' => rtrim(config()->string('app.url'), '/'),
            'perIp' => OrderRateLimiter::PER_IP,
            'perPhone' => OrderRateLimiter::PER_PHONE,
        ]);
    }
}
