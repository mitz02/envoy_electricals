<?php

use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultStore = Store::where('is_default', true)->first() ?? Store::first();
        if (! $defaultStore) {
            $defaultStoreId = DB::table('stores')->insertGetId([
                'name' => 'Lagos Main Branch',
                'code' => 'LAG',
                'address' => '123 Lagos Street, Victoria Island, Lagos',
                'phone' => '+234 800 123 4567',
                'email' => 'lagos@envoyelectric.ng',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultStoreId = $defaultStore->id;
        }

        $allStores = DB::table('stores')->get();

        // 1. Backfill product_store pivot for all products across all stores
        $products = DB::table('products')->get();
        foreach ($products as $p) {
            foreach ($allStores as $s) {
                $exists = DB::table('product_store')
                    ->where('product_id', $p->id)
                    ->where('store_id', $s->id)
                    ->exists();

                if (! $exists) {
                    $qty = ($s->id === $defaultStoreId) ? (int) $p->current_quantity : 0;
                    DB::table('product_store')->insert([
                        'product_id' => $p->id,
                        'store_id' => $s->id,
                        'current_quantity' => $qty,
                        'reorder_level' => $p->reorder_level ?? 0,
                        'average_cost' => $p->average_cost ?? $p->cost_price ?? 0,
                        'selling_price' => $p->selling_price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Backfill store_id on all legacy transactions that have store_id IS NULL
        $tables = [
            'sales', 'purchases', 'expenses', 'payments',
            'customers', 'projects', 'staff', 'assets', 'stock_movements',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('store_id')
                ->update(['store_id' => $defaultStoreId]);
        }
    }

    public function down(): void
    {
        // No-op rollback for data sync
    }
};
