<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->date('expense_date');
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('paid_to')->nullable();
            $table->string('reference_no')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('receipt_path')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->enum('status', ['recorded', 'void'])->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'expense_category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
