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
        Schema::table('solar_packages', function (Blueprint $table) {
            $table->string('inverter_capacity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_packages', function (Blueprint $table) {
            $table->enum('inverter_capacity', ['1kva', '2.5kva', '3kva', '5kva', '7.5kva', '10kva', '15kva', 'custom'])->nullable()->change();
        });
    }
};
