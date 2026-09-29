<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MoveRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\ToggleProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public const string IMAGE_DIR = 'producten';

    /**
     * Alle producten per categorie (ook verborgen); zoeken en filteren gebeurt in de pagina.
     */
    public function index(): Response
    {
        $categories = Category::query()
            ->ordered()
            ->with('products')
            ->get()
            ->map(fn (Category $category): array => [
                ...self::category($category),
                'products' => $category->products->map(fn (Product $product): array => self::product($product))->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/products/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $categoryId = (int) $request->validated('category_id');

        $product = new Product([
            'category_id' => $categoryId,
            'name' => $request->string('name')->toString(),
            'slug' => self::uniqueSlug($request->string('name')->toString()),
            'description' => self::nullable($request->input('description')),
            'price_cents' => $request->priceCents(),
            'is_visible' => $request->boolean('is_visible', true),
            'is_sold_out' => $request->boolean('is_sold_out', false),
            'sort_order' => self::nextSortOrder($categoryId),
        ]);

        $image = $request->uploadedImage();

        if ($image !== null) {
            $product->image_path = (string) $image->store(self::IMAGE_DIR, 'public');
        }

        $product->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product '{$product->name}' aangemaakt."]);

        return to_route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        return $this->form($product);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $categoryId = (int) $request->validated('category_id');

        if ($categoryId !== $product->category_id) {
            $product->category_id = $categoryId;
            $product->sort_order = self::nextSortOrder($categoryId);
        }

        $product->name = $request->string('name')->toString();
        $product->description = self::nullable($request->input('description'));
        $product->price_cents = $request->priceCents();
        $product->is_visible = $request->boolean('is_visible', true);
        $product->is_sold_out = $request->boolean('is_sold_out', false);

        $image = $request->uploadedImage();

        if ($image !== null) {
            self::deleteImage($product);
            $product->image_path = (string) $image->store(self::IMAGE_DIR, 'public');
        } elseif ($request->boolean('remove_image')) {
            self::deleteImage($product);
            $product->image_path = null;
        }

        $product->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product '{$product->name}' bewaard."]);

        return to_route('admin.products.index');
    }

    /**
     * Harde delete; oude bestelregels houden hun kopie van naam en prijs (A-6).
     */
    public function destroy(Product $product): RedirectResponse
    {
        self::deleteImage($product);
        $name = $product->name;
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Product '{$name}' verwijderd."]);

        return to_route('admin.products.index');
    }

    /**
     * Eén plaats omhoog of omlaag binnen de categorie (volgorde eerst genormaliseerd naar 0..n-1).
     */
    public function move(MoveRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $siblings = Product::query()
                ->where('category_id', $product->category_id)
                ->ordered()
                ->lockForUpdate()
                ->get()
                ->values();

            $index = $siblings->search(fn (Product $sibling): bool => $sibling->id === $product->id);
            $target = $request->isUp() ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $siblings->count()) {
                return;
            }

            $order = $siblings->keys()->all();
            [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

            foreach ($order as $position => $originalIndex) {
                $sibling = $siblings[$originalIndex];
                $sibling->sort_order = $position;
                $sibling->save();
            }
        });

        return back();
    }

    public function toggle(ToggleProductRequest $request, Product $product): RedirectResponse
    {
        if ($request->has('is_visible')) {
            $product->is_visible = $request->boolean('is_visible');
        }

        if ($request->has('is_sold_out')) {
            $product->is_sold_out = $request->boolean('is_sold_out');
        }

        $product->save();

        return back();
    }

    private function form(?Product $product): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => $product === null ? null : self::product($product),
            'categories' => Category::query()->ordered()->get()->map(fn (Category $c): array => self::category($c))->values()->all(),
        ]);
    }

    /**
     * @return array{id: int, name: string, slug: string, sort_order: int}
     */
    private static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price_cents' => $product->price_cents,
            'image_url' => $product->image_url,
            'is_visible' => $product->is_visible,
            'is_sold_out' => $product->is_sold_out,
            'sort_order' => $product->sort_order,
        ];
    }

    private static function nextSortOrder(int $categoryId): int
    {
        $max = Product::query()->where('category_id', $categoryId)->max('sort_order');

        return $max === null ? 0 : (int) $max + 1;
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'product';
        }

        $base = Str::limit($base, 70, '');
        $slug = $base;
        $i = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * Geüploade foto van disk `public` verwijderen; seederfoto's in public/img/menu nooit (03-databank).
     */
    private static function deleteImage(Product $product): void
    {
        $path = $product->image_path;

        if ($path === null || $path === '' || Product::isPublicAssetPath($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private static function nullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
