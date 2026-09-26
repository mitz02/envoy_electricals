<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'code' => 'LAG',
                'name' => 'Lagos Main Branch',
                'address' => '123 Lagos Street, Victoria Island, Lagos',
                'phone' => '+234 800 123 4567',
                'email' => 'lagos@envoyelectric.ng',
                'is_default' => true,
            ],
            [
                'code' => 'ABJ',
                'name' => 'Abuja Branch',
                'address' => '456 Abuja Avenue, Wuse 2, Abuja',
                'phone' => '+234 800 987 6543',
                'email' => 'abuja@envoyelectric.ng',
                'is_default' => false,
            ],
        ];

        foreach ($stores as $store) {
            Store::updateOrCreate(
                ['code' => $store['code']],
                [
                    'name' => $store['name'],
                    'address' => $store['address'],
                    'phone' => $store['phone'],
                    'email' => $store['email'],
                    'is_active' => true,
                    'is_default' => $store['is_default'],
                ],
            );
        }
    }
}
