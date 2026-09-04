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
        Schema::create('product_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('price_per_mt', 18, 2);

            // ADD HERE
            $table->decimal('gst_percentage', 5, 2)
                ->default(0.00);

            $table->decimal('discount_amount', 18, 2)->nullable()->default(0.00);

            $table->string('price_type', 50)
                ->nullable();

            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('inactive');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_pricings');
    }
};
