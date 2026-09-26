<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
            $table->date('start_date')->nullable()->after('duration_weeks');
            $table->text('prerequisites')->nullable()->after('description');
            $table->text('objectives')->nullable()->after('prerequisites');
            $table->text('learning_outcomes')->nullable()->after('objectives');
            $table->boolean('certificate_eligible')->default(true)->after('is_active');
            $table->boolean('is_featured')->default(false)->after('certificate_eligible');
        });

        Schema::create('training_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number')->default(1);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['training_id', 'week_number']);
        });

        Schema::create('training_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_week_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['training_week_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lessons');
        Schema::dropIfExists('training_weeks');

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn([
                'image_path', 'start_date', 'prerequisites', 'objectives',
                'learning_outcomes', 'certificate_eligible', 'is_featured',
            ]);
        });
    }
};
