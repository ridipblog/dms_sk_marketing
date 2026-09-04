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
        Schema::table('debit_note_tracks', function (Blueprint $table) {
            $table->decimal('base_amount', 18, 2)->nullable()->after('amount');
            $table->decimal('gst_amount', 18, 2)->nullable()->after('base_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_note_tracks', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'gst_amount']);
        });
    }
};
