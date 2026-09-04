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
        Schema::table('scheme_amounts', function (Blueprint $table) {
            if (!Schema::hasColumn('scheme_amounts', 'quantity')) {
                $table->decimal('quantity', 15, 3)->nullable()->comment('Quantity in MT')->after('dealer_company_id');
            }
            if (!Schema::hasColumn('scheme_amounts', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->comment('Month 1-12')->after('quantity');
            }
            if (!Schema::hasColumn('scheme_amounts', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->comment('Year YYYY')->after('month');
            }
            if (!Schema::hasColumn('scheme_amounts', 'rate_per_mt')) {
                $table->decimal('rate_per_mt', 15, 2)->nullable()->comment('Rate per MT')->after('year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_amounts', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('scheme_amounts', 'quantity')) {
                $columns[] = 'quantity';
            }
            if (Schema::hasColumn('scheme_amounts', 'month')) {
                $columns[] = 'month';
            }
            if (Schema::hasColumn('scheme_amounts', 'year')) {
                $columns[] = 'year';
            }
            if (Schema::hasColumn('scheme_amounts', 'rate_per_mt')) {
                $columns[] = 'rate_per_mt';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
