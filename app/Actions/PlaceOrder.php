<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\OrderingClosedException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\OrderRateLimiter;
use App\Support\Phone;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Plaatst een bestelling: controleert open/dicht, beschikbaarheid en rate limit, berekent het totaal
 * server-side uit de producten (nooit uit de klantinvoer) en kent het nummer VTI-### toe.
 */
class PlaceOrder
{
    public function __construct(private readonly OrderRateLimiter $rateLimiter) {}

    /**
     * @param  list<array{product_id: int, quantity: int}>  $lines
     *
     * @throws ValidationException (422) bij gesloten, verdwenen of uitverkocht product of ongeldig nummer
     * @throws ThrottleRequestsException (429) bij te veel bestellingen
     */
    public function handle(string $customerName, string $customerPhone, array $lines, string $ip): Order
    {
        $phone = Phone::normalize($customerPhone);

        if ($phone === null) {
            throw ValidationException::withMessages(['customer_phone' => ['Vul een geldig Belgisch gsm-nummer in.']]);
        }

        $settings = Setting::current();

        if (! $settings->is_open) {
            throw OrderingClosedException::withClosedMessage($settings->closed_message);
        }

        $products = Product::query()
            ->whereIn('id', array_column($lines, 'product_id'))
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($lines as $index => $line) {
            $product = $products->get($line['product_id']);

            if ($product === null || ! $product->is_visible) {
                $errors["lines.{$index}.product_id"] = ['Dit product bestaat niet meer.'];
            } elseif ($product->is_sold_out) {
                $errors["lines.{$index}.product_id"] = ["{$product->name} is uitverkocht."];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->rateLimiter->ensureAllowed($ip, $phone);

        $order = DB::transaction(function () use ($customerName, $phone, $lines, $products): Order {
            $total = 0;
            $rows = [];

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $products->get($line['product_id']);
                $lineTotal = $product->price_cents * $line['quantity'];
                $total += $lineTotal;

                $rows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price_cents' => $product->price_cents,
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $lineTotal,
                ];
            }

            $order = Order::query()->create([
                'public_token' => Order::generateToken(),
                'customer_name' => trim($customerName),
                'customer_phone' => $phone,
                'status' => OrderStatus::Nieuw,
                'total_cents' => $total,
            ]);

            $order->lines()->createMany($rows);
            $order->number = Order::numberFor($order->id);
            $order->save();

            return $order;
        });

        $this->rateLimiter->hit($ip, $phone);

        return $order->load('lines');
    }
}
