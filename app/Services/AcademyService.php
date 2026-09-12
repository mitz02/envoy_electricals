<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Staff;
use App\Models\Trainee;
use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AcademyService
{
    /**
     * Create a trainee profile, optionally provisioning a user login account.
     */
    public function createTrainee(array $data, ?int $userId): Trainee
    {
        return DB::transaction(function () use ($data, $userId) {
            $loginUser = $this->createLoginUserIfRequested($data, $userId);

            $trainee = Trainee::create([
                'ref_id' => ReferenceGenerator::generate('trainee'),
                'user_id' => $loginUser?->id,
                'staff_id' => $this->resolveStaffId($data['email'] ?? null),
                'type' => $data['type'] ?? Trainee::TYPE_TRAINEE,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'education' => $data['education'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? Trainee::STATUS_ACTIVE,
                'created_by' => $userId,
            ]);

            AuditLogger::log('created', 'trainee', $trainee->id, "Created trainee {$trainee->ref_id}: {$trainee->name}");

            return $trainee;
        });
    }

    public function updateTrainee(Trainee $trainee, array $data, ?int $userId): Trainee
    {
        $trainee->update([
            'type' => $data['type'] ?? $trainee->type,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'education' => $data['education'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? $trainee->status,
        ]);

        AuditLogger::log('updated', 'trainee', $trainee->id, "Updated trainee {$trainee->ref_id}: {$trainee->name}");

        return $trainee;
    }

    public function destroyTrainee(Trainee $trainee, ?int $userId): void
    {
        $name = $trainee->name;

        $trainee->delete();

        AuditLogger::log('deleted', 'trainee', $trainee->id, "Deleted trainee record for {$name}");
    }

    /**
     * Enroll a trainee in a training program.
     *
     * @throws RuntimeException
     */
    public function enroll(Trainee $trainee, int $trainingId, ?int $userId, ?string $enrolledAt = null): Enrollment
    {
        return DB::transaction(function () use ($trainee, $trainingId, $userId, $enrolledAt) {
            $existing = Enrollment::where('trainee_id', $trainee->id)
                ->where('training_id', $trainingId)
                ->exists();

            if ($existing) {
                throw new RuntimeException('This trainee is already enrolled in that program.');
            }

            $training = Training::findOrFail($trainingId);

            if ($training->is_active === false) {
                throw new RuntimeException('This training program is no longer accepting enrollments.');
            }

            $capacity = $training->capacity;

            if ($capacity !== null && $training->enrolled_count >= (int) $capacity) {
                throw new RuntimeException('This training program has reached its capacity.');
            }

            $enrollment = Enrollment::create([
                'ref_id' => ReferenceGenerator::generate('enrollment'),
                'trainee_id' => $trainee->id,
                'training_id' => $trainingId,
                'enrolled_at' => $enrolledAt ?? now()->toDateString(),
                'status' => Enrollment::STATUS_ENROLLED,
                'progress' => 0,
                'created_by' => $userId,
            ]);

            AuditLogger::log('created', 'enrollment', $enrollment->id, "Enrolled {$trainee->name} ({$trainee->ref_id}) in {$training->title}", userId: $userId);

            return $enrollment;
        });
    }

    /**
     * Update a trainee's progress. Reaching 100% marks the program complete and issues a certificate.
     *
     * @throws RuntimeException
     */
    public function updateProgress(Enrollment $enrollment, int $progress, ?int $userId, ?float $grade = null): Enrollment
    {
        if ($enrollment->status === Enrollment::STATUS_WITHDRAWN) {
            throw new RuntimeException('A withdrawn enrollment cannot be progressed.');
        }

        $progress = max(0, min(100, $progress));
        $completed = $progress >= 100;

        $enrollment->update([
            'progress' => $progress,
            'grade' => $grade,
            'status' => $completed ? Enrollment::STATUS_COMPLETED : Enrollment::STATUS_IN_PROGRESS,
        ]);

        if ($completed) {
            $this->issueCertificate($enrollment, $grade, $userId);
        }

        AuditLogger::log('updated', 'enrollment', $enrollment->id, "Progress set to {$progress}% for enrollment {$enrollment->ref_id}", userId: $userId);

        return $enrollment;
    }

    public function withdraw(Enrollment $enrollment, ?int $userId): Enrollment
    {
        if ($enrollment->status === Enrollment::STATUS_COMPLETED) {
            throw new RuntimeException('A completed enrollment cannot be withdrawn.');
        }

        $enrollment->update([
            'status' => Enrollment::STATUS_WITHDRAWN,
            'progress' => 0,
        ]);

        AuditLogger::log('updated', 'enrollment', $enrollment->id, "Withdrew enrollment {$enrollment->ref_id}", userId: $userId);

        return $enrollment;
    }

    public function destroyEnrollment(Enrollment $enrollment, ?int $userId): void
    {
        $ref = $enrollment->ref_id;

        $enrollment->delete();

        AuditLogger::log('deleted', 'enrollment', $enrollment->id, "Removed enrollment {$ref}", userId: $userId);
    }

    /**
     * Issue a certificate for a completed enrollment.
     *
     * @throws RuntimeException
     */
    public function issueCertificate(Enrollment $enrollment, ?float $grade, ?int $userId): Certificate
    {
        $existing = $enrollment->certificate()->where('status', Certificate::STATUS_ISSUED)->exists();

        if ($existing) {
            throw new RuntimeException('A certificate has already been issued for this enrollment.');
        }

        $certNo = ReferenceGenerator::generate('certificate');

        $certificate = Certificate::create([
            'ref_id' => $certNo,
            'enrollment_id' => $enrollment->id,
            'trainee_id' => $enrollment->trainee_id,
            'training_id' => $enrollment->training_id,
            'certificate_no' => $certNo,
            'grade' => $grade,
            'issued_at' => now()->toDateString(),
            'status' => Certificate::STATUS_ISSUED,
            'issued_by' => $userId,
        ]);

        AuditLogger::log('created', 'certificate', $certificate->id, "Issued {$certNo} to {$enrollment->trainee->name} for {$enrollment->training->title}", userId: $userId);

        return $certificate;
    }

    public function voidCertificate(Certificate $certificate, ?int $userId): Certificate
    {
        if ($certificate->status === Certificate::STATUS_VOID) {
            throw new RuntimeException('This certificate has already been voided.');
        }

        $certificate->update([
            'status' => Certificate::STATUS_VOID,
            'voided_by' => $userId,
            'voided_at' => now(),
        ]);

        AuditLogger::log('updated', 'certificate', $certificate->id, "Voided certificate {$certificate->certificate_no}", userId: $userId);

        return $certificate;
    }

    /**
     * Provision a user login account (trainee role) when requested during admin creation.
     */
    protected function createLoginUserIfRequested(array $data, ?int $userId): ?User
    {
        if (! ($data['create_login'] ?? false)) {
            return null;
        }

        $email = $data['email'] ?? null;

        if (! $email) {
            throw new RuntimeException('An email is required to create login access.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? '',
                'role_id' => \App\Models\Role::where('slug', 'trainee')->value('id'),
                'is_active' => true,
            ]);
        }

        return $user;
    }

    protected function resolveStaffId(?string $email): ?int
    {
        if (! $email) {
            return null;
        }

        return Staff::where('email', $email)->value('id');
    }
}