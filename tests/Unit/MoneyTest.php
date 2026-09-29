<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_centen_worden_als_euro_met_komma_getoond(): void
    {
        $this->assertSame('€ 9,30', Money::format(930));
        $this->assertSame('€ 0,90', Money::format(90));
        $this->assertSame('€ 0,00', Money::format(0));
        $this->assertSame('€ 12,05', Money::format(1205));
        $this->assertSame('-€ 1,00', Money::format(-100));
    }

    public function test_euro_tekst_wordt_naar_centen_omgezet(): void
    {
        $this->assertSame(350, Money::parseEuro('3,50'));
        $this->assertSame(350, Money::parseEuro('3.50'));
        $this->assertSame(350, Money::parseEuro('3,5'));
        $this->assertSame(300, Money::parseEuro('3'));
        $this->assertSame(420, Money::parseEuro('€ 4,20'));
        $this->assertSame(90, Money::parseEuro('0,90'));
    }

    public function test_ongeldige_prijs_geeft_null(): void
    {
        $this->assertNull(Money::parseEuro(''));
        $this->assertNull(Money::parseEuro('abc'));
        $this->assertNull(Money::parseEuro('-3,50'));
        $this->assertNull(Money::parseEuro('3,500'));
        $this->assertNull(Money::parseEuro('1.000,50'));
    }
}
