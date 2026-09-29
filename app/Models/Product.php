<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $price_cents
 * @property string|null $image_path
 * @property bool $is_visible
 * @property bool $is_sold_out
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $image_url
 */
#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'price_cents',
    'image_path',
    'is_visible',
    'is_sold_out',
    'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Klanten mogen dit product bestellen: zichtbaar en niet uitverkocht.
     */
    public function isOrderable(): bool
    {
        return $this->is_visible && ! $this->is_sold_out;
    }

    /**
     * URL van de foto voor de site. `image_path` is ofwel een pad op disk `public` (upload via admin,
     * map `producten/`), ofwel een absoluut pad zoals `/img/menu/<slug>.jpg` (gezet door de seeder).
     * Zonder `image_path` kijken we nog naar `public/img/menu/<slug>.(webp|jpg)`; anders null (placeholder).
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->image_path;

            if ($path === null || $path === '') {
                return self::menuImagePath($this->slug);
            }

            if (self::isPublicAssetPath($path)) {
                return $path;
            }

            return Storage::disk('public')->url($path);
        });
    }

    /**
     * Staat deze `image_path` rechtstreeks in `public/` (seederfoto) in plaats van op disk `public` (upload)?
     * Personeel mag zo'n bestand nooit verwijderen bij vervangen.
     */
    public static function isPublicAssetPath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    /**
     * Relatief pad (`/img/menu/<slug>.webp|jpg`) als zo'n bestand in `public/` staat, anders null.
     */
    public static function menuImagePath(string $slug): ?string
    {
        foreach (['webp', 'jpg', 'jpeg'] as $extension) {
            $relative = "img/menu/{$slug}.{$extension}";

            if (is_file(public_path($relative))) {
                return '/'.$relative;
            }
        }

        return null;
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_visible' => 'boolean',
            'is_sold_out' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
