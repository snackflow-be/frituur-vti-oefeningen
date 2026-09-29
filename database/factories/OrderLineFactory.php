<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(90, 650);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_name' => Str::limit(Str::ucfirst(fake()->word().' '.fake()->word()), 60, ''),
            'unit_price_cents' => $unitPrice,
            'quantity' => $quantity,
            'line_total_cents' => $quantity * $unitPrice,
        ];
    }

    /**
     * Regel voor een bestaand product: naam en prijs worden gekopieerd (B-6).
     */
    public function forProduct(Product $product, int $quantity = 1): static
    {
        return $this->state(fn () => [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price_cents' => $product->price_cents,
            'quantity' => $quantity,
            'line_total_cents' => $quantity * $product->price_cents,
        ]);
    }
}
