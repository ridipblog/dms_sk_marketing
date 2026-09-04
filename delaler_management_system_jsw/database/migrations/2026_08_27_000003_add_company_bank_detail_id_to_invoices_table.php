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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'company_bank_detail_id')) {
                $table->unsignedBigInteger('company_bank_detail_id')->nullable()->after('created_by')->comment('Company Bank Detail ID at time of invoice finalization');
                $table->foreign('company_bank_detail_id')->references('id')->on('company_bank_details')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'company_bank_detail_id')) {
                $table->dropForeign(['company_bank_detail_id']);
                $table->dropColumn('company_bank_detail_id');
            }
        });
    }
};
