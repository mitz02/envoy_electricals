<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function userWithRole(string $slug): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', $slug)->first()->id,
        ]);
    }

    protected function product(string $name, float $cost = 100): Product
    {
        $product = Product::create([
            'ref_id' => 'EV-PROD-' . str_pad((string) random_int(1000, PHP_INT_MAX), 6, '0', STR_PAD_LEFT),
            'name' => $name,
            'sku' => 'PRJ-' . strtoupper(substr(md5((string) mt_rand()), 0, 8)),
            'unit' => 'piece',
            'cost_price' => $cost,
            'selling_price' => 150,
        ]);

        app(InventoryService::class)->setOpeningStock($product, 10, $cost);

        return $product;
    }

    protected function project(float $contract = 1000, int $userId = 1): Project
    {
        return app(ProjectService::class)->create([
            'name' => 'Test Solar Project',
            'contract_value' => $contract,
            'status' => Project::STATUS_APPROVED,
        ], $userId);
    }

    public function test_create_project_generates_ref_and_initial_balances(): void
    {
        $owner = $this->owner();
        $customer = Customer::create(['ref_id' => 'CUS-T', 'name' => 'Project Owner', 'phone' => '08000000002']);

        $this->actingAs($owner)
            ->post('/admin/projects', [
                'name' => 'Backup Power Installation',
                'customer_id' => $customer->id,
                'contract_value' => 1250000,
                'status' => 'approved',
                'start_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Backup Power Installation',
            'customer_id' => $customer->id,
            'contract_value' => 1250000,
            'balance' => 1250000,
            'gross_profit' => 1250000,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)->get('/admin/projects')->assertOk();
    }

    public function test_material_issue_decreases_inventory_and_updates_project_costs(): void
    {
        // TEST 4 — project material issue: 10 → 7 after issuing 3 units.
        $owner = $this->owner();
        $product = $this->product('Solar Panel 550W', 100);
        $project = $this->project(1000, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/materials", [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_cost' => 100,
                'issue_now' => true,
            ])
            ->assertRedirect();

        $this->assertSame(7, (int) $product->fresh()->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_PROJECT_ISSUE,
            'quantity_change' => -3,
            'document_type' => 'project',
            'document_id' => $project->id,
        ]);

        $project->refresh();
        $this->assertEquals(300.0, (float) $project->material_cost);
        $this->assertEquals(300.0, (float) $project->project_cost);
        $this->assertEquals(700.0, (float) $project->gross_profit);
    }

    public function test_material_issue_without_inventory_is_rejected(): void
    {
        $owner = $this->owner();
        $product = Product::create([
            'ref_id' => 'EV-PROD-LOW', 'name' => 'Rare Battery', 'sku' => 'PRJ-LOW-1',
            'unit' => 'piece', 'cost_price' => 100, 'selling_price' => 150,
        ]);
        app(InventoryService::class)->setOpeningStock($product, 2, 100);
        $project = $this->project(1000, $owner->id);

        $this->from("/admin/projects/{$project->id}")
            ->actingAs($owner)
            ->post("/admin/projects/{$project->id}/materials", [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_cost' => 100,
                'issue_now' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('product');

        $this->assertSame(2, (int) $product->fresh()->current_quantity);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id, 'type' => StockMovement::TYPE_PROJECT_ISSUE]);
    }

    public function test_removing_issued_material_returns_stock_to_inventory(): void
    {
        $owner = $this->owner();
        $product = $this->product('Inverter 5kVA', 250);
        $project = $this->project(1000, $owner->id);
        $material = app(ProjectService::class)->addMaterial($project, [
            'product_id' => $product->id, 'quantity' => 4, 'issue_now' => true,
        ], $owner->id);

        $this->assertSame(6, (int) $product->fresh()->current_quantity);

        $this->actingAs($owner)
            ->delete("/admin/projects/{$project->id}/materials/{$material->id}")
            ->assertRedirect();

        $this->assertSame(10, (int) $product->fresh()->current_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_PROJECT_RETURN,
            'quantity_change' => 4,
        ]);

        $project->refresh();
        $this->assertEquals(0.0, (float) $project->material_cost);
        $this->assertEquals(1000.0, (float) $project->gross_profit);
    }

    public function test_removing_pending_material_does_not_touch_inventory(): void
    {
        $owner = $this->owner();
        $product = $this->product('Cable 4mm', 50);
        $project = $this->project(1000, $owner->id);
        $material = app(ProjectService::class)->addMaterial($project, [
            'product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 50,
        ], $owner->id);

        $this->actingAs($owner)
            ->delete("/admin/projects/{$project->id}/materials/{$material->id}")
            ->assertRedirect();

        $this->assertSame(10, (int) $product->fresh()->current_quantity);
        $this->assertEquals(0.0, (float) $project->fresh()->material_cost);
    }

    public function test_project_payments_update_balance_and_reject_overpayment(): void
    {
        // TEST 5 / 11 — amount received reduces outstanding balance.
        $owner = $this->owner();
        $project = $this->project(1000, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/payments", [
                'payment_date' => now()->toDateString(),
                'amount' => 400,
                'payment_method' => 'bank',
                'reference' => 'TXN-1',
            ])
            ->assertRedirect();

        $project->refresh();
        $this->assertEquals(400.0, (float) $project->amount_received);
        $this->assertEquals(600.0, (float) $project->balance);

        $this->from("/admin/projects/{$project->id}")
            ->actingAs($owner)
            ->post("/admin/projects/{$project->id}/payments", [
                'payment_date' => now()->toDateString(),
                'amount' => 700,
                'payment_method' => 'cash',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        $this->assertEquals(400.0, (float) $project->fresh()->amount_received);
    }

    public function test_deleting_project_payment_restores_balance(): void
    {
        $owner = $this->owner();
        $project = $this->project(1000, $owner->id);
        $payment = app(ProjectService::class)->recordPayment($project, [
            'payment_date' => now()->toDateString(), 'amount' => 300, 'payment_method' => 'cash',
        ], $owner->id);

        $this->actingAs($owner)
            ->delete("/admin/projects/{$project->id}/payments/{$payment->id}")
            ->assertRedirect();

        $project->refresh();
        $this->assertEquals(0.0, (float) $project->amount_received);
        $this->assertEquals(1000.0, (float) $project->balance);
    }

    public function test_project_cost_and_gross_profit_match_spec_example(): void
    {
        // TEST 13 — spec §14 numbers: contract 4,500,000, cost 2,800,000, profit 1,700,000.
        $owner = $this->owner();
        $project = $this->project(4500000, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/materials", [
                'product_id' => $this->product('PV Panel 550W', 460000)->id,
                'quantity' => 5,
                'issue_now' => true,
            ])
            ->assertRedirect();

        foreach ([
            ['expense_type' => 'labour', 'amount' => 350000],
            ['expense_type' => 'transport', 'amount' => 80000],
            ['expense_type' => 'other', 'amount' => 70000],
        ] as $expense) {
            $this->actingAs($owner)
                ->post("/admin/projects/{$project->id}/expenses", [
                    'expense_type' => $expense['expense_type'],
                    'amount' => $expense['amount'],
                    'expense_date' => now()->toDateString(),
                ])
                ->assertRedirect();
        }

        $project->refresh();
        $this->assertEquals(2300000.0, (float) $project->material_cost);
        $this->assertEquals(350000.0, (float) $project->labour_cost);
        $this->assertEquals(80000.0, (float) $project->transport_cost);
        $this->assertEquals(70000.0, (float) $project->other_cost);
        $this->assertEquals(2800000.0, (float) $project->project_cost);
        $this->assertEquals(1700000.0, (float) $project->gross_profit);
    }

    public function test_changing_status_to_completed_stamps_completion_date(): void
    {
        $owner = $this->owner();
        $project = $this->project(1000, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/status", ['status' => 'completed'])
            ->assertRedirect();

        $project->refresh();
        $this->assertSame('completed', $project->status);
        $this->assertSame(now()->toDateString(), $project->completion_date?->toDateString());
    }

    public function test_technician_can_view_project_but_not_record_payments(): void
    {
        $owner = $this->owner();
        $project = $this->project(1000, $owner->id);
        $technician = $this->userWithRole('technician');

        $this->actingAs($technician)->get("/admin/projects/{$project->id}")->assertOk();
        $this->actingAs($technician)
            ->post("/admin/projects/{$project->id}/payments", [
                'payment_date' => now()->toDateString(), 'amount' => 100, 'payment_method' => 'cash',
            ])
            ->assertForbidden();
    }

    public function test_sales_staff_cannot_view_projects(): void
    {
        $sales = $this->userWithRole('sales');
        $this->actingAs($sales)->get('/admin/projects')->assertForbidden();
    }

    public function test_media_upload_publish_and_delete(): void
    {
        Storage::fake('public');

        $owner = $this->owner();
        $project = $this->project(1000, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/media", [
                'files' => [UploadedFile::fake()->image('before.jpg')],
                'stage' => 'before',
                'caption' => 'Site before work',
                'published' => true,
            ])
            ->assertRedirect();

        $media = $project->media()->first();
        $this->assertNotNull($media);
        $this->assertSame('image', $media->type);
        $this->assertTrue((bool) $media->published);
        Storage::disk('public')->assertExists($media->path);

        $this->actingAs($owner)
            ->post("/admin/projects/{$project->id}/media/{$media->id}/publish", ['published' => false])
            ->assertRedirect();
        $this->assertFalse((bool) $media->fresh()->published);

        $this->actingAs($owner)
            ->delete("/admin/projects/{$project->id}/media/{$media->id}")
            ->assertRedirect();
        Storage::disk('public')->assertMissing($media->path);
        $this->assertDatabaseMissing('project_media', ['id' => $media->id]);
    }
}