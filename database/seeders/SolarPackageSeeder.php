<?php

namespace Database\Seeders;

use App\Models\SolarPackage;
use App\Models\SolarPackageItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class SolarPackageSeeder extends Seeder
{
    public function run(): void
    {
        if (SolarPackage::count() > 0) {
            return;
        }

        $products = Product::where('is_visible_online', true)->get()->keyBy('name');

        $year = now()->format('Y');
        $baseCount = SolarPackage::count();

        $packagesData = [
            [
                'name' => 'Home Starter 1.5kW',
                'description' => 'Perfect for small homes and apartments. Powers essential loads: lights, TV, fan, phone charging, and small fridge. Ideal for 1-2 bedroom flats with moderate energy needs.',
                'package_price' => 650000,
                'installation_cost' => 80000,
                'estimated_load_capacity' => 1500,
                'inverter_capacity' => '1kva',
                'warranty' => '5 Years Inverter, 10 Years Panels',
                'is_featured' => true,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '2 × 450W Monocrystalline',
                    'Inverter' => '1.5kVA Hybrid (MPPT)',
                    'Battery' => '1 × 200Ah Tubular',
                    'Mounting' => 'Roof Mount Kit',
                    'Protection' => 'DC/AC Breakers, Surge Protector',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 2],
                    ['product_name' => 'Amaze 2.5kVA Pure Sine Inverter', 'quantity' => 1],
                    ['product_name' => 'Lento 12V 200Ah Gel Battery', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Home Essential 3kW',
                'description' => 'Our most popular package for standard 2-3 bedroom homes. Runs lights, TV, fans, fridge, freezer, washing machine, and electronics. Includes lithium battery for longer lifespan.',
                'package_price' => 1250000,
                'installation_cost' => 120000,
                'estimated_load_capacity' => 3000,
                'inverter_capacity' => '3kva',
                'warranty' => '5 Years Inverter, 10 Years Panels, 10 Years Battery',
                'is_featured' => true,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '4 × 450W Monocrystalline',
                    'Inverter' => '3kVA Hybrid (Dual MPPT)',
                    'Battery' => '1 × 5kWh LiFePO4 (48V)',
                    'Mounting' => 'Roof Mount Kit with Rails',
                    'Protection' => 'DC/AC Breakers, SPD, Isolator',
                    'Monitoring' => 'WiFi/Bluetooth Dongle',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 4],
                    ['product_name' => 'Growatt 3kVA Off-Grid Inverter', 'quantity' => 1],
                    ['product_name' => 'Alpha ESS Smile 5.12kWh LiFePO4', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Home Plus 5kW',
                'description' => 'For larger homes with high energy demands. Powers everything in 3kW plus: AC (1.5HP), water pump, microwave, and additional appliances. Dual battery for extended backup.',
                'package_price' => 2100000,
                'installation_cost' => 180000,
                'estimated_load_capacity' => 5000,
                'inverter_capacity' => '5kva',
                'warranty' => '5 Years Inverter, 10 Years Panels, 10 Years Battery',
                'is_featured' => true,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '8 × 450W Monocrystalline',
                    'Inverter' => '5kVA Hybrid (Dual MPPT)',
                    'Battery' => '2 × 5kWh LiFePO4 (48V)',
                    'Mounting' => 'Roof Mount Kit with Rails',
                    'Protection' => 'DC/AC Breakers, SPD, Isolator',
                    'Monitoring' => 'WiFi/Bluetooth Dongle',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 8],
                    ['product_name' => 'Inverter 5kVA Hybrid', 'quantity' => 1],
                    ['product_name' => 'Alpha ESS Smile 5.12kWh LiFePO4', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Business Starter 5kW',
                'description' => 'Designed for small offices, shops, and clinics. Powers computers, printers, lighting, POS, CCTV, and essential equipment during outages. Fast ROI for small businesses.',
                'package_price' => 2300000,
                'installation_cost' => 200000,
                'estimated_load_capacity' => 5000,
                'inverter_capacity' => '5kva',
                'warranty' => '5 Years Inverter, 10 Years Panels, 10 Years Battery',
                'is_featured' => false,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '8 × 450W Monocrystalline',
                    'Inverter' => '5kVA 3-Phase Hybrid',
                    'Battery' => '2 × 5kWh LiFePO4 (48V)',
                    'Mounting' => 'Roof/Ground Mount Kit',
                    'Protection' => 'DC/AC Breakers, SPD, Isolator',
                    'Monitoring' => 'WiFi/Bluetooth + Cloud Portal',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 8],
                    ['product_name' => 'Inverter 5kVA Hybrid', 'quantity' => 1],
                    ['product_name' => 'Alpha ESS Smile 5.12kWh LiFePO4', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Business Pro 10kW',
                'description' => 'For medium businesses: offices, schools, restaurants, and small factories. Runs heavy loads including multiple ACs, industrial fridges, and machinery. Expandable design.',
                'package_price' => 4200000,
                'installation_cost' => 350000,
                'estimated_load_capacity' => 10000,
                'inverter_capacity' => '10kva',
                'warranty' => '5 Years Inverter, 10 Years Panels, 10 Years Battery',
                'is_featured' => true,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '16 × 450W Monocrystalline',
                    'Inverter' => '10kVA 3-Phase Hybrid',
                    'Battery' => '4 × 5kWh LiFePO4 (48V)',
                    'Mounting' => 'Ground Mount Structure',
                    'Protection' => 'DC Combiner, AC Panel, SPD',
                    'Monitoring' => 'Cloud Portal + Mobile App',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 16],
                    ['product_name' => 'Sunsynk 8kW Hybrid Inverter', 'quantity' => 1],
                    ['product_name' => 'Alpha ESS Smile 5.12kWh LiFePO4', 'quantity' => 4],
                ],
            ],
            [
                'name' => 'Industrial 20kW',
                'description' => 'Heavy-duty solution for factories, large farms, hotels, and estates. High-capacity 3-phase system with generator integration. Modular design for future expansion.',
                'package_price' => 7800000,
                'installation_cost' => 600000,
                'estimated_load_capacity' => 20000,
                'inverter_capacity' => '15kva',
                'warranty' => '5 Years Inverter, 10 Years Panels, 10 Years Battery',
                'is_featured' => false,
                'availability' => 'unavailable',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '32 × 450W Monocrystalline',
                    'Inverter' => '20kVA 3-Phase Hybrid (Parallel Ready)',
                    'Battery' => '8 × 5kWh LiFePO4 (48V)',
                    'Mounting' => 'Ground Mount with Tracking Option',
                    'Protection' => 'DC Combiner Box, AC Panel, SPD, Isolation',
                    'Monitoring' => 'Advanced SCADA + Cloud Portal',
                    'Integration' => 'Generator Auto-Start Controller',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 32],
                    ['product_name' => 'Sunsynk 8kW Hybrid Inverter', 'quantity' => 2],
                    ['product_name' => 'Alpha ESS Smile 5.12kWh LiFePO4', 'quantity' => 8],
                ],
            ],
            [
                'name' => 'Solar Water Pumping 2kW',
                'description' => 'Complete solar pumping solution for boreholes and surface water. Includes submersible pump, controller, and solar array. Ideal for farms, irrigation, and community water.',
                'package_price' => 950000,
                'installation_cost' => 150000,
                'estimated_load_capacity' => 2000,
                'inverter_capacity' => '2.5kva',
                'warranty' => '3 Years Controller, 10 Years Panels, 2 Years Pump',
                'is_featured' => false,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panels' => '6 × 450W Monocrystalline',
                    'Controller' => '2kVA MPPT Solar Pump VFD',
                    'Pump' => '1.5kW Submersible (Stainless Steel)',
                    'Mounting' => 'Pole/Ground Mount Structure',
                    'Protection' => 'Surge Protector, Dry-Run Sensor',
                    'Accessories' => 'Water Level Sensors, Float Switch',
                ],
                'items' => [
                    ['product_name' => 'JinkoSolar 450W Mono Panel', 'quantity' => 6],
                    ['product_name' => '30A MPPT Charge Controller', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Portable Solar Generator 500W',
                'description' => 'Compact, portable power station for camping, outdoor events, and emergency backup. Powers laptops, phones, lights, fans, and small appliances. No installation required.',
                'package_price' => 320000,
                'installation_cost' => 0,
                'estimated_load_capacity' => 500,
                'inverter_capacity' => '1kva',
                'warranty' => '2 Years Unit, 1 Year Battery',
                'is_featured' => false,
                'availability' => 'available',
                'is_visible_online' => true,
                'components_json' => [
                    'Solar Panel' => '1 × 120W Foldable (Included)',
                    'Battery' => '500Wh LiFePO4 (Built-in)',
                    'Inverter' => '500W Pure Sine Wave (Built-in)',
                    'Ports' => 'AC, USB-C PD, USB-A, DC 12V, Car Port',
                    'Charging' => 'Solar, AC Wall, Car Charger',
                ],
                'items' => [
                    ['product_name' => 'LED Floodlight 12W', 'quantity' => 1],
                ],
            ],
        ];

        foreach ($packagesData as $index => $packageData) {
            $items = $packageData['items'];
            unset($packageData['items']);

            $packageData['ref_id'] = "SOL-PKG-{$year}-" . str_pad($baseCount + $index + 1, 6, '0', STR_PAD_LEFT);

            $package = SolarPackage::create($packageData);

            foreach ($items as $item) {
                $product = $products->get($item['product_name']);
                if ($product) {
                    SolarPackageItem::create([
                        'solar_package_id' => $package->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
        }
    }
}
