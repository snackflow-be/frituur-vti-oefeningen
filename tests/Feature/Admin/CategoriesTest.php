<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Setting::factory()->create();
        $this->staff = User::factory()->create();
    }

    public function test_index_lists_categories_with_product_counts(): void
    {
        $category = Category::factory()->create(['name' => 'Frieten', 'slug' => 'frieten']);
        Product::factory()->for($category)->count(2)->create();

        $this->actingAs($this->staff)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/categories/index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Frieten')
                ->where('categories.0.products_count', 2));
    }

    public function test_category_can_be_created_and_renamed(): void
    {
        Category::factory()->create(['name' => 'Frieten', 'slug' => 'frieten', 'sort_order' => 0]);

        $this->actingAs($this->staff)
            ->post(route('admin.categories.store'), ['name' => 'Snacks'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $snacks = Category::query()->where('name', 'Snacks')->firstOrFail();
        $this->assertSame('snacks', $snacks->slug);
        $this->assertSame(1, $snacks->sort_order);

        $this->actingAs($this->staff)
            ->put(route('admin.categories.update', $snacks), ['name' => 'Snacks & burgers'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Snacks & burgers', $snacks->fresh()?->name);
        $this->assertSame('snacks', $snacks->fresh()?->slug);
    }

    public function test_duplicate_name_is_refused_in_dutch(): void
    {
        Category::factory()->create(['name' => 'Frieten', 'slug' => 'frieten']);

        $this->actingAs($this->staff)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), ['name' => 'Frieten'])
            ->assertSessionHasErrors(['name' => 'Er bestaat al een categorie met die naam.']);
    }

    public function test_empty_category_can_be_deleted_but_one_with_products_not(): void
    {
        $empty = Category::factory()->create();
        $full = Category::factory()->create();
        Product::factory()->for($full)->create();

        $this->actingAs($this->staff)
            ->delete(route('admin.categories.destroy', $empty))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertModelMissing($empty);

        $this->actingAs($this->staff)
            ->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $full))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasErrors(['name' => 'Verplaats of verwijder eerst de producten in deze categorie.']);

        $this->assertModelExists($full);
    }

    public function test_categories_can_be_moved(): void
    {
        $a = Category::factory()->create(['name' => 'A', 'sort_order' => 0]);
        $b = Category::factory()->create(['name' => 'B', 'sort_order' => 1]);

        $this->actingAs($this->staff)
            ->patch(route('admin.categories.move', $b), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(['B', 'A'], Category::query()->ordered()->pluck('name')->all());
        $this->assertSame(0, $b->fresh()?->sort_order);
        $this->assertSame(1, $a->fresh()?->sort_order);
    }
}
