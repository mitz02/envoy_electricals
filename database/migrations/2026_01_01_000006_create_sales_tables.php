<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('invoice_no')->unique();
            $table->date('sale_date');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->enum('status', ['completed', 'void'])->default('completed');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_date', 'status', 'customer_id']);
            $table->index('invoice_no');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->decimal('profit', 14, 2)->default(0);
            $table->timestamps();
            $table->index(['sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
