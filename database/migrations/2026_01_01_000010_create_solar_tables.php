<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solar_packages', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('package_price', 14, 2)->default(0);
            $table->decimal('installation_cost', 14, 2)->default(0);
            $table->string('estimated_load_capacity')->nullable();
            $table->enum('inverter_capacity', ['1kva', '2.5kva', '3kva', '5kva', '7.5kva', '10kva', '15kva', 'custom'])->nullable();
            $table->string('warranty')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('availability', ['available', 'unavailable'])->default('available');
            $table->json('components_json')->nullable();
            $table->unsignedBigInteger('featured_image_media_id')->nullable();
            $table->boolean('is_visible_online')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('solar_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solar_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->string('specification')->nullable();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('solar_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->json('appliances_json')->nullable();
            $table->decimal('total_connected_load', 10, 2)->default(0);
            $table->decimal('daily_consumption_kwh', 10, 2)->default(0);
            $table->decimal('peak_load_kw', 10, 2)->default(0);
            $table->string('recommended_inverter')->nullable();
            $table->integer('recommended_panels')->nullable();
            $table->string('recommended_battery')->nullable();
            $table->foreignId('recommended_package_id')->nullable()->constrained('solar_packages')->nullOnDelete();
            $table->decimal('estimated_price', 14, 2)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('location')->nullable();
            $table->enum('lead_status', ['new', 'contacted', 'quoted', 'approved', 'installation', 'completed', 'lost'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_status', 'created_at']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('location')->nullable();
            $table->json('appliances_json')->nullable();
            $table->string('recommended_system')->nullable();
            $table->foreignId('solar_package_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_price', 14, 2)->nullable();
            $table->enum('status', ['new', 'contacted', 'quoted', 'approved', 'declined', 'converted'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('solar_calculations');
        Schema::dropIfExists('solar_package_items');
        Schema::dropIfExists('solar_packages');
    }
};