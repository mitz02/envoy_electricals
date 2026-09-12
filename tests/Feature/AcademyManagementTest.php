<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Trainee;
use App\Models\Training;
use App\Models\User;
use App\Services\AcademyService;
use App\Services\PaystackService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    protected function training(array $overrides = []): Training
    {
        return Training::create(array_merge([
            'ref_id' => 'TRG-' . now()->format('Y') . '-000001',
            'title' => 'Solar Installation Basics',
            'description' => 'Foundations of solar installation.',
            'duration_weeks' => 6,
            'level' => Training::LEVEL_BEGINNER,
            'price' => 150000,
            'capacity' => 25,
            'is_active' => true,
            'created_by' => null,
        ], $overrides));
    }

    protected function trainee(): Trainee
    {
        return app(AcademyService::class)->createTrainee([
            'name' => 'Chidi Apprentice',
            'email' => 'chidi@academy.test',
            'phone' => '08033334444',
            'type' => Trainee::TYPE_APPRENTICE,
        ], null);
    }

    public function test_admin_can_create_training_program(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/training', [
                'title' => 'Advanced Inverters',
                'description' => 'Deep dive into inverter systems.',
                'curriculum' => "Week 1: Safety\nWeek 2: Sizing",
                'duration_weeks' => 8,
                'level' => Training::LEVEL_ADVANCED,
                'price' => 250000,
                'capacity' => 15,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('trainings', [
            'title' => 'Advanced Inverters',
            'level' => Training::LEVEL_ADVANCED,
            'price' => 250000,
            'is_active' => true,
        ]);

        $training = Training::where('title', 'Advanced Inverters')->first();
        $this->assertStringStartsWith('TRG-' . now()->format('Y') . '-', $training->ref_id);
    }

    public function test_training_program_rejects_invalid_level(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/training', [
                'title' => 'Bad Level',
                'level' => 'expert',
            ])
            ->assertSessionHasErrors('level');

        $this->assertDatabaseCount('trainings', 0);
    }

    public function test_admin_can_create_trainee_without_login(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/admin/trainees', [
                'type' => Trainee::TYPE_TRAINEE,
                'name' => 'Dara Trainee',
                'phone' => '08055556666',
                'email' => 'dara@academy.test',
                'status' => Trainee::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('trainees', [
            'email' => 'dara@academy.test',
            'user_id' => null,
            'status' => Trainee::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_create_trainee_with_login(): void
    {
        $owner = $this->owner();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->actingAs($owner)
            ->post('/admin/trainees', [
                'type' => Trainee::TYPE_TRAINEE,
                'name' => 'Emeka Trainee',
                'email' => 'emeka@academy.test',
                'status' => Trainee::STATUS_ACTIVE,
                'create_login' => true,
                'password' => 'password123',
            ])
            ->assertRedirect();

        $user = User::where('email', 'emeka@academy.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::where('slug', 'trainee')->value('id'), $user->role_id);
        $this->assertDatabaseHas('trainees', ['email' => 'emeka@academy.test', 'user_id' => $user->id]);
    }

    public function test_enroll_marks_enrollment_and_blocks_duplicates(): void
    {
        $owner = $this->owner();
        $trainee = $this->trainee();
        $training = $this->training();

        $this->actingAs($owner)
            ->post("/admin/trainees/{$trainee->id}/enroll", ['training_id' => $training->id])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
            'status' => Enrollment::STATUS_ENROLLED,
            'progress' => 0,
        ]);

        $enrollment = Enrollment::where('trainee_id', $trainee->id)->first();
        $this->assertStringStartsWith('ENR-', $enrollment->ref_id);

        $this->from("/admin/trainees/{$trainee->id}")
            ->actingAs($owner)
            ->post("/admin/trainees/{$trainee->id}/enroll", ['training_id' => $training->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, $trainee->enrollments()->count());
    }

    public function test_completing_progress_issues_certificate(): void
    {
        $owner = $this->owner();
        $trainee = $this->trainee();
        $training = $this->training();

        $enrollment = app(AcademyService::class)->enroll($trainee, $training->id, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/enrollments/{$enrollment->id}/progress", [
                'progress' => 100,
                'grade' => 92.5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $enrollment->refresh();
        $this->assertSame(Enrollment::STATUS_COMPLETED, $enrollment->status);
        $this->assertSame(92.5, (float) $enrollment->grade);

        $this->assertDatabaseHas('certificates', [
            'enrollment_id' => $enrollment->id,
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
            'status' => Certificate::STATUS_ISSUED,
            'grade' => 92.5,
        ]);

        $certificate = $enrollment->certificate;
        $this->assertStringStartsWith('CERT-' . now()->format('Y') . '-', $certificate->certificate_no);
    }

    public function test_partial_progress_does_not_issue_certificate(): void
    {
        $owner = $this->owner();
        $trainee = $this->trainee();
        $training = $this->training();

        $enrollment = app(AcademyService::class)->enroll($trainee, $training->id, $owner->id);

        $this->actingAs($owner)
            ->post("/admin/enrollments/{$enrollment->id}/progress", ['progress' => 40])
            ->assertRedirect();

        $this->assertSame(Enrollment::STATUS_IN_PROGRESS, $enrollment->fresh()->status);
        $this->assertSame(0, Certificate::count());
    }

    public function test_admin_can_void_certificate(): void
    {
        $owner = $this->owner();
        $trainee = $this->trainee();
        $training = $this->training();

        $enrollment = app(AcademyService::class)->enroll($trainee, $training->id, $owner->id);
        $certificate = app(AcademyService::class)->issueCertificate($enrollment, 88.0, $owner->id);

        $this->from('/admin/certificates')
            ->actingAs($owner)
            ->post("/admin/certificates/{$certificate->id}/void")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Certificate::STATUS_VOID, $certificate->fresh()->status);
        $this->assertNotNull($certificate->fresh()->voided_by);
    }

    public function test_role_without_training_permission_is_denied(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $sales = User::factory()->create([
            'role_id' => Role::where('slug', 'sales')->first()->id,
        ]);

        $this->actingAs($sales)->get('/admin/training')->assertForbidden();
        $this->actingAs($sales)->get('/admin/certificates')->assertForbidden();
    }

    public function test_portal_trainee_can_enroll_and_view_own_certificate(): void
    {
        $owner = $this->owner();
        $training = $this->training(['price' => 0]);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => 'Portal Trainee',
            'email' => 'portal@academy.test',
            'phone' => '08077778888',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        $user = $trainee->user;

        $this->actingAs($user)
            ->post(route('portal.enroll', $training->id))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('enrollments', [
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
        ]);

        $enrollment = Enrollment::where('trainee_id', $trainee->id)->first();
        $certificate = app(AcademyService::class)->issueCertificate($enrollment, 90.0, $owner->id);

        $this->actingAs($user)
            ->get(route('portal.certificates.show', $certificate->id))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('portal.certificates.show', $certificate->id))
            ->assertOk();
    }

    public function test_trainee_cannot_view_another_trainees_certificate(): void
    {
        $owner = $this->owner();
        $training = $this->training();

        $first = app(AcademyService::class)->createTrainee([
            'name' => 'First Trainee',
            'email' => 'first@academy.test',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        $second = app(AcademyService::class)->createTrainee([
            'name' => 'Second Trainee',
            'email' => 'second@academy.test',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        $enrollment = app(AcademyService::class)->enroll($first, $training->id, $owner->id);
        $certificate = app(AcademyService::class)->issueCertificate($enrollment, 90.0, $owner->id);

        $this->actingAs($second->user)
            ->get(route('portal.certificates.show', $certificate->id))
            ->assertForbidden();
    }

    public function test_portal_trainee_can_enroll_offline_on_paid_program(): void
    {
        $this->owner();
        $training = $this->training(['price' => 150000]);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => 'Offline Trainee',
            'email' => 'offline@academy.test',
            'phone' => '08099998888',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        $this->actingAs($trainee->user)
            ->post(route('portal.enroll', $training->id), ['mode' => 'offline'])
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'document_type' => 'enrollment',
            'document_id' => $training->id,
            'trainee_id' => $trainee->id,
            'gateway' => Payment::GATEWAY_LOCAL,
            'payment_method' => 'bank_transfer',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_portal_trainee_cannot_enroll_in_paid_program_without_payment_choice(): void
    {
        $this->owner();
        $training = $this->training(['price' => 150000]);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => 'Guard Trainee',
            'email' => 'guard@academy.test',
            'phone' => '08011112222',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        $this->from(route('portal.dashboard'))
            ->actingAs($trainee->user)
            ->post(route('portal.enroll', $training->id))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('enrollments', [
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
        ]);
        $this->assertDatabaseMissing('payments', [
            'document_type' => 'enrollment',
            'trainee_id' => $trainee->id,
        ]);
    }

    public function test_payment_callback_verification_applies_enrollment(): void
    {
        $this->owner();
        $training = $this->training(['price' => 150000]);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => 'Callback Trainee',
            'email' => 'callback@academy.test',
            'phone' => '08022223333',
            'type' => Trainee::TYPE_TRAINEE,
            'password' => 'password123',
            'create_login' => true,
        ], null);

        PaymentService::recordPayment(
            type: 'payment_in',
            amount: $training->price,
            paymentMethod: 'paystack',
            documentType: 'enrollment',
            documentId: $training->id,
            customerId: null,
            traineeId: $trainee->id,
            supplierId: null,
            reference: 'PAY-TEST-REF',
            userId: $trainee->user->id,
            gateway: Payment::GATEWAY_PAYSTACK,
            gatewayReference: 'PAY-TEST-REF',
            status: Payment::STATUS_PENDING,
        );

        $fake = \Mockery::mock(PaystackService::class);
        $fake->shouldReceive('isConfigured')->andReturn(true);
        $fake->shouldReceive('verify')->andReturn(['status' => true]);
        $fake->shouldReceive('isSuccessfulVerification')->andReturn(true);
        $this->app->instance(PaystackService::class, $fake);

        $callbackUrl = route('training.payment.callback', $training) . '?reference=PAY-TEST-REF';

        $this->get($callbackUrl)
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'trainee_id' => $trainee->id,
            'training_id' => $training->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'reference' => 'PAY-TEST-REF',
            'gateway' => Payment::GATEWAY_PAYSTACK,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        // Running the verification twice must stay idempotent (webhook + callback overlap).
        $this->get($callbackUrl)
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame(1, Enrollment::where('trainee_id', $trainee->id)
            ->where('training_id', $training->id)
            ->count());
    }
}