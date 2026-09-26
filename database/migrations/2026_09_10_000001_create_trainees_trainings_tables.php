<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainees', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['staff', 'apprentice', 'trainee'])->default('trainee');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'active', 'graduated', 'withdrawn'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('curriculum')->nullable();
            $table->unsignedSmallInteger('duration_weeks')->default(1);
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('trainees');
    }
};
