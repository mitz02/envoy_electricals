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
            $table->string('battery_capacity')->nullable()->after('custom_inverter_capacity');
            $table->string('custom_battery_capacity')->nullable()->after('battery_capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_packages', function (Blueprint $table) {
            $table->dropColumn(['battery_capacity', 'custom_battery_capacity']);
        });
    }
};
