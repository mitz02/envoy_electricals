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
        Schema::create('custom_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('ref_id')->unique();

            // Customer Information
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_company')->nullable();

            // Quotation Information
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('job_type'); // CCTV, Electrical Maintenance, Electric Fence, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Financial
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Status
            $table->string('status')->default('draft'); // draft, sent, viewed, accepted, rejected, expired, converted
            $table->unsignedBigInteger('project_id')->nullable(); // if converted to project

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'quotation_date']);
            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_quotations');
    }
};
