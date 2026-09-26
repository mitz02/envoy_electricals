<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorePhaseOneFlowTest extends TestCase
{
    use RefreshDatabase;

    private function isolatedProduct(string $name): Product
    {
        return Product::create([
            'ref_id' => 'EV-PROD-'.str_pad((string) random_int(1000, PHP_INT_MAX), 6, '0', STR_PAD_LEFT),
            'name' => $name,
            'sku' => 'FLOW-'.strtoupper(substr(md5((string) mt_rand()), 0, 8)),
            'unit' => 'piece',
            'selling_price' => 0,
        ]);
    }

    public function test_full_purchase_and_sale_flow(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::create(['name' => 'Test Cat', 'slug' => 'test-cat-'.mt_rand()]);
        $supplier = Supplier::create(['ref_id' => 'SUP-TEST0', 'name' => 'Test Supplier']);

        $inventory = app(InventoryService::class);
        $purchaseService = app(PurchaseService::class);
        $saleService = app(SaleService::class);

        // ---- Product + opening stock ----
        $product = $this->isolatedProduct('PV Panel 550W');
        $product->update(['category_id' => $category->id, 'selling_price' => 120000]);
        $inventory->setOpeningStock($product, 10, 95000);

        $this->assertSame(10, $product->current_quantity);
        $this->assertSame(95000.0, (float) $product->average_cost);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_OPENING,
            'quantity_change' => 10,
        ]);

        // ---- Purchase ----
        $purchase = $purchaseService->createPurchase(
            ['purchase_date' => now()->toDateString(), 'supplier_id' => $supplier->id, 'amount_paid' => 100000],
            [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 100000]],
            $user->id
        );

        $this->assertSame(15, $product->fresh()->current_quantity);
        // Weighted average: (10*95000 + 5*100000) / 15 = 96666.67
        $this->assertEqualsWithDelta(96666.67, (float) $product->fresh()->average_cost, 0.02);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity_change' => 5,
            'reference' => $purchase->ref_id,
        ]);

        // ---- Sale ----
        $customer = Customer::create(['ref_id' => 'CUS-TEST01', 'name' => 'Mr Test']);
        $sale = $saleService->createSale(
            [
                'sale_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'payment_method' => 'transfer',
                'amount_paid' => 240000,
            ],
            [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 120000]],
            $user->id
        );

        $this->assertSame(13, $product->fresh()->current_quantity);
        $this->assertSame(240000.0, (float) $sale->total);
        $item = $sale->items()->first();
        // unit cost snapshot at sale time
        $this->assertEqualsWithDelta(96666.67, (float) $item->unit_cost, 0.02);
        $this->assertEqualsWithDelta(46666.67, (float) $item->profit, 0.02);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity_change' => -2,
            'reference' => $sale->invoice_no,
        ]);

        // ---- Insufficient stock is rejected ----
        $this->expectException(ValidationException::class);
        $saleService->createSale(
            ['sale_date' => now()->toDateString(), 'customer_id' => $customer->id],
            [['product_id' => $product->id, 'quantity' => 999, 'unit_price' => 120000]],
            $user->id
        );
    }

    public function test_expense_recording_and_ledger(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::create(['name' => 'Transport', 'slug' => 'transport-'.mt_rand()]);

        $expense = Expense::create([
            'ref_id' => 'EXP-TEST000001',
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $category->id,
            'description' => 'Delivery van fuel',
            'amount' => 15000,
            'payment_method' => 'cash',
            'status' => 'recorded',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 15000]);
        $this->assertSame(15000.0, (float) DB::table('expenses')->sum('amount'));
    }
}
