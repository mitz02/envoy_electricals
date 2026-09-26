<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'store_id']);
        });

        // Carry existing single-branch assignments into the new allow-list so
        // restricted users keep exactly the access they had before.
        DB::table('users')
            ->whereNotNull('store_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('user_store')->insertOrIgnore([
                    'user_id' => $user->id,
                    'store_id' => $user->store_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_store');
    }
};
