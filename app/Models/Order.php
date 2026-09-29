<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $number
 * @property string $public_token
 * @property string $customer_name
 * @property string $customer_phone
 * @property OrderStatus $status
 * @property int $total_cents
 * @property Carbon|null $started_at
 * @property Carbon|null $ready_at
 * @property Carbon|null $picked_up_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number',
    'public_token',
    'customer_name',
    'customer_phone',
    'status',
    'total_cents',
    'started_at',
    'ready_at',
    'picked_up_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const string NUMBER_PREFIX = 'VTI-';

    public const int TOKEN_LENGTH = 24;

    /**
     * Bestelnummer op basis van het id: VTI-042, VTI-1000, … (B-17).
     */
    public static function numberFor(int $id): string
    {
        return self::NUMBER_PREFIX.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Willekeurig token voor de opvolglink (niet te raden uit het nummer, US-4).
     */
    public static function generateToken(): string
    {
        return Str::random(self::TOKEN_LENGTH);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('id');
    }

    /**
     * Geplaatst sinds middernacht (Europe/Brussels via app-tijdzone).
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function today(Builder $query): void
    {
        $query->where('created_at', '>=', now()->startOfDay());
    }

    /**
     * Nog niet afgehaald.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', '!=', OrderStatus::Afgehaald->value);
    }

    /**
     * Zoek op (deel van) bestelnummer, naam of gsm-nummer.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $phoneTerm = preg_replace('/[\s.\/-]+/', '', $term) ?? $term;
        $phoneLike = '%'.addcslashes($phoneTerm, '%_\\').'%';

        $query->where(function (Builder $q) use ($like, $phoneLike): void {
            $q->where('number', 'like', $like)
                ->orWhere('customer_name', 'like', $like)
                ->orWhere('customer_phone', 'like', $phoneLike);
        });
    }

    /**
     * Geplaatst tussen $from en $to (beide inclusief, elk optioneel).
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function placedBetween(Builder $query, ?DateTimeInterface $from, ?DateTimeInterface $to): void
    {
        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }
}
