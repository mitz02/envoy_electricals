<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('type');
            $table->integer('quantity_change');
            $table->integer('prev_quantity');
            $table->integer('new_quantity');
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->timestamp('movement_date')->useCurrent();
            $table->timestamps();

            $table->index(['product_id', 'movement_date']);
            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
