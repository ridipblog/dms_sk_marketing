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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'tax_type')) {
                $table->string('tax_type', 20)->default('intra')->after('status')->comment('intra => CGST+SGST, inter => IGST');
            }
            if (!Schema::hasColumn('purchase_invoices', 'total_igst_amount')) {
                $table->decimal('total_igst_amount', 15, 2)->nullable()->after('total_sgst_amount');
            }
        });

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_details', 'igst_amount')) {
                $table->decimal('igst_amount', 15, 2)->default(0)->after('sgst_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'tax_type')) {
                $table->dropColumn('tax_type');
            }
            if (Schema::hasColumn('purchase_invoices', 'total_igst_amount')) {
                $table->dropColumn('total_igst_amount');
            }
        });

        Schema::table('purchase_invoice_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_details', 'igst_amount')) {
                $table->dropColumn('igst_amount');
            }
        });
    }
};
