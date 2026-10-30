<?php

namespace Tests\Feature;

use App\Mail\QuotationMail;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\SolarCalculation;
use App\Models\SolarPackage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SolarLeadQuotationTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function package(array $overrides = []): SolarPackage
    {
        return SolarPackage::create(array_merge([
            'ref_id' => 'SOL-PKG-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => '5kVA Home Package',
            'package_price' => 2500000,
            'estimated_load_capacity' => '3.5kW',
            'inverter_capacity' => '5kva',
            'availability' => 'available',
            'is_visible_online' => false,
        ], $overrides));
    }

    public function test_create_quotation_page_renders_existing_packages_including_hidden_ones(): void
    {
        $owner = $this->owner();
        $this->package(['name' => 'Hidden 5kVA Kit', 'is_visible_online' => false]);

        $this->actingAs($owner)
            ->get('/admin/solar-leads/quotations/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SolarLeads/CreateQuotation')
                ->has('packages', 1)
                ->where('packages.0.name', 'Hidden 5kVA Kit'));
    }

    public function test_manual_quotation_from_existing_package_is_accepted(): void
    {
        $owner = $this->owner();
        $package = $this->package();

        $this->actingAs($owner)
            ->post('/admin/solar-leads/quotations', [
                'customer_name' => 'Ada Lovelace',
                'customer_phone' => '08012345678',
                'customer_email' => 'ada@example.com',
                'location' => 'Akure',
                'solar_package_id' => $package->id,
                'additional_logistics' => 50000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quotations', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'solar_package_id' => $package->id,
            'estimated_price' => 2500000,
            'additional_logistics' => 50000,
            'status' => 'quoted',
        ]);
    }

    public function test_lead_quotation_uses_selected_package_price(): void
    {
        $owner = $this->owner();
        $package = $this->package(['package_price' => 3100000]);

        $lead = SolarCalculation::create([
            'ref_id' => 'CALC-TEST-1',
            'customer_name' => 'Lead Customer',
            'customer_phone' => '08000000001',
            'customer_email' => 'lead@example.com',
            'estimated_price' => 900000,
            'recommended_inverter' => '3kva',
            'lead_status' => 'new',
        ]);

        $this->actingAs($owner)
            ->post("/admin/solar-leads/{$lead->id}/quotation", [
                'solar_package_id' => $package->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quotations', [
            'customer_email' => 'lead@example.com',
            'solar_package_id' => $package->id,
            'estimated_price' => 3100000,
        ]);
        $this->assertDatabaseHas('solar_calculations', [
            'id' => $lead->id,
            'lead_status' => 'quoted',
        ]);
    }

    public function test_quotation_can_be_sent_by_email(): void
    {
        Mail::fake();

        $owner = $this->owner();
        $package = $this->package();
        $quotation = Quotation::create([
            'ref_id' => 'QUO-TEST-1',
            'customer_name' => 'Ada Lovelace',
            'customer_phone' => '08012345678',
            'customer_email' => 'ada@example.com',
            'solar_package_id' => $package->id,
            'estimated_price' => 2500000,
            'status' => 'quoted',
        ]);

        $this->actingAs($owner)
            ->post("/admin/solar-leads/quotations/{$quotation->id}/send")
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(QuotationMail::class, function (QuotationMail $mail) use ($quotation) {
            return $mail->quotation->is($quotation)
                && $mail->hasTo('ada@example.com');
        });
    }

    public function test_send_quotation_without_email_is_rejected(): void
    {
        Mail::fake();

        $owner = $this->owner();
        $quotation = Quotation::create([
            'ref_id' => 'QUO-TEST-2',
            'customer_name' => 'No Email',
            'customer_phone' => '08012345678',
            'estimated_price' => 1000000,
            'status' => 'quoted',
        ]);

        $this->actingAs($owner)
            ->from("/admin/solar-leads/quotations/{$quotation->id}")
            ->post("/admin/solar-leads/quotations/{$quotation->id}/send")
            ->assertRedirect("/admin/solar-leads/quotations/{$quotation->id}")
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }
}
