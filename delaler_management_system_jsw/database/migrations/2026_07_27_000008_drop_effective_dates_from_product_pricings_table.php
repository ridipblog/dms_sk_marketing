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
        Schema::table('product_pricings', function (Blueprint $table) {
            if (Schema::hasColumn('product_pricings', 'effective_from')) {
                $table->dropColumn('effective_from');
            }
            if (Schema::hasColumn('product_pricings', 'effective_to')) {
                $table->dropColumn('effective_to');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_pricings', function (Blueprint $table) {
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
        });
    }
};
