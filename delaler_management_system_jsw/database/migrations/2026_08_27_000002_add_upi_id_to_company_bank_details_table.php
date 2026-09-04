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
        Schema::table('company_bank_details', function (Blueprint $table) {
            if (!Schema::hasColumn('company_bank_details', 'upi_id')) {
                $table->string('upi_id')->nullable()->after('branch_name')->comment('Company UPI ID / VPA');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_bank_details', function (Blueprint $table) {
            if (Schema::hasColumn('company_bank_details', 'upi_id')) {
                $table->dropColumn('upi_id');
            }
        });
    }
};
