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
            if (!Schema::hasColumn('scheme_amounts', 'is_used')) {
                $table->boolean('is_used')->nullable()->default(0)->comment('0 = Not Used, 1 = Used')->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_amounts', function (Blueprint $table) {
            if (Schema::hasColumn('scheme_amounts', 'is_used')) {
                $table->dropColumn('is_used');
            }
        });
    }
};
