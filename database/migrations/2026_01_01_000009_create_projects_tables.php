<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_address')->nullable();
            $table->string('location')->nullable();
            $table->decimal('contract_value', 14, 2)->default(0);
            $table->decimal('material_cost', 14, 2)->default(0);
            $table->decimal('labour_cost', 14, 2)->default(0);
            $table->decimal('transport_cost', 14, 2)->default(0);
            $table->decimal('other_cost', 14, 2)->default(0);
            $table->decimal('project_cost', 14, 2)->default(0);
            $table->decimal('amount_received', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('gross_profit', 14, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['draft', 'quotation', 'approved', 'in_progress', 'installation', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('technician_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('published')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'customer_id', 'start_date']);
        });

        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->date('issued_date')->nullable();
            $table->boolean('issued_to_inventory')->default(false);
            $table->timestamps();
            $table->index(['project_id', 'product_id']);
        });

        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->string('stage')->nullable();
            $table->boolean('published')->default(false);
            $table->unsignedBigInteger('media_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_media');
        Schema::dropIfExists('project_payments');
        Schema::dropIfExists('project_materials');
        Schema::dropIfExists('projects');
    }
};