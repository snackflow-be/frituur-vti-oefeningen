<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_statussen_gaan_enkel_vooruit_in_vaste_volgorde(): void
    {
        $this->assertSame(OrderStatus::Bezig, OrderStatus::Nieuw->next());
        $this->assertSame(OrderStatus::Klaar, OrderStatus::Bezig->next());
        $this->assertSame(OrderStatus::Afgehaald, OrderStatus::Klaar->next());
        $this->assertNull(OrderStatus::Afgehaald->next());
    }

    public function test_enkel_de_directe_volgende_stap_is_toegestaan(): void
    {
        $this->assertTrue(OrderStatus::Nieuw->canAdvanceTo(OrderStatus::Bezig));
        $this->assertTrue(OrderStatus::Bezig->canAdvanceTo(OrderStatus::Klaar));
        $this->assertTrue(OrderStatus::Klaar->canAdvanceTo(OrderStatus::Afgehaald));

        // Terug, overslaan, zelfde status of na afgehaald: nooit.
        $this->assertFalse(OrderStatus::Bezig->canAdvanceTo(OrderStatus::Nieuw));
        $this->assertFalse(OrderStatus::Nieuw->canAdvanceTo(OrderStatus::Klaar));
        $this->assertFalse(OrderStatus::Nieuw->canAdvanceTo(OrderStatus::Nieuw));
        $this->assertFalse(OrderStatus::Afgehaald->canAdvanceTo(OrderStatus::Nieuw));
        $this->assertFalse(OrderStatus::Afgehaald->canAdvanceTo(OrderStatus::Afgehaald));
    }

    public function test_labels_en_tijdstempelkolommen(): void
    {
        $this->assertSame('Ontvangen', OrderStatus::Nieuw->label());
        $this->assertSame('In de maak', OrderStatus::Bezig->label());
        $this->assertSame('Nieuw', OrderStatus::Nieuw->kitchenLabel());
        $this->assertSame('Start', OrderStatus::Nieuw->actionLabel());
        $this->assertNull(OrderStatus::Afgehaald->actionLabel());

        $this->assertNull(OrderStatus::Nieuw->timestampColumn());
        $this->assertSame('started_at', OrderStatus::Bezig->timestampColumn());
        $this->assertSame('ready_at', OrderStatus::Klaar->timestampColumn());
        $this->assertSame('picked_up_at', OrderStatus::Afgehaald->timestampColumn());
    }

    public function test_values_geeft_de_vier_waarden_in_volgorde(): void
    {
        $this->assertSame(['nieuw', 'bezig', 'klaar', 'afgehaald'], OrderStatus::values());
    }
}
