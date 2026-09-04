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
        Schema::create('purchase_payment_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('transaction_id', 100)->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_amount', 18, 2)->nullable();
            $table->enum('payment_mode', [
                'cash',
                'bank_transfer',
                'cheque',
                'upi',
                'adjustment',
                'entry'
            ]);
            $table->unsignedBigInteger('voucher_type_id')->nullable();
            $table->dateTime('transaction_date');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('user_role_companies')->onDelete('set null');
            $table->foreign('voucher_type_id')->references('id')->on('voucher_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_tracks');
    }
};
