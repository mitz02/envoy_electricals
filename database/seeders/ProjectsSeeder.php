<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ProjectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        if (Project::count() > 0) {
            return;
        }

        DB::transaction(function () {
            $inventory = app(InventoryService::class);
            $projects = app(ProjectService::class);

            $managerRole = Role::where('slug', 'manager')->first();
            $technicianRole = Role::where('slug', 'technician')->first();

            $owner = User::where('email', 'owner@envoyelectric.com')->first();

            $ada = User::updateOrCreate(
                ['email' => 'ada@envoyelectric.com'],
                ['name' => 'Ada Manager', 'password' => 'password', 'role_id' => $managerRole->id, 'is_active' => true]
            );
            $mark = User::updateOrCreate(
                ['email' => 'mark@envoyelectric.com'],
                ['name' => 'Mark Technician', 'password' => 'password', 'role_id' => $technicianRole->id, 'is_active' => true]
            );

            $john = Customer::updateOrCreate(
                ['phone' => '+234 801 234 5678'],
                [
                    'ref_id' => 'CUS-' . now()->format('Y') . '-000001',
                    'name' => 'Mr John Adeyemi',
                    'email' => 'john@example.com',
                    'address' => '12 Admiralty Way, Lekki Phase 1',
                    'location' => 'Lekki, Lagos',
                    'customer_type' => 'regular',
                ]
            );

            $mall = Customer::updateOrCreate(
                ['phone' => '+234 809 555 0199'],
                [
                    'ref_id' => 'CUS-' . now()->format('Y') . '-000002',
                    'name' => 'Ikeja City Mall',
                    'email' => 'facilities@ikcmall.ng',
                    'address' => 'Obafemi Awolowo Way, Ikeja',
                    'location' => 'Ikeja, Lagos',
                    'customer_type' => 'corporate',
                ]
            );

            $solarCat = ProductCategory::where('slug', 'solar-panels')->first() ?? new ProductCategory(['name' => 'Solar Panels']);
            $inverterCat = ProductCategory::where('slug', 'inverters')->first() ?? new ProductCategory(['name' => 'Inverters']);
            $batteryCat = ProductCategory::where('slug', 'batteries')->first() ?? new ProductCategory(['name' => 'Batteries']);
            $lightCat = ProductCategory::where('slug', 'led-lights')->first() ?? new ProductCategory(['name' => 'LED Lights']);

            $panel = $this->product($inventory, 'EV-SOL-550W-001', 'Solar Panel 550W Mono', 10, 460000, 520000, $solarCat);
            $inverter = $this->product($inventory, 'EV-INV-5KVA-001', 'Inverter 5kVA Hybrid', 8, 1200000, 1350000, $inverterCat);
            $battery = $this->product($inventory, 'EV-BAT-200AH-001', 'Battery 200Ah Deep Cycle', 12, 350000, 410000, $batteryCat);
            $led = $this->product($inventory, 'EV-LED-12W-001', 'LED Floodlight 12W', 60, 12000, 15000, $lightCat);

            $user = $owner ?: $ada;

            // Spec §14 example project — contract 4,500,000, cost 2,800,000, profit 1,700,000.
            $solarProject = $projects->create([
                'name' => 'Mr John Solar Installation',
                'description' => 'Full 5kW off-grid solar system: 5× 550W panels, 5kVA hybrid inverter, 200Ah battery bank and direct wiring.',
                'customer_id' => $john->id,
                'customer_address' => $john->address,
                'location' => 'Lekki, Lagos',
                'contract_value' => 4500000,
                'status' => Project::STATUS_IN_PROGRESS,
                'start_date' => now()->subDays(21)->toDateString(),
                'expected_completion_date' => now()->addDays(9)->toDateString(),
                'assigned_user_id' => $ada->id,
                'technician_user_id' => $mark->id,
                'notes' => 'Customer paid an advance. Banter street gate must be closed for delivery.',
            ], $user->id);

            // Exactly the spec's material line: 5 × solar panel @ ₦460,000 = ₦2,300,000.
            $projects->addMaterial($solarProject, ['product_id' => $panel->id, 'quantity' => 5, 'issue_now' => true], $user->id);

            $projects->addExpense($solarProject, [
                'expense_type' => 'labour', 'amount' => 350000,
                'expense_date' => now()->subDays(20)->toDateString(), 'payee' => 'Installation Team',
            ], $user->id);
            $projects->addExpense($solarProject, [
                'expense_type' => 'transport', 'amount' => 80000,
                'expense_date' => now()->subDays(20)->toDateString(), 'payee' => 'LogisticHub',
            ], $user->id);
            $projects->addExpense($solarProject, [
                'expense_type' => 'other', 'amount' => 70000,
                'expense_date' => now()->subDays(18)->toDateString(), 'payee' => 'Skilled Labour Permit',
            ], $user->id);

            $projects->recordPayment($solarProject, [
                'payment_date' => now()->subDays(21)->toDateString(),
                'amount' => 2000000,
                'payment_method' => 'bank',
                'reference' => 'TXN-9841203',
                'remarks' => 'Advance deposit',
            ], $user->id);

            // Completed project — fully paid, profit positive.
            $mallProject = $projects->create([
                'name' => 'Ikeja Mall Lighting Upgrade',
                'description' => 'Replaced 40 mall walkway floodlights with energy-efficient LED units including mounting and control.',
                'customer_id' => $mall->id,
                'customer_address' => $mall->address,
                'location' => 'Ikeja, Lagos',
                'contract_value' => 1200000,
                'status' => Project::STATUS_COMPLETED,
                'start_date' => now()->subMonths(2)->toDateString(),
                'completion_date' => now()->subDays(12)->toDateString(),
                'assigned_user_id' => $ada->id,
                'technician_user_id' => $mark->id,
                'notes' => 'Warranty cover 12 months.',
            ], $user->id);

            $projects->addMaterial($mallProject, ['product_id' => $led->id, 'quantity' => 40, 'issue_now' => true], $user->id);
            $projects->addExpense($mallProject, [
                'expense_type' => 'labour', 'amount' => 100000,
                'expense_date' => now()->subMonths(2)->toDateString(), 'payee' => 'Installation Team',
            ], $user->id);
            $projects->addExpense($mallProject, [
                'expense_type' => 'transport', 'amount' => 20000,
                'expense_date' => now()->subMonths(2)->toDateString(), 'payee' => 'LogisticHub',
            ], $user->id);

            $projects->recordPayment($mallProject, [
                'payment_date' => now()->subDays(12)->toDateString(),
                'amount' => 1200000,
                'payment_method' => 'bank',
                'reference' => 'TXN-7710294',
            ], $user->id);

            // Quotation — no work started.
            $projects->create([
                'name' => 'Ogba Estate Emergency Backup',
                'description' => 'Proposed 3kW backup inverter system for the estate hall and borehole pump.',
                'customer_id' => null,
                'location' => 'Ogba, Lagos',
                'contract_value' => 800000,
                'status' => Project::STATUS_QUOTATION,
                'start_date' => now()->addDays(3)->toDateString(),
                'expected_completion_date' => now()->addWeeks(4)->toDateString(),
                'assigned_user_id' => $ada->id,
                'notes' => 'Awaiting client budget approval.',
            ], $user->id);
        });
    }

    protected function product(InventoryService $inventory, string $sku, string $name, int $qty, float $cost, float $price, ?ProductCategory $category): Product
    {
        $product = Product::updateOrCreate(
            ['sku' => $sku],
            [
                'ref_id' => 'EV-PROD-' . str_pad((string) (Product::count() + 1), 6, '0', STR_PAD_LEFT),
                'name' => $name,
                'category_id' => $category?->id,
                'unit' => 'pcs',
                'cost_price' => $cost,
                'selling_price' => $price,
                'reorder_level' => 2,
                'status' => 'active',
                'is_visible_online' => true,
            ]
        );

        $inventory->setOpeningStock($product, $qty, $cost);

        return $product;
    }
}