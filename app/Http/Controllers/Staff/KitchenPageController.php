<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Staff\KitchenBoard;
use Inertia\Inertia;
use Inertia\Response;

class KitchenPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('keuken/index', [
            'initial' => KitchenBoard::toArray(),
        ]);
    }
}
