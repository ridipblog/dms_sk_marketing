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
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->decimal('scheme_amount', 18, 2)->default(0)->after('balance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->dropColumn('scheme_amount');
        });
    }
};
