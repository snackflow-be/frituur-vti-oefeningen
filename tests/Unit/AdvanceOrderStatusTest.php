<?php

namespace Tests\Unit;

use App\Actions\AdvanceOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdvanceOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_de_volgende_stap_zet_status_en_tijdstempel(): void
    {
        $order = Order::factory()->create();
        $action = new AdvanceOrderStatus;

        $action->handle($order, OrderStatus::Bezig);
        $this->assertSame(OrderStatus::Bezig, $order->fresh()?->status);
        $this->assertNotNull($order->fresh()?->started_at);
        $this->assertNull($order->fresh()?->ready_at);

        $action->handle($order, OrderStatus::Klaar);
        $this->assertSame(OrderStatus::Klaar, $order->fresh()?->status);
        $this->assertNotNull($order->fresh()?->ready_at);

        $action->handle($order, OrderStatus::Afgehaald);
        $this->assertSame(OrderStatus::Afgehaald, $order->fresh()?->status);
        $this->assertNotNull($order->fresh()?->picked_up_at);
    }

    public function test_een_stap_overslaan_geeft_een_nederlandse_fout_op_status(): void
    {
        $order = Order::factory()->create();

        try {
            (new AdvanceOrderStatus)->handle($order, OrderStatus::Klaar);
            $this->fail('Overslaan had moeten falen.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ["Deze bestelling staat op 'Nieuw'; enkel de stap naar 'Bezig' kan."],
                $e->errors()['status'],
            );
        }

        $this->assertSame(OrderStatus::Nieuw, $order->fresh()?->status);
    }

    public function test_terug_gaan_kan_niet(): void
    {
        $order = Order::factory()->klaar()->create();

        $this->expectException(ValidationException::class);

        (new AdvanceOrderStatus)->handle($order, OrderStatus::Bezig);
    }

    public function test_na_afgehaald_is_er_geen_stap_meer(): void
    {
        $order = Order::factory()->afgehaald()->create();

        try {
            (new AdvanceOrderStatus)->handle($order, OrderStatus::Afgehaald);
            $this->fail('Na afgehaald had moeten falen.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('geen volgende stap', $e->errors()['status'][0]);
        }
    }
}
