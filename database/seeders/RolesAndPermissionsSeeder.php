<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Permissions grouped by module ----
        $permissions = [
            ['dashboard', 'dashboard.view', 'View dashboard'],
            ['stores', 'stores.view', 'View stores'],
            ['stores', 'stores.create', 'Create stores'],
            ['stores', 'stores.edit', 'Edit stores'],
            ['stores', 'stores.delete', 'Delete stores'],
            ['products', 'products.view', 'View products'],
            ['products', 'products.create', 'Create products'],
            ['products', 'products.edit', 'Edit products'],
            ['products', 'products.price', 'Edit product prices'],
            ['products', 'products.delete', 'Delete products'],
            ['inventory', 'inventory.view', 'View inventory'],
            ['inventory', 'inventory.adjust', 'Adjust stock'],
            ['inventory', 'inventory.opening', 'Set opening stock'],
            ['inventory', 'inventory.view_cost', 'View product cost price'],
            ['purchases', 'purchases.view', 'View purchases'],
            ['purchases', 'purchases.create', 'Create purchases'],
            ['purchases', 'purchases.edit', 'Edit purchases'],
            ['purchases', 'purchases.void', 'Void purchases'],
            ['suppliers', 'suppliers.manage', 'Manage suppliers'],
            ['sales', 'sales.view', 'View sales'],
            ['sales', 'sales.create', 'Create sales'],
            ['sales', 'sales.void', 'Void sales'],
            ['sales', 'sales.profit', 'View sales profit'],
            ['payments', 'payments.view', 'View payments'],
            ['payments', 'payments.record', 'Record payments'],
            ['customers', 'customers.manage', 'Manage customers'],
            ['expenses', 'expenses.view', 'View expenses'],
            ['expenses', 'expenses.create', 'Create expenses'],
            ['expenses', 'expenses.edit', 'Edit expenses'],
            ['projects', 'projects.view', 'View projects'],
            ['projects', 'projects.create', 'Create projects'],
            ['projects', 'projects.edit', 'Edit projects'],
            ['projects', 'projects.delete', 'Delete projects'],
            ['projects', 'projects.materials', 'Issue project materials'],
            ['projects', 'projects.payments', 'Record project payments'],
            ['projects', 'projects.expenses', 'Record project expenses'],
            ['projects', 'projects.media', 'Upload project media'],
            ['staff', 'staff.view', 'View staff'],
            ['staff', 'staff.manage', 'Manage staff'],
            ['payroll', 'payroll.view', 'View payroll'],
            ['payroll', 'payroll.manage', 'Manage payroll'],
            ['assets', 'assets.manage', 'Manage assets'],
            ['solar', 'solar.view', 'View solar packages'],
            ['solar', 'solar.manage', 'Manage solar packages'],
            ['solar', 'solar.calculator_config', 'Configure solar calculator'],
            ['solar', 'solar.leads', 'View solar leads'],
            ['marketing', 'marketing.newsletter', 'Send newsletters'],
            ['marketing', 'marketing.testimonials', 'Manage testimonials'],
            ['website', 'website.content', 'Manage website content'],
            ['website', 'website.media', 'Manage media library'],
            ['reports', 'reports.view', 'View reports'],
            ['reports', 'reports.financial', 'View financial reports'],
            ['reports', 'reports.profit', 'View profit'],
            ['settings', 'settings.manage', 'Manage system settings'],
            ['settings', 'settings.roles', 'Manage roles & permissions'],
            ['audit', 'audit.view', 'View audit logs'],
            ['orders', 'orders.view', 'View online orders'],
            ['orders', 'orders.manage', 'Manage online orders'],
            ['feedback', 'feedback.manage', 'Manage feedback'],
            ['training', 'training.view', 'View training programs & trainees'],
            ['training', 'training.manage', 'Manage training, enrollments & certificates'],
        ];

        $isMysql = DB::connection()->getDriverName() === 'mysql';

        // MySQL refuses to TRUNCATE a table referenced by a foreign key, so the
        // checks are toggled off for the reset only (no-op on other drivers).
        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('permissions')->truncate();

        foreach ($permissions as [$module, $slug, $description]) {
            Permission::create([
                'name' => ucwords(str_replace('.', ' ', $slug)),
                'slug' => $slug,
                'module' => $module,
                'description' => $description,
            ]);
        }

        // ---- Roles ----
        DB::table('roles')->truncate();
        DB::table('role_permission')->truncate();

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        $owner = Role::create(['name' => 'Owner / Super Admin', 'slug' => 'owner', 'description' => 'Full access to everything.']);
        $manager = Role::create(['name' => 'Manager', 'slug' => 'manager', 'description' => 'Sales, purchases, inventory, customers, projects, expenses, reports.']);
        $sales = Role::create(['name' => 'Sales Staff', 'slug' => 'sales', 'description' => 'Create sales, view stock, record payments.']);
        $technician = Role::create(['name' => 'Technician', 'slug' => 'technician', 'description' => 'View assigned projects, update progress, upload media.']);
        $accountant = Role::create(['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Sales, purchases, expenses, payments, financial reports.']);
        $content = Role::create(['name' => 'Content Manager', 'slug' => 'content', 'description' => 'Website content, media, testimonials, newsletters.']);
        $trainee = Role::create(['name' => 'Trainee', 'slug' => 'trainee', 'description' => 'Academy trainee with portal access only.']);
        Role::create(['name' => 'Buyer', 'slug' => 'buyer', 'description' => 'Registered online customer with a buyer account only.']);

        $manager->permissions()->sync($this->ids([
            'dashboard.view', 'stores.view', 'stores.create', 'stores.edit',
            'products.view', 'products.create', 'products.edit',
            'inventory.view', 'inventory.adjust', 'purchases.view', 'purchases.create', 'purchases.edit',
            'suppliers.manage', 'sales.view', 'sales.create', 'customers.manage',
            'expenses.view', 'expenses.create', 'expenses.edit', 'projects.view',
            'projects.create', 'projects.edit', 'projects.delete', 'projects.materials',
            'projects.payments', 'projects.expenses', 'reports.view',
            'payments.view', 'payments.record', 'staff.view', 'payroll.view',
            'training.view', 'training.manage',
        ]));

        $sales->permissions()->sync($this->ids([
            'dashboard.view', 'products.view', 'inventory.view', 'sales.view',
            'sales.create', 'customers.manage', 'payments.record',
        ]));

        $technician->permissions()->sync($this->ids([
            'projects.view', 'projects.media',
        ]));

        $accountant->permissions()->sync($this->ids([
            'dashboard.view', 'products.view', 'inventory.view', 'inventory.view_cost',
            'purchases.view', 'purchases.create', 'suppliers.manage', 'sales.view',
            'customers.manage', 'expenses.view', 'expenses.create', 'reports.view',
            'reports.financial', 'reports.profit', 'payments.view', 'payments.record',
            'projects.view', 'projects.payments', 'projects.expenses',
            'staff.view', 'payroll.view', 'payroll.manage',
        ]));

        $content->permissions()->sync($this->ids([
            'website.content', 'website.media', 'marketing.newsletter',
            'marketing.testimonials', 'feedback.manage', 'projects.view', 'projects.media',
        ]));

        $owner->permissions()->sync(Permission::pluck('id')->all());
    }

    protected function ids(array $slugs): array
    {
        return Permission::whereIn('slug', $slugs)->pluck('id')->all();
    }
}
