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
            $table->foreignId('aso_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealer_companies', function (Blueprint $table) {
            $table->dropForeign(['aso_id']);
            $table->dropColumn('aso_id');
        });
    }
};
