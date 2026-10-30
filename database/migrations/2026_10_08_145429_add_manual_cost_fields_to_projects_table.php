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
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('manual_material_cost', 14, 2)->nullable()->after('material_cost');
            $table->decimal('manual_labour_cost', 14, 2)->nullable()->after('labour_cost');
            $table->decimal('manual_transport_cost', 14, 2)->nullable()->after('transport_cost');
            $table->decimal('manual_other_cost', 14, 2)->nullable()->after('other_cost');
            $table->decimal('manual_project_cost', 14, 2)->nullable()->after('project_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'manual_material_cost',
                'manual_labour_cost',
                'manual_transport_cost',
                'manual_other_cost',
                'manual_project_cost',
            ]);
        });
    }
};
