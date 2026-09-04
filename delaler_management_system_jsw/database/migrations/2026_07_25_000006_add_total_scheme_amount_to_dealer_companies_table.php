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
            if (!Schema::hasColumn('dealer_companies', 'total_scheme_amount')) {
                $table->decimal('total_scheme_amount', 15, 2)->default(0.00)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealer_companies', function (Blueprint $table) {
            if (Schema::hasColumn('dealer_companies', 'total_scheme_amount')) {
                $table->dropColumn('total_scheme_amount');
            }
        });
    }
};
