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
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('token')->unique()->nullable()->after('id');
            $table->string('status')->default('pending')->after('token'); // pending, approved, rejected
            $table->string('email')->nullable()->after('author_role');
            $table->string('phone')->nullable()->after('email');
            $table->text('admin_notes')->nullable()->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['token', 'status', 'email', 'phone', 'admin_notes']);
        });
    }
};
