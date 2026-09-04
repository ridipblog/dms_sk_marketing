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
        Schema::create('daily_stock_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->date('date')->index();
            $table->decimal('opening_stock', 15, 3)->default(0.000);
            $table->decimal('sale_quantity', 15, 3)->default(0.000);
            $table->decimal('purchase_quantity', 15, 3)->default(0.000);
            $table->decimal('closing_stock', 15, 3)->default(0.000);
            $table->timestamps();

            $table->unique(['company_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_stock_reports');
    }
};
