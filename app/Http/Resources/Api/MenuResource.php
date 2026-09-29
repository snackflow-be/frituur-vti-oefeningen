<?php

namespace App\Http\Resources\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Het publieke menu: zaakgegevens, open/dicht en de categorieën met zichtbare producten.
 * `$resource` = ['settings' => Setting, 'categories' => Collection<Category>] (producten al geladen).
 */
class MenuResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param  Collection<int, Category>  $categories
     */
    public static function fromModels(Setting $settings, Collection $categories): self
    {
        return new self(['settings' => $settings, 'categories' => $categories]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{settings: Setting, categories: Collection<int, Category>} $data */
        $data = $this->resource;
        $settings = $data['settings'];

        return [
            'business' => self::business($settings),
            'ordering' => self::ordering($settings),
            'categories' => $data['categories']
                ->filter(fn (Category $category): bool => $category->products->isNotEmpty())
                ->values()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products' => $category->products
                        ->map(fn (Product $product): array => self::product($product))
                        ->values()
                        ->all(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{name: string, address: string, phone: string, opening_hours: list<array{day: int, label: string, slots: list<array{from: string, to: string}>}>}
     */
    public static function business(Setting $settings): array
    {
        $hours = [];

        foreach ($settings->opening_hours ?? Setting::closedAllWeek() as $day) {
            $hours[] = [
                'day' => $day['day'],
                'label' => Setting::DAY_LABELS[$day['day']] ?? '',
                'slots' => $day['slots'],
            ];
        }

        return [
            'name' => $settings->business_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'opening_hours' => $hours,
        ];
    }

    /**
     * @return array{is_open: bool, closed_message: string|null}
     */
    public static function ordering(Setting $settings): array
    {
        return [
            'is_open' => $settings->is_open,
            'closed_message' => $settings->closed_message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price_cents' => $product->price_cents,
            'price' => Money::format($product->price_cents),
            'image_url' => $product->image_url,
            'is_sold_out' => $product->is_sold_out,
        ];
    }
}
