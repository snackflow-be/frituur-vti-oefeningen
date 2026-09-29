<?php

namespace App\Enums;

/**
 * Status van een bestelling. Enkel vooruit: nieuw → bezig → klaar → afgehaald.
 */
enum OrderStatus: string
{
    case Nieuw = 'nieuw';
    case Bezig = 'bezig';
    case Klaar = 'klaar';
    case Afgehaald = 'afgehaald';

    /**
     * Label voor de klant (opvolgpagina).
     */
    public function label(): string
    {
        return match ($this) {
            self::Nieuw => 'Ontvangen',
            self::Bezig => 'In de maak',
            self::Klaar => 'Klaar',
            self::Afgehaald => 'Afgehaald',
        };
    }

    /**
     * Label voor de keuken (kolomtitel).
     */
    public function kitchenLabel(): string
    {
        return match ($this) {
            self::Nieuw => 'Nieuw',
            self::Bezig => 'Bezig',
            self::Klaar => 'Klaar',
            self::Afgehaald => 'Afgehaald',
        };
    }

    /**
     * Tekst op de knop die naar de volgende stap leidt; null als er geen volgende stap is.
     */
    public function actionLabel(): ?string
    {
        return match ($this) {
            self::Nieuw => 'Start',
            self::Bezig => 'Klaar',
            self::Klaar => 'Afgehaald',
            self::Afgehaald => null,
        };
    }

    /**
     * De enige status waarnaar je vanaf hier mag overgaan; null na de laatste stap.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Nieuw => self::Bezig,
            self::Bezig => self::Klaar,
            self::Klaar => self::Afgehaald,
            self::Afgehaald => null,
        };
    }

    /**
     * Kolom op `orders` die het tijdstip van aankomst in deze status bewaart.
     * Voor `Nieuw` is dat `created_at` (niet apart bijgehouden), dus null.
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Nieuw => null,
            self::Bezig => 'started_at',
            self::Klaar => 'ready_at',
            self::Afgehaald => 'picked_up_at',
        };
    }

    /**
     * Mag deze bestelling naar $to? Enkel de directe volgende stap.
     */
    public function canAdvanceTo(self $to): bool
    {
        return $this->next() === $to;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
