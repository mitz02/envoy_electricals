<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('trainee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'withdrawn'])->default('enrolled');
            $table->unsignedSmallInteger('progress')->default(0);
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['trainee_id', 'training_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_no')->unique();
            $table->decimal('grade', 5, 2)->nullable();
            $table->date('issued_at');
            $table->enum('status', ['issued', 'void'])->default('issued');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('enrollments');
    }
};