<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '550W Solar Panel',
            'cost_price' => 180000,
            'selling_price' => 220000,
        ], $overrides);
    }

    protected function png(): UploadedFile
    {
        return UploadedFile::fake()->create('panel.png', 100, 'image/png');
    }

    public function test_create_product_with_multiple_images_and_first_is_cover(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner())
            ->post('/admin/products', array_merge($this->payload(), [
                'images' => [$this->png(), $this->png()],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', '550W Solar Panel')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('EV-', $product->sku);

        $images = $product->images()->orderBy('sort_order')->get();
        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_featured);
        $this->assertFalse($images[1]->is_featured);

        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_featured_new_index_picks_the_cover(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner())
            ->post('/admin/products', array_merge($this->payload(['name' => 'Cover Panel']), [
                'images' => [$this->png(), $this->png(), $this->png()],
                'featured_new_index' => 2,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Cover Panel')->first();
        $this->assertNotNull($product);

        $featured = $product->images()->where('is_featured', true)->first();
        $this->assertNotNull($featured);
        $this->assertSame(2, $featured->sort_order);
    }

    public function test_invalid_image_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner())
            ->post('/admin/products', array_merge($this->payload(), [
                'images' => [UploadedFile::fake()->create('notes.txt', 100, 'text/plain')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_update_removes_old_images_and_adds_new_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('product-images/old.png', 'old');
        Storage::disk('public')->put('product-images/keep.png', 'keep');

        $product = Product::create([
            'ref_id' => 'PROD-TEST-1',
            'sku' => 'EV-SOL-UPD',
            'name' => 'Updated Panel',
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $old = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'product-images/old.png',
            'is_featured' => true,
            'sort_order' => 0,
        ]);
        $keep = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'product-images/keep.png',
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->owner())
            ->put("/admin/products/{$product->id}", array_merge($this->payload(['name' => 'Updated Panel']), [
                'remove_images' => [$old->id],
                'images' => [$this->png()],
                'featured_new_index' => 0,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertDatabaseMissing('product_images', ['id' => $old->id]);
        Storage::disk('public')->assertMissing('product-images/old.png');
        Storage::disk('public')->assertExists('product-images/keep.png');

        $images = $product->images()->orderBy('sort_order')->orderBy('id')->get();
        $this->assertCount(2, $images);

        $featured = $images->firstWhere('is_featured', true);
        $this->assertNotNull($featured);
        $this->assertNotSame($keep->id, $featured->id);
        $this->assertSame(1, $product->images()->where('is_featured', true)->count());
    }

    public function test_create_product_with_specs_rows(): void
    {
        $this->actingAs($this->owner())
            ->post('/admin/products', array_merge($this->payload(['name' => 'Spec Panel']), [
                'specs' => [
                    ['label' => 'Wattage', 'value' => '550W'],
                    ['label' => 'Warranty', 'value' => '12 months'],
                    ['label' => '', 'value' => 'dropped'],
                    ['label' => 'dropped', 'value' => ''],
                ],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Spec Panel')->first();
        $this->assertNotNull($product);
        $this->assertSame([
            ['label' => 'Wattage', 'value' => '550W'],
            ['label' => 'Warranty', 'value' => '12 months'],
        ], $product->specifications_json);
        $this->assertSame("Wattage: 550W\nWarranty: 12 months", $product->specifications);
    }

    public function test_update_specs_rows_replaces_previous_ones(): void
    {
        $product = Product::create([
            'ref_id' => 'PROD-TEST-2',
            'sku' => 'EV-SOL-SPC',
            'name' => 'Spec Update',
            'cost_price' => 100,
            'selling_price' => 150,
            'specifications' => "Wattage: 500W\nWarranty: 6 months",
            'specifications_json' => [
                ['label' => 'Wattage', 'value' => '500W'],
                ['label' => 'Warranty', 'value' => '6 months'],
            ],
        ]);

        $this->actingAs($this->owner())
            ->put("/admin/products/{$product->id}", array_merge($this->payload(), [
                'specs' => [
                    ['label' => 'Wattage', 'value' => '550W'],
                ],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame([
            ['label' => 'Wattage', 'value' => '550W'],
        ], $product->specifications_json);
        $this->assertSame('Wattage: 550W', $product->specifications);
    }

    public function test_clearing_specs_stores_null(): void
    {
        $product = Product::create([
            'ref_id' => 'PROD-TEST-3',
            'sku' => 'EV-SOL-CLR',
            'name' => 'Spec Clear',
            'cost_price' => 100,
            'selling_price' => 150,
            'specifications' => 'Wattage: 550W',
            'specifications_json' => [
                ['label' => 'Wattage', 'value' => '550W'],
            ],
        ]);

        $this->actingAs($this->owner())
            ->put("/admin/products/{$product->id}", array_merge($this->payload(), [
                'specs' => [],
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertNull($product->specifications_json);
        $this->assertNull($product->specifications);
    }
}
