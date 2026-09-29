<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_belgische_gsm_nummers_worden_genormaliseerd(): void
    {
        $this->assertSame('0470123456', Phone::normalize('0470123456'));
        $this->assertSame('0470123456', Phone::normalize('0470 12 34 56'));
        $this->assertSame('0470123456', Phone::normalize('0470.12.34.56'));
        $this->assertSame('0470123456', Phone::normalize('0470/12.34.56'));
        $this->assertSame('0470123456', Phone::normalize('+32 470 12 34 56'));
        $this->assertSame('0470123456', Phone::normalize('+32470123456'));
        $this->assertSame('0470123456', Phone::normalize('0032 470 12 34 56'));
        $this->assertSame('0470123456', Phone::normalize(' 0470-12-34-56 '));
    }

    public function test_de_nul_tussen_haakjes_na_de_landcode_valt_weg(): void
    {
        $this->assertSame('0470123456', Phone::normalize('+32 (0)470 12 34 56'));
        $this->assertSame('0470123456', Phone::normalize('+32(0)470123456'));
        $this->assertSame('0470123456', Phone::normalize('0032 (0) 470 12 34 56'));
        $this->assertSame('0470123456', Phone::normalize('+32 (0)470/12.34.56'));

        // Enkel na de landcode: een (0) verderop in het nummer blijft ongeldig.
        $this->assertNull(Phone::normalize('+32 470 (0)12 34 56'));
        $this->assertNull(Phone::normalize('+32 (0)56 00 00 00'));
    }

    public function test_geen_belgisch_gsm_nummer_geeft_null(): void
    {
        $this->assertNull(Phone::normalize(''));
        $this->assertNull(Phone::normalize('056 00 00 00'));
        $this->assertNull(Phone::normalize('047012345'));
        $this->assertNull(Phone::normalize('04701234567'));
        $this->assertNull(Phone::normalize('+31 6 12345678'));
        $this->assertNull(Phone::normalize('abc'));
    }

    public function test_nummer_wordt_leesbaar_geformatteerd(): void
    {
        $this->assertSame('0470 12 34 56', Phone::format('0470123456'));
        $this->assertSame('0470 12 34 56', Phone::format('+32 470 12 34 56'));
        $this->assertSame('onbekend', Phone::format('onbekend'));
    }
}
