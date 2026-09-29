<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\MoveRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->ordered()
            ->withCount('products')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
                'products_count' => (int) $category->getAttribute('products_count'),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $max = Category::query()->max('sort_order');

        $category = Category::query()->create([
            'name' => $name,
            'slug' => self::uniqueSlug($name),
            'sort_order' => $max === null ? 0 : (int) $max + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categorie '{$category->name}' aangemaakt."]);

        return to_route('admin.categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->name = $request->string('name')->toString();
        $category->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categorie hernoemd naar '{$category->name}'."]);

        return to_route('admin.categories.index');
    }

    /**
     * Een categorie met producten kan niet weg (B-8): 422 met melding.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'name' => ['Verplaats of verwijder eerst de producten in deze categorie.'],
            ]);
        }

        $name = $category->name;
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categorie '{$name}' verwijderd."]);

        return to_route('admin.categories.index');
    }

    public function move(MoveRequest $request, Category $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category): void {
            $all = Category::query()->ordered()->lockForUpdate()->get()->values();

            $index = $all->search(fn (Category $c): bool => $c->id === $category->id);
            $target = $request->isUp() ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $all->count()) {
                return;
            }

            $order = $all->keys()->all();
            [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

            foreach ($order as $position => $originalIndex) {
                $item = $all[$originalIndex];
                $item->sort_order = $position;
                $item->save();
            }
        });

        return back();
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'categorie';
        }

        $base = Str::limit($base, 50, '');
        $slug = $base;
        $i = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
