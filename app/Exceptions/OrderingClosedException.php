<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Bestellen staat dicht (422): `errors.ordering` bevat de boodschap van de baas, `message` blijft vast.
 */
class OrderingClosedException extends ValidationException
{
    public const string MESSAGE = 'Bestellen is momenteel gesloten.';

    public static function withClosedMessage(?string $closedMessage): self
    {
        $validator = Validator::make([], []);
        $validator->errors()->add('ordering', $closedMessage !== null && $closedMessage !== '' ? $closedMessage : self::MESSAGE);

        $exception = new self($validator);
        $exception->message = self::MESSAGE;

        return $exception;
    }
}
