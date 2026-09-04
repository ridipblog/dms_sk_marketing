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
        Schema::create('scheme_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_company_id')->constrained('dealer_companies')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('user_role_company map ID');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('user_role_companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheme_amounts');
    }
};
