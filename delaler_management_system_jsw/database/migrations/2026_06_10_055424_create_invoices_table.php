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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('ship_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('user_role_company_mapId');
            $table->decimal('total_quantity', 15, 3)->nullable()->comment('Store In MT');
            $table->decimal('total_amount', 15, 2)->nullable()->comment('without GST');
            $table->decimal('chargeable_amount', 15, 2)->nullable()->comment('with GST');
            $table->decimal('total_gst_amount', 15, 2)->nullable();
            $table->decimal('total_cgst_amount', 15, 2)->nullable();
            $table->decimal('total_sgst_amount', 15, 2)->nullable();
            $table->decimal('cgst', 5, 2)->default(9)->comment('CGST default 9%');
            $table->decimal('sgst', 5, 2)->default(9)->comment('SGST default 9%');
            $table->decimal('gst', 5, 2)->default(18)->comment('GST default 18%');
            $table->date('invoice_generate_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('no_of_goods')->nullable();
            $table->decimal('round_of', 15, 2)->nullable();
            $table->tinyInteger('invoice_status')->default(0)->comment('0=>not_generated,1=>generated')->nullable();
            $table->timestamps();

            $table->foreign('buyer_id')->references('id')->on('dealer_companies')->onDelete('cascade');
            $table->foreign('ship_to')->references('id')->on('dealer_companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('user_role_companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
