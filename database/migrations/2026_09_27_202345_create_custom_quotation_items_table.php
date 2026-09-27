<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_quotation_id')->constrained()->cascadeOnDelete();
            $table->string('item'); // Item name
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->nullable(); // pcs, m, hours, etc.
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_quotation_items');
    }
};
