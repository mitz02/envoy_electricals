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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete()->after('product_id');
            $table->foreignId('from_store_id')->nullable()->constrained('stores')->nullOnDelete()->after('store_id');
            $table->foreignId('to_store_id')->nullable()->constrained('stores')->nullOnDelete()->after('from_store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['from_store_id']);
            $table->dropForeign(['to_store_id']);
            $table->dropColumn(['store_id', 'from_store_id', 'to_store_id']);
        });
    }
};
