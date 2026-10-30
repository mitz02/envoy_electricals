<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'author_name' => 'Adebayo Johnson',
                'author_role' => 'Homeowner, Lagos',
                'content' => 'Envoy Electricals transformed our home with a 5kVA solar system. No more generator noise, no more fuel costs. Professional installation and excellent after-sales support!',
                'rating' => 5,
                'is_published' => true,
            ],
            [
                'author_name' => 'Mrs. Funke Adebayo',
                'author_role' => 'Business Owner, Ibadan',
                'content' => 'We installed a 15kVA system for our office. The team was punctual, clean, and knowledgeable. Our electricity bills dropped by 85%. Highly recommended!',
                'rating' => 5,
                'is_published' => true,
            ],
            [
                'author_name' => 'Engr. Chukwuemeka Okonkwo',
                'author_role' => 'Solar Installer, Abuja',
                'content' => 'I have been buying panels, inverters and batteries from Envoy for 3 years. Genuine products, fair prices, and they always honor warranties. My go-to supplier.',
                'rating' => 5,
                'is_published' => true,
            ],
            [
                'author_name' => 'Dr. Grace Okafor',
                'author_role' => 'Homeowner, Enugu',
                'content' => 'The training program gave me confidence to maintain my own system. Now I understand my inverter settings and battery management. Worth every naira!',
                'rating' => 4,
                'is_published' => true,
            ],
            [
                'author_name' => 'Alhaji Musa Ibrahim',
                'author_role' => 'Farmer, Kano',
                'content' => 'Solar water pumping system for my 5-hectare farm. Runs all day without fuel. Envoy designed the perfect system for my borehole. Excellent service!',
                'rating' => 5,
                'is_published' => true,
            ],
            [
                'author_name' => 'Chidinma Eze',
                'author_role' => 'Homeowner, Port Harcourt',
                'content' => 'Professional from quote to installation. They explained everything clearly, no hidden costs. My 3.5kVA system powers everything I need. Thank you Envoy!',
                'rating' => 5,
                'is_published' => true,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
