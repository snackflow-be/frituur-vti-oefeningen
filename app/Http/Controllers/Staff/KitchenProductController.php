<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\UpdateSoldOutRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class KitchenProductController extends Controller
{
    /**
     * Alle producten per categorie (ook verborgen) voor de uitverkocht-schakelaars.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->ordered()
            ->with('products')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'products' => $category->products
                    ->map(fn (Product $product): array => self::product($product))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return response()->json(['categories' => $categories])->header('Cache-Control', 'no-store');
    }

    public function soldOut(UpdateSoldOutRequest $request, Product $product): JsonResponse
    {
        $product->is_sold_out = $request->boolean('is_sold_out');
        $product->save();

        return response()->json(['product' => self::product($product)]);
    }

    /**
     * @return array{id: int, name: string, is_sold_out: bool, is_visible: bool}
     */
    private static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'is_sold_out' => $product->is_sold_out,
            'is_visible' => $product->is_visible,
        ];
    }
}
