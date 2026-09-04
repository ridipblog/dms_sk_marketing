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
        Schema::table('daily_stock_reports', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'date']);
            $table->unsignedBigInteger('product_id')->nullable()->after('company_id')->index();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['company_id', 'product_id', 'date'], 'company_product_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_stock_reports', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique('company_product_date_unique');
            $table->dropColumn('product_id');
            $table->unique(['company_id', 'date']);
        });
    }
};
