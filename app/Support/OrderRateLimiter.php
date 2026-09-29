<?php

namespace App\Support;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * Rem op het plaatsen van bestellingen: per IP (een hele klas op één wifi moet kunnen) en strenger
 * per gsm-nummer. Enkel geslaagde bestellingen tellen mee (`hit` pas na de transactie), zodat
 * geweigerde pogingen (422) niemand blokkeren.
 */
final class OrderRateLimiter
{
    public const int PER_IP = 60;

    public const int PER_PHONE = 3;

    public const int DECAY_SECONDS = 60;

    public function __construct(private readonly RateLimiter $limiter) {}

    /**
     * @throws ThrottleRequestsException met header Retry-After (429)
     */
    public function ensureAllowed(string $ip, string $phone): void
    {
        foreach ([self::ipKey($ip) => self::PER_IP, self::phoneKey($phone) => self::PER_PHONE] as $key => $max) {
            if ($this->limiter->tooManyAttempts($key, $max)) {
                $retryAfter = max(1, $this->limiter->availableIn($key));

                throw new ThrottleRequestsException(
                    'Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.',
                    null,
                    ['Retry-After' => (string) $retryAfter],
                );
            }
        }
    }

    public function hit(string $ip, string $phone): void
    {
        $this->limiter->hit(self::ipKey($ip), self::DECAY_SECONDS);
        $this->limiter->hit(self::phoneKey($phone), self::DECAY_SECONDS);
    }

    public static function ipKey(string $ip): string
    {
        return 'bestellen:ip:'.$ip;
    }

    public static function phoneKey(string $phone): string
    {
        return 'bestellen:tel:'.$phone;
    }
}
