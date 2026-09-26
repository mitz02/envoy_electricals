<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add the "buyer" role to an existing database without truncating roles.
     *
     * Fresh installs get the role from RolesAndPermissionsSeeder; this migration
     * covers databases that already ran that seeder before buyers were added.
     * The buyer role intentionally has no permissions, so buyers can never
     * reach the admin area.
     */
    public function up(): void
    {
        if (! Role::where('slug', 'buyer')->exists()) {
            Role::create([
                'name' => 'Buyer',
                'slug' => 'buyer',
                'description' => 'Registered online customer with a buyer account only.',
            ]);
        }
    }

    public function down(): void
    {
        Role::where('slug', 'buyer')->delete();
    }
};
