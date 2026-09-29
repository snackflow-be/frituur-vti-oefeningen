<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => null,
            'public_token' => Order::generateToken(),
            'customer_name' => fake()->firstName(),
            'customer_phone' => '04'.fake()->numerify('########'),
            'status' => OrderStatus::Nieuw,
            'total_cents' => fake()->numberBetween(200, 4000),
            'started_at' => null,
            'ready_at' => null,
            'picked_up_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order): void {
            $changed = false;

            if ($order->number === null) {
                $order->number = Order::numberFor($order->id);
                $changed = true;
            }

            if ($order->lines()->exists()) {
                $order->total_cents = (int) $order->lines()->sum('line_total_cents');
                $changed = true;
            }

            if ($changed) {
                $order->save();
            }
        });
    }

    public function bezig(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Bezig,
            'started_at' => now(),
        ]);
    }

    public function klaar(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Klaar,
            'started_at' => now()->subMinutes(5),
            'ready_at' => now(),
        ]);
    }

    public function afgehaald(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Afgehaald,
            'started_at' => now()->subMinutes(10),
            'ready_at' => now()->subMinutes(5),
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Met $n bestelregels; het totaal wordt na het aanmaken herberekend uit de regels.
     */
    public function withLines(int $n = 2): static
    {
        return $this->has(OrderLine::factory()->count($n), 'lines');
    }
}
