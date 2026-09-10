<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('housing_allowance', 14, 2)->default(0)->after('base_salary');
            $table->decimal('transport_allowance', 14, 2)->default(0)->after('housing_allowance');
            $table->decimal('other_allowance', 14, 2)->default(0)->after('transport_allowance');
        });

        Schema::table('payroll', function (Blueprint $table) {
            $table->index(['staff_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            $table->dropIndex(['staff_id', 'period_year', 'period_month']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['housing_allowance', 'transport_allowance', 'other_allowance']);
        });
    }
};