<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('success')->after('type');
            $table->string('gateway')->default('local')->after('status');
            $table->string('gateway_reference')->nullable()->after('reference');
            $table->timestamp('paid_at')->nullable()->after('gateway_reference');

            $table->unique('gateway_reference');
            $table->index('status');
            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropIndex(['status']);
            $table->dropIndex(['gateway']);
            $table->dropColumn(['status', 'gateway', 'gateway_reference', 'paid_at']);
        });
    }
};