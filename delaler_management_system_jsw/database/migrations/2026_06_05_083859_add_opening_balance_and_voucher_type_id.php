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
        Schema::table('dealer_companies', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('company_id');
        });

        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_type_id')->nullable();
            $table->foreign('voucher_type_id')->references('id')->on('voucher_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->dropForeign(['voucher_type_id']);
            $table->dropColumn('voucher_type_id');
        });

        Schema::table('dealer_companies', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
