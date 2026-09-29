<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        Setting::factory()->create();
        $this->staff = User::factory()->create();
        $this->category = Category::factory()->create(['name' => 'Snacks', 'slug' => 'snacks']);
    }

    public function test_index_lists_products_per_category_including_hidden(): void
    {
        Product::factory()->for($this->category)->create(['sort_order' => 1]);
        Product::factory()->for($this->category)->hidden()->create(['sort_order' => 0]);

        $this->actingAs($this->staff)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/products/index')
                ->has('categories', 1)
                ->has('categories.0.products', 2)
                ->where('categories.0.products.0.is_visible', false));
    }

    public function test_product_can_be_created_with_euro_price_and_photo(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/products/form')->where('product', null));

        $this->actingAs($this->staff)
            ->post(route('admin.products.store'), [
                'name' => 'Frikandel',
                'description' => 'De klassieker',
                'price' => '2,20',
                'category_id' => $this->category->id,
                'image' => UploadedFile::fake()->image('frikandel.jpg', 400, 300),
                'is_visible' => true,
                'is_sold_out' => false,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Frikandel')->firstOrFail();

        $this->assertSame(220, $product->price_cents);
        $this->assertSame('frikandel', $product->slug);
        $this->assertSame(0, $product->sort_order);
        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith('producten/', (string) $product->image_path);
        Storage::disk('public')->assertExists((string) $product->image_path);
    }

    public function test_invalid_price_and_wrong_file_are_refused_in_dutch(): void
    {
        $this->actingAs($this->staff)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'X',
                'price' => 'abc',
                'category_id' => $this->category->id,
                'image' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors([
                'name' => 'De naam moet minstens 2 tekens zijn.',
                'price' => 'Geef een geldige prijs op, bv. 3,50.',
                'image' => 'De foto moet een afbeelding zijn (jpg, png of webp).',
            ]);
    }

    public function test_product_can_be_updated_and_photo_replaced_or_removed(): void
    {
        $product = Product::factory()->for($this->category)->create([
            'image_path' => UploadedFile::fake()->image('oud.jpg')->store('producten', 'public'),
        ]);
        $oldPath = (string) $product->image_path;

        $this->actingAs($this->staff)
            ->put(route('admin.products.update', $product), [
                'name' => 'Nieuwe naam',
                'price' => '3.5',
                'category_id' => $this->category->id,
                'image' => UploadedFile::fake()->image('nieuw.png'),
                'is_visible' => false,
                'is_sold_out' => true,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Nieuwe naam', $product->name);
        $this->assertSame(350, $product->price_cents);
        $this->assertFalse($product->is_visible);
        $this->assertTrue($product->is_sold_out);
        $this->assertNotSame($oldPath, $product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists((string) $product->image_path);

        $newPath = (string) $product->image_path;

        $this->actingAs($this->staff)
            ->put(route('admin.products.update', $product), [
                'name' => 'Nieuwe naam',
                'price' => '3,50',
                'category_id' => $this->category->id,
                'remove_image' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()?->image_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_product_can_be_deleted_with_its_photo(): void
    {
        $product = Product::factory()->for($this->category)->create([
            'image_path' => UploadedFile::fake()->image('weg.jpg')->store('producten', 'public'),
        ]);
        $path = (string) $product->image_path;

        $this->actingAs($this->staff)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_seeder_photo_in_public_is_never_deleted(): void
    {
        $product = Product::factory()->for($this->category)->create(['image_path' => '/img/menu/frikandel.jpg']);

        $this->actingAs($this->staff)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertModelMissing($product);
    }

    public function test_products_can_be_moved_within_their_category(): void
    {
        $a = Product::factory()->for($this->category)->create(['name' => 'A', 'sort_order' => 0]);
        $b = Product::factory()->for($this->category)->create(['name' => 'B', 'sort_order' => 1]);
        $c = Product::factory()->for($this->category)->create(['name' => 'C', 'sort_order' => 2]);

        $this->actingAs($this->staff)
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.move', $c), ['direction' => 'up'])
            ->assertRedirect(route('admin.products.index'));

        $names = Product::query()->where('category_id', $this->category->id)->ordered()->pluck('name')->all();
        $this->assertSame(['A', 'C', 'B'], $names);

        $this->actingAs($this->staff)
            ->patch(route('admin.products.move', $a), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(['A', 'C', 'B'], Product::query()->ordered()->pluck('name')->all());
        $this->assertSame(0, $a->fresh()?->sort_order);
        $this->assertSame(2, $b->fresh()?->sort_order);
    }

    public function test_visibility_and_sold_out_can_be_toggled(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $this->actingAs($this->staff)
            ->patch(route('admin.products.toggle', $product), ['is_sold_out' => true])
            ->assertRedirect();

        $this->assertTrue($product->fresh()?->is_sold_out);
        $this->assertTrue($product->fresh()?->is_visible);

        $this->actingAs($this->staff)
            ->patch(route('admin.products.toggle', $product), ['is_visible' => false])
            ->assertRedirect();

        $this->assertFalse($product->fresh()?->is_visible);
    }
}
