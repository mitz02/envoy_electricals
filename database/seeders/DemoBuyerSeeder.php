<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Database\Seeder;

class DemoBuyerSeeder extends Seeder
{
    /**
     * Create a demo website buyer that staff can use to test the buyer flow.
     *
     * Idempotent and safe to run against a live database: it only ever creates
     * or updates the demo buyer account and its linked customer record.
     */
    public function run(): void
    {
        $buyerRole = Role::where('slug', 'buyer')->first();

        if (! $buyerRole) {
            throw new \RuntimeException('Buyer role must exist before seeding the demo buyer. Run RolesAndPermissionsSeeder first.');
        }

        $email = env('BUYER_EMAIL', 'buyer@envoyelectric.com');

        $buyer = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('BUYER_NAME', 'Tunde Demo'),
                'phone' => '08012345678',
                'password' => env('BUYER_PASSWORD', 'password'),
                'role_id' => $buyerRole->id,
                'is_active' => true,
            ]
        );

        if (! $buyer->customer) {
            $customer = $buyer->customer()->make([
                'ref_id' => ReferenceGenerator::generate('customer'),
                'name' => $buyer->name,
                'phone' => $buyer->phone,
                'customer_type' => 'regular',
            ]);
            // Like self-registered buyers, the demo buyer stays unassigned
            // (visible under "All Stores") until an admin assigns a branch.
            $customer->skipStoreAutoAssign = true;
            $customer->save();
        }

        $password = env('BUYER_PASSWORD', 'password');

        $this->command?->info("Demo buyer ready → email: {$email} / password: {$password}");
        $this->command?->info('   Sign in at /login · dashboard at /account');
        $this->command?->info('   Admin can manage this buyer under /admin/buyers.');
    }
}
