<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Services\ReferenceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        // [sku, name, category_slug, brand, cost, sell, qty, reorder, featured, available]
        $products = [
            // Solar Panels
            ['DEMO-SOL-550JA', 'JA Solar 550W Half-Cell Mono Panel', 'solar-panels', 'JA Solar', 480000, 520000, 26, 6, true, true],
            ['DEMO-SOL-550CA', 'Canadian Solar 550W Mono Panel', 'solar-panels', 'Canadian Solar', 470000, 510000, 18, 6, false, true],
            ['DEMO-SOL-450JK', 'JinkoSolar 450W Mono Panel', 'solar-panels', 'JinkoSolar', 380000, 430000, 0, 5, false, true],
            ['DEMO-SOL-300EN', 'Envoy 300W Poly Solar Panel', 'solar-panels', 'Envoy', 220000, 260000, 12, 4, false, true],
            ['DEMO-SOL-555LO', 'LONGi 555W Hi-MO 7 Bifacial Panel', 'solar-panels', 'LONGi', 500000, 560000, 10, 5, true, true],

            // Inverters
            ['DEMO-INV-5DE', 'Deye 5kW Hybrid Inverter', 'inverters', 'Deye', 1200000, 1350000, 8, 3, true, true],
            ['DEMO-INV-3GR', 'Growatt 3kVA Off-Grid Inverter', 'inverters', 'Growatt', 520000, 590000, 8, 3, false, true],
            ['DEMO-INV-8SU', 'Sunsynk 8kW Hybrid Inverter', 'inverters', 'Sunsynk', 2100000, 2350000, 3, 2, false, true],
            ['DEMO-INV-12VI', 'Victron Phoenix 12/800 Inverter', 'inverters', 'Victron', 320000, 365000, 5, 2, false, true],
            ['DEMO-INV-25AM', 'Amaze 2.5kVA Pure Sine Inverter', 'inverters', 'Amaze', 210000, 245000, 0, 4, false, true],

            // Batteries
            ['DEMO-BAT-5AL', 'Alpha ESS Smile 5.12kWh LiFePO4', 'batteries', 'Alpha ESS', 1500000, 1690000, 7, 2, true, true],
            ['DEMO-BAT-200GL', 'Glova 200Ah Deep Cycle Battery', 'batteries', 'Glova', 360000, 410000, 15, 5, false, true],
            ['DEMO-BAT-100FE', 'Felicity 100Ah Lithium Battery', 'batteries', 'Felicity Solar', 480000, 540000, 9, 3, false, true],
            ['DEMO-BAT-200LE', 'Lento 12V 200Ah Gel Battery', 'batteries', 'Lento', 280000, 320000, 22, 6, false, true],
            ['DEMO-BAT-13TE', 'Tesla Powerwall 13.5kWh Backup', 'batteries', 'Tesla', 4800000, 5200000, 0, 1, false, true],

            // Cables
            ['DEMO-CAB-10SF', '10mm² Solar Cable (Red) — 50m', 'cables', 'SolarFlex', 48000, 59000, 60, 15, false, true],
            ['DEMO-CAB-16BT', '16mm² Battery Cable (3m pair)', 'cables', 'SolarFlex', 25000, 32000, 80, 20, false, true],
            ['DEMO-CAB-25KA', '2.5mm² House Wiring Cable', 'cables', 'Kabelo', 15000, 19000, 120, 30, false, true],
            ['DEMO-CAB-40CU', '4mm² Armoured Power Cable', 'cables', 'Cutix', 21000, 27000, 45, 15, false, true],

            // Breakers
            ['DEMO-BRK-63SC', '2P 63A Miniature Circuit Breaker', 'breakers', 'Schneider', 4500, 6500, 200, 30, false, true],
            ['DEMO-BRK-125SC', '3P 125A Air MCB', 'breakers', 'Schneider', 12000, 16000, 40, 10, false, true],
            ['DEMO-BRK-32DC', 'DC 32A MCB for Solar', 'breakers', 'CHINT', 6000, 8500, 75, 15, false, true],

            // Switches
            ['DEMO-SWC-13LG', '13A Double Pole Switch', 'switches', 'Legrand', 2500, 3800, 150, 25, false, true],
            ['DEMO-SWC-1LG', 'Single Gang Light Switch', 'switches', 'Legrand', 1500, 2500, 200, 30, false, true],

            // Sockets
            ['DEMO-SOK-UPLG', 'Universal Power Socket', 'sockets', 'Legrand', 3000, 4500, 180, 30, false, true],
            ['DEMO-SOK-3MK', '3-Pin Industrial Socket', 'sockets', 'MK', 8000, 11000, 35, 10, false, true],

            // LED Lights
            ['DEMO-LED-18PH', 'LED Panel Light 18W Cool White', 'led-lights', 'Philips', 12000, 16000, 60, 12, false, true],
            ['DEMO-LED-100EN', 'Solar Street Light 100W', 'led-lights', 'Envoy', 85000, 102000, 6, 2, true, true],

            // Fans
            ['DEMO-FAN-16AP', '16" Pedestal Fan', 'fans', 'Apex', 18000, 24000, 30, 10, false, true],
            ['DEMO-FAN-52AP', '52" Ceiling Fan with Remote', 'fans', 'Apex', 45000, 58000, 12, 4, false, true],

            // Solar Accessories
            ['DEMO-ACC-20EP', '12V 20A PWM Charge Controller', 'solar-accessories', 'EPEVER', 15000, 21000, 40, 10, false, true],
            ['DEMO-ACC-30EP', '30A MPPT Charge Controller', 'solar-accessories', 'EPEVER', 35000, 45000, 25, 6, false, true],
            ['DEMO-ACC-MC4', 'MC4 Connector Pair', 'solar-accessories', 'Stäubli', 1500, 2500, 300, 50, false, true],

            // Electrical Accessories
            ['DEMO-ELX-6CV', '6-Way Distribution Box', 'electrical-accessories', 'CVC', 12000, 16500, 28, 8, false, true],
            ['DEMO-ELX-30CH', 'RCD 30mA 2P Residual Device', 'electrical-accessories', 'CHINT', 16000, 22000, 33, 8, false, true],

            // Installation Materials
            ['DEMO-INS-24RK', 'Roof Mounting Rail 2.4m', 'installation-materials', 'Mecc Alte', 14000, 18500, 50, 12, false, true],
            ['DEMO-INS-CLMP', 'End & Mid Clamp Set', 'installation-materials', 'Mecc Alte', 4000, 5500, 150, 30, false, true],

            // Other
            ['DEMO-OTH-150WH', 'Solar Water Heater 150L', 'other', 'Envoy', 350000, 415000, 4, 2, false, true],
        ];

        $imagesByCategory = [
            'solar-panels' => 'hero_solar_panels.jpg',
            'inverters' => 'inverter_tech.jpg',
            'batteries' => 'battery_bank.jpg',
            'cables' => 'solar_installation.jpg',
            'breakers' => 'technician_drill.jpg',
            'switches' => 'engineers_blueprint.jpg',
            'sockets' => 'workflow_tablet.jpg',
            'led-lights' => 'solar_city.jpg',
            'fans' => 'engineer_solar.jpg',
            'solar-accessories' => 'battery_storage_alt.jpg',
            'electrical-accessories' => 'workflow_team.jpg',
            'installation-materials' => 'solar_farm_wide.jpg',
            'other' => 'battery_storage.jpg',
        ];

        DB::transaction(function () use ($products, $imagesByCategory) {
            foreach ($products as [$sku, $name, $categorySlug, $brand, $cost, $sell, $qty, $reorder, $featured, $available]) {
                $category = ProductCategory::where('slug', $categorySlug)->first();

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'ref_id' => ReferenceGenerator::generate('product'),
                        'name' => $name,
                        'category_id' => $category->id ?? null,
                        'brand' => $brand,
                        'description' => 'Genuine '.$brand.' product supplied by Envoy Electric. '.$name.'. Quality-checked, fully warranted, and ready for nationwide delivery.',
                        'specifications' => $brand.' '.$name,
                        'unit' => 'pcs',
                        'cost_price' => $cost,
                        'selling_price' => $sell,
                        'average_cost' => $cost,
                        'current_quantity' => $qty,
                        'reorder_level' => $reorder,
                        'status' => 'active',
                        'is_featured' => $featured,
                        'is_visible_online' => $available,
                        'allow_online_purchase' => true,
                    ]
                );

                if ($product->images()->count() === 0 && isset($imagesByCategory[$categorySlug])) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => 'images/landing/'.$imagesByCategory[$categorySlug],
                        'alt' => $name,
                        'is_featured' => true,
                        'sort_order' => 0,
                    ]);
                }
            }
        });
    }
}