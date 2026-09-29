<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->word().' '.fake()->word());

        return [
            'name' => Str::limit($name, 40, ''),
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
