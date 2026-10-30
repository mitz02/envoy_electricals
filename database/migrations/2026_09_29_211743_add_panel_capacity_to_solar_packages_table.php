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
            $table->integer('panel_count')->nullable()->after('custom_inverter_capacity');
            $table->string('panel_wattage')->nullable()->after('panel_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_packages', function (Blueprint $table) {
            $table->dropColumn(['panel_count', 'panel_wattage']);
        });
    }
};
