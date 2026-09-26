<?php

use App\Models\Brand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('products', 'brand_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('subcategory_id')->constrained('brands')->nullOnDelete();
        });

        $this->backfillFromLegacyBrandColumn();
    }

    /**
     * Migrate free-text `products.brand` values onto the brands relation.
     */
    protected function backfillFromLegacyBrandColumn(): void
    {
        if (! Schema::hasColumn('products', 'brand')) {
            return;
        }

        DB::table('products')
            ->select('id', 'brand')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->orderBy('id')
            ->each(function ($product) {
                $name = trim((string) $product->brand);

                if ($name === '') {
                    return;
                }

                $brand = Brand::firstOrCreate(
                    ['name' => $name],
                    ['slug' => Str::slug($name).'-'.Str::lower(Str::random(5)), 'is_active' => true],
                );

                DB::table('products')->where('id', $product->id)->update(['brand_id' => $brand->id]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('products', 'brand_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
