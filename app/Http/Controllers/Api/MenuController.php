<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MenuResource;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Het publieke menu: verborgen producten en lege categorieën ontbreken, uitverkochte staan er wél in.
     */
    public function __invoke(): JsonResponse
    {
        $categories = Category::query()
            ->ordered()
            ->with(['products' => fn ($query) => $query->visible()])
            ->get();

        return MenuResource::fromModels(Setting::current(), $categories)
            ->response()
            ->header('Cache-Control', 'no-store');
    }
}
