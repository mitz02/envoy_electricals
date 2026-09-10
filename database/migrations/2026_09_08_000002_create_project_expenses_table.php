<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('expense_type', ['labour', 'transport', 'other'])->default('other');
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->string('payee')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'expense_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_expenses');
    }
};