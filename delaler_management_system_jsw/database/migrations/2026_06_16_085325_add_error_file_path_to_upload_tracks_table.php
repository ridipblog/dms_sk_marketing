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
        Schema::table('upload_tracks', function (Blueprint $table) {
            $table->string('error_file_path')->nullable()->after('error_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_tracks', function (Blueprint $table) {
            $table->dropColumn('error_file_path');
        });
    }
};
