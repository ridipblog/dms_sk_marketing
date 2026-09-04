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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 20)->unique();
            $table->string('company_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('gst_no', 20)->nullable();
            $table->string('pan_no', 20)->nullable();

            $table->text('address')->nullable();

            $table->enum('status', ['active', 'inactive'])
                ->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
