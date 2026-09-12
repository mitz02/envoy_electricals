<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $this->seedCategories();
        $this->seedExpenseCategories();
        $this->seedSettings();
        $this->seedOwner();

        $this->call(ProjectsSeeder::class);
        $this->call(StaffSeeder::class);
        $this->call(DemoProductsSeeder::class);
        $this->call(TrainingSeeder::class);
        $this->call(SolarPackageSeeder::class);
    }

    protected function seedOwner(): void
    {
        $ownerRole = Role::where('slug', 'owner')->first();

        User::updateOrCreate(
            ['email' => 'owner@envoyelectric.com'],
            [
                'name' => 'Envoy Owner',
                'password' => 'password',
                'role_id' => $ownerRole->id,
                'is_active' => true,
            ]
        );
    }

    protected function seedCategories(): void
    {
        $categories = [
            'Solar Panels', 'Inverters', 'Batteries', 'Cables', 'Breakers',
            'Switches', 'Sockets', 'LED Lights', 'Fans', 'Solar Accessories',
            'Electrical Accessories', 'Installation Materials', 'Other',
        ];

        foreach ($categories as $name) {
            ProductCategory::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            );
        }
    }

    protected function seedExpenseCategories(): void
    {
        $categories = [
            'Transport', 'Salary', 'Rent', 'Electricity', 'Internet',
            'Marketing', 'Maintenance', 'Logistics', 'Installation Expenses',
            'Office Expenses', 'Fuel', 'Other',
        ];

        foreach ($categories as $name) {
            ExpenseCategory::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            );
        }
    }

    protected function seedSettings(): void
    {
        $defaults = [
            ['key' => 'business.name', 'value' => 'Envoy Electric', 'group' => 'business'],
            ['key' => 'business.email', 'value' => 'hello@envoyelectric.com', 'group' => 'business'],
            ['key' => 'business.phone', 'value' => '+234 809 708 9259', 'group' => 'business'],
            ['key' => 'business.address', 'value' => 'Shop 1, Peace Avenue Junction, opp Goddy Royal Hotel, Futa Southgate Road, Akure', 'group' => 'business'],
            ['key' => 'currency.symbol', 'value' => '₦', 'group' => 'business'],
            ['key' => 'tax.rate', 'value' => '0', 'group' => 'sales'],
            ['key' => 'inventory.allow_negative', 'value' => '0', 'group' => 'inventory'],
            ['key' => 'bank.account_name', 'value' => 'Envoy Electricals', 'group' => 'bank'],
            ['key' => 'bank.account_number', 'value' => '5168265608', 'group' => 'bank'],
            ['key' => 'bank.bank_name', 'value' => 'Moniepoint MFB', 'group' => 'bank'],
            ['key' => 'bank.instructions', 'value' => 'Transfer the exact order amount and quote your order reference (ORD-...) as the narration. Our team verifies bank transfers before confirming your order.', 'group' => 'bank'],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}