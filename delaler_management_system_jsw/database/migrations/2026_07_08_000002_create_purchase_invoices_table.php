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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('company_id');
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_quantity', 15, 3)->nullable()->comment('Store In MT');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('without GST');
            $table->decimal('total_gst_amount', 15, 2)->nullable();
            $table->decimal('total_cgst_amount', 15, 2)->nullable();
            $table->decimal('total_sgst_amount', 15, 2)->nullable();
            $table->decimal('chargeable_amount', 15, 2)->nullable()->comment('with GST');
            $table->integer('no_of_goods')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=>draft, 1=>finalized');
            $table->unsignedBigInteger('created_by')->nullable()->comment('user_role_company_mapId');
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('user_role_companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
