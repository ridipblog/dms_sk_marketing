<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify payment_mode column in payment_tracks to VARCHAR(100) to support 'accounts' and any future payment modes
        DB::statement("ALTER TABLE payment_tracks MODIFY COLUMN payment_mode VARCHAR(100) NOT NULL");
        
        if (Schema::hasTable('purchase_payment_tracks')) {
            DB::statement("ALTER TABLE purchase_payment_tracks MODIFY COLUMN payment_mode VARCHAR(100) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
