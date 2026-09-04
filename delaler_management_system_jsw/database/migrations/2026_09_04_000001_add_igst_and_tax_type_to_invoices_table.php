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
            if (!Schema::hasColumn('invoices', 'tax_type')) {
                $table->string('tax_type', 20)->default('intra')->after('ship_to')->comment('intra => CGST+SGST, inter => IGST');
            }
            if (!Schema::hasColumn('invoices', 'igst')) {
                $table->decimal('igst', 5, 2)->default(0)->after('sgst')->comment('IGST rate percentage');
            }
            if (!Schema::hasColumn('invoices', 'total_igst_amount')) {
                $table->decimal('total_igst_amount', 15, 2)->nullable()->after('total_sgst_amount');
            }
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_details', 'igst_amount')) {
                $table->decimal('igst_amount', 15, 2)->default(0)->after('sgst_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'igst', 'total_igst_amount']);
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn(['igst_amount']);
        });
    }
};
