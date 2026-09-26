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
            $table->string('custom_inverter_capacity')->nullable()->after('inverter_capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_packages', function (Blueprint $table) {
            $table->dropColumn('custom_inverter_capacity');
        });
    }
};
