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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_pricing_id')->nullable();
            $table->decimal('cgst_amount', 15, 2)->default(9)->comment('default 9%');
            $table->decimal('sgst_amount', 15, 2)->default(9)->comment('default 9%');
            $table->decimal('gst_amount', 15, 2)->nullable()->comment('cgst + sgst');
            $table->decimal('quantity', 15, 3)->nullable()->comment('Store in MT');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('without GST');
            $table->decimal('chargeable_amount', 15, 2)->nullable()->comment('with GST');

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('product_pricing_id')->references('id')->on('product_pricings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
