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
            if (!Schema::hasColumn('invoices', 'vehicle_no')) {
                $table->string('vehicle_no')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('invoices', 'delivery_note')) {
                $table->string('delivery_note')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('invoices', 'destination')) {
                $table->string('destination')->nullable()->after('delivery_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('invoices', 'vehicle_no')) {
                $columnsToDrop[] = 'vehicle_no';
            }
            if (Schema::hasColumn('invoices', 'delivery_note')) {
                $columnsToDrop[] = 'delivery_note';
            }
            if (Schema::hasColumn('invoices', 'destination')) {
                $columnsToDrop[] = 'destination';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
