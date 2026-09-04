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
            $table->unsignedBigInteger('role_user_company_id')->nullable()->after('company_id');
            $table->foreign('role_user_company_id')->references('id')->on('user_role_companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_tracks', function (Blueprint $table) {
            $table->dropForeign(['role_user_company_id']);
            $table->dropColumn('role_user_company_id');
        });
    }
};
