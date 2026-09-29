<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->word().' '.fake()->word());

        return [
            'category_id' => Category::factory(),
            'name' => Str::limit($name, 60, ''),
            'slug' => Str::slug($name),
            'description' => Str::limit(fake()->sentence(4), 60, ''),
            'price_cents' => fake()->numberBetween(90, 650),
            'image_path' => null,
            'is_visible' => true,
            'is_sold_out' => false,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['is_sold_out' => true]);
    }
}
