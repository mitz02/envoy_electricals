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
        Schema::create('battery_capacities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "100ah", "200ah", "5kwh"
            $table->string('label')->unique(); // e.g., "100 Ah", "200 Ah", "5 kWh" - display label
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_capacities');
    }
};
