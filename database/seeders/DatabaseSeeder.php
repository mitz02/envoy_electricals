<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $this->call(StoreSeeder::class);

        $this->seedCategories();
        $this->seedExpenseCategories();
        $this->seedSettings();
        $this->seedOwner();
        $this->seedAdmin();

        $this->call(StaffSeeder::class);
        $this->call(DemoProductsSeeder::class);
        $this->call(DemoBuyerSeeder::class);
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

    protected function seedAdmin(): void
    {
        $ownerRole = Role::where('slug', 'owner')->first();

        if (! $ownerRole) {
            throw new \RuntimeException('Owner role must exist before seeding the admin user.');
        }

        $email = env('ADMIN_EMAIL', 'admin@gmail.com');
        $name = env('ADMIN_NAME', 'Super Admin');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = env('ADMIN_PASSWORD', 'password');

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role_id' => $ownerRole->id,
                'is_active' => true,
            ]);

            $this->command?->warn("Admin user created: {$email} (role: owner). Set ADMIN_EMAIL / ADMIN_NAME / ADMIN_PASSWORD in .env to customize, or edit the users table directly.");
            $this->command?->warn('Change the password immediately after first login — see /admin settings or `php artisan user:set-password`.');

            return;
        }

        // The user already exists — keep name/role active in sync but NEVER reset
        // the password, so any edits made directly in the database are respected.
        $user->update([
            'name' => $name,
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);

        $this->command?->line("Admin user {$email} already exists — password left unchanged (edit it in the users table or with `php artisan user:set-password`).");
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
                ['slug' => Str::slug($name)],
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
                ['slug' => Str::slug($name)],
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
