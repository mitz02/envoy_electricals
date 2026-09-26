<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The free-text `brand` column shadowed the `brand()` relation, so the same
     * JSON key resolved to a string in some payloads and an object in others.
     * Values were already backfilled onto brands by the previous migration.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'brand')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'brand')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('subcategory_id');
        });
    }
};
