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
        Schema::create('payment_tracks', function (Blueprint $table) {
            $table->id();


            $table->string('transaction_id', 100)
                ->nullable();

            $table->decimal('amount', 18, 2);
            $table->decimal('balance_amount', 18, 2)->nullable();

            $table->decimal('payment_for_mt', 18, 3)->nullable();

            $table->enum('payment_mode', [
                'cash',
                'bank_transfer',
                'cheque',
                'upi',
                'adjustment',
                'entry'
            ]);


            $table->dateTime('transaction_date');

            $table->text('remarks')
                ->nullable();

            $table->unsignedBigInteger('modified_by')->nullable();
            // $table->foreign('modified_by', 'fk_payment_tracks_modified_by')->references('id')->on('user_role_companies');
            $table->softDeletes();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_tracks');
    }
};
