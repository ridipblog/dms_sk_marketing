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
        Schema::create('purchase_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 15, 3)->nullable()->comment('Store in MT');
            $table->decimal('rate', 15, 2)->nullable()->comment('Purchase price per MT');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('without GST');
            $table->decimal('cgst_amount', 15, 2)->nullable();
            $table->decimal('sgst_amount', 15, 2)->nullable();
            $table->decimal('gst_amount', 15, 2)->nullable()->comment('cgst + sgst');
            $table->decimal('chargeable_amount', 15, 2)->nullable()->comment('with GST');
            $table->timestamps();

            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_details');
    }
};
